<?php

namespace App\Services\Receipts;

use App\Models\Client;
use App\Models\Collection;
use App\Models\Invoice;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The one place receipts are created and edited — the single-invoice form,
 * the multi-invoice form and the edit form all come through here.
 *
 * Balances are never typed or chosen. For every invoice:
 *
 *     outstanding = invoice.total − SUM(receipt_allocations.settled)
 *
 * and the invoice's status/balance are re-derived from that after every
 * change (InvoiceSettlement::recalculate). The cashier's old "completed /
 * uncompleted" choice is gone: a receipt is "completed" when every invoice it
 * pays is fully settled.
 *
 * Money that is not needed for the invoices (overpayment, or a payment with
 * no invoice) is kept on the receipt as client credit (unapplied_amount) —
 * only when the cashier ticks "keep as credit", so a typing mistake (an extra
 * zero) is caught instead of silently becoming credit.
 *
 * Normalised input ($input):
 *   mode[]                 ticked payment modes
 *   <payment fields>       cash_amount, cheque_*, transfer_*, momo_*, other_*
 *   from, receipt_month, staff, description
 *   keep_credit            bool
 *   invoices               [invoice_id => ['applied' => ?float, 'wht' => float, 'vat7' => float, 'deduction' => float]]
 */
class ReceiptRecorder
{
    /** Payment fields that belong to each mode. Unticked modes are always stored as null. */
    public const MODE_FIELDS = [
        'cheque' => ['cheque_reference', 'cheque_amount', 'cheque_bank', 'cheque_to_bank_id'],
        'transfer' => ['transfer_reference', 'transfer_amount', 'transfer_bank', 'transfer_to_bank_id'],
        'momo' => ['momo_transactin_id', 'momo_amount'],
        'other payments' => ['other_payment_descri', 'other_payment_amnt'],
        'cash' => ['cash_amount'],
    ];

    public const AMOUNT_FIELDS = ['cheque_amount', 'transfer_amount', 'momo_amount', 'other_payment_amnt', 'cash_amount'];

    /** Messages for the cashier collected while recording (e.g. category already set). */
    public array $notes = [];

    public function __construct(
        private ReceiptAllocator $allocator,
        private InvoiceSettlement $settlement,
        private BankPosting $bankPosting,
        private ReceiptWorkflow $workflow,
        private ClientLedger $ledger,
    ) {
    }

    /* ------------------------------------------------------------------
     | Input
     * ------------------------------------------------------------------ */

    /** Common fields from any receipt form. */
    public static function baseInput(Request $request): array
    {
        $input = [
            'mode' => array_values((array) $request->input('mode', [])),
            'from' => $request->input('from'),
            'receipt_month' => $request->input('receipt_month'),
            'staff' => $request->input('staff'),
            'description' => $request->input('description'),
            'keep_credit' => in_array($request->input('keep_credit'), ['1', 'on', 1, true], true),
            'confirm_duplicate' => $request->input('confirm_duplicate') === '1',
        ];
        foreach (self::MODE_FIELDS as $fields) {
            foreach ($fields as $field) {
                $input[$field] = $request->input($field);
            }
        }

        return $input;
    }

    /**
     * The single-invoice edit form (receipt_edit) and the POST /receipt endpoint: WHT, VAT and
     * deductions are switches + amounts for its one invoice.
     */
    public static function singleInvoiceInput(Request $request, int $invoiceId): array
    {
        $on = fn (string $name) => $request->input($name) === 'on';

        return self::baseInput($request) + ['invoices' => [
            $invoiceId => [
                'applied' => null,
                'wht' => $on('wth') ? (float) $request->input('wht_amount') : 0,
                'vat7' => $on('vat') ? (float) $request->input('vat7_value') : 0,
                'deduction' => $on('deductions') ? (float) $request->input('dAmount') : 0,
            ],
        ]];
    }

    /** The multi-invoice form: only ticked rows. */
    public static function multiInvoiceInput(Request $request): array
    {
        $invoices = collect((array) $request->input('invoices', []))
            ->filter(fn ($row) => ($row['selected'] ?? null) === '1')
            ->map(fn ($row) => [
                'applied' => ($row['applied'] ?? '') === '' ? null : $row['applied'],
                'wht' => $row['wht'] ?? 0,
                'vat7' => $row['vat7'] ?? 0,
                'deduction' => $row['deduction'] ?? 0,
            ])
            ->all();

        return self::baseInput($request) + ['invoices' => $invoices];
    }

    /* ------------------------------------------------------------------
     | Create / revise
     * ------------------------------------------------------------------ */

    public function create(Client $client, array $input, ?UploadedFile $image = null): Receipt
    {
        $this->notes = [];

        return DB::transaction(function () use ($client, $input, $image) {
            [$payment, $money] = $this->payment($input);
            if (empty($input['confirm_duplicate'])) {
                $this->refuseLikelyDuplicate($client, $payment, $money);
            }
            $invoices = $this->lockOpenInvoices($client->id, array_keys($input['invoices']));
            $plan = $this->plan($invoices, $input, $money);

            $receipt = new Receipt();
            $receipt->client_id = $client->id;
            $receipt->user_id = Auth::id();
            $this->workflow->applyApproval($receipt, $input['staff'] ?? null);
            $this->fill($receipt, $input, $payment, $money, $plan, $invoices);
            $receipt->image = $image?->store('images', 'public_html_disk');
            $receipt->save();

            $this->allocate($receipt, $invoices, $plan, false);
            $this->writeCollection($receipt, $client);
            $this->bankPosting->sync($receipt);
            $this->ledger->sync($client->id);

            return $receipt;
        });
    }

    /**
     * Edit a receipt: undo what it did to its invoices, then apply the new
     * amounts from scratch. Other receipts on the same invoices are untouched,
     * so editing can never double-count or wipe another payment.
     */
    public function revise(Receipt $receipt, array $input, ?UploadedFile $image = null): Receipt
    {
        $this->notes = [];

        return DB::transaction(function () use ($receipt, $input, $image) {
            $receipt = Receipt::whereKey($receipt->id)->lockForUpdate()->firstOrFail();
            if (\App\Services\Banking\DepositBook::anyPartDeposited(Collection::where('receipt_id', $receipt->id)->first())) {
                throw ValidationException::withMessages(['receipt' => 'Money on this receipt has already been deposited, so it cannot be changed. Reverse the deposit first.']);
            }
            [$payment, $money] = $this->payment($input);

            // Credit from this receipt already applied to other invoices stays applied;
            // the edited receipt must still cover it.
            $creditUsed = $this->creditUsed($receipt);
            $this->settlement->release($receipt, true);

            $invoices = $this->lockOpenInvoices($receipt->client_id, array_keys($input['invoices']));
            if ($creditUsed > 0 && $money < $creditUsed) {
                throw ValidationException::withMessages(['invoices' => 'GH₵' . number_format($creditUsed, 2)
                    . ' of this receipt\'s credit is already applied to other invoices, so the money received cannot be less than that.']);
            }
            $plan = $this->plan($invoices, $input, round($money - $creditUsed, 2), $creditUsed);

            $this->fill($receipt, $input, $payment, $money, $plan, $invoices);
            if ($creditUsed > 0) {
                $receipt->advance_payment = 'advance';
            }
            if ($image) {
                $receipt->image = $image->store('images', 'public_html_disk');   // otherwise keep the existing copy
            }
            $receipt->save();

            $this->allocate($receipt, $invoices, $plan, true);
            if ($creditUsed > 0) {
                // Completed only when every invoice it pays (including through its credit) is paid.
                $receipt->status = Invoice::whereIn('id', $receipt->allocations()->pluck('invoice_id'))
                    ->where('status', '!=', 'completed')->doesntExist() ? 'completed' : 'uncompleted';
                $receipt->save();
            }
            $this->writeCollection($receipt, Client::find($receipt->client_id));
            $this->bankPosting->sync($receipt);
            $this->ledger->sync($receipt->client_id);

            return $receipt;
        });
    }

    /* ------------------------------------------------------------------
     | Steps
     * ------------------------------------------------------------------ */

    /** Minutes within which the same amount for the same client is treated as a likely repeat. */
    public const REPEAT_WINDOW_MINUTES = 10;

    /**
     * Stop a receipt that looks like one already saved, unless the cashier
     * confirms it is a separate payment:
     *  - the same cheque number from the same bank, transfer reference or
     *    MoMo transaction ID is already on a receipt (any date, any client); or
     *  - the same client paid the same amount in the last few minutes
     *    (typically the same receipt entered again after a connection drop).
     */
    private function refuseLikelyDuplicate(Client $client, array $payment, float $money): void
    {
        $norm = fn ($v) => mb_strtolower(trim((string) $v));
        $match = null;

        $refs = [
            'cheque' => ['cheque_reference', 'cheque number'],
            'transfer' => ['transfer_reference', 'transfer reference'],
            'momo' => ['momo_transactin_id', 'MoMo transaction ID'],
        ];
        foreach ($refs as $mode => [$field, $label]) {
            $ref = $norm($payment[$field] ?? '');
            if ($ref === '') {
                continue;
            }
            $q = Receipt::whereRaw('LOWER(TRIM(' . $field . ')) = ?', [$ref]);
            if ($mode === 'cheque' && $norm($payment['cheque_bank'] ?? '') !== '') {
                $q->whereRaw('LOWER(TRIM(cheque_bank)) = ?', [$norm($payment['cheque_bank'])]);
            }
            if ($existing = $q->orderByDesc('id')->first()) {
                $match = [$existing, 'the same ' . $label . ' (' . trim((string) $payment[$field]) . ')'];
                break;
            }
        }

        if (! $match && $money > 0) {
            $recent = Receipt::where('client_id', $client->id)
                ->where('created_at', '>=', now()->subMinutes(self::REPEAT_WINDOW_MINUTES))
                ->orderByDesc('id')->get()
                ->first(fn ($r) => abs(array_sum(array_map(fn ($f) => (float) $r->{$f}, self::AMOUNT_FIELDS)) - $money) < 0.005);
            if ($recent) {
                $match = [$recent, 'the same amount (GH₵' . number_format($money, 2) . ') for this client a few minutes ago'];
            }
        }

        if ($match) {
            [$existing, $why] = $match;
            throw ValidationException::withMessages(['duplicate' =>
                'This looks like receipt FWSSR' . $existing->id . ', saved ' . $existing->created_at?->format('d M Y H:i')
                . ' with ' . $why . '. If it is a separate payment, tick "Yes, record it as a separate payment" and save again.',
            ]);
        }
    }

    /** @return array{0: array, 1: float} payment fields (ticked modes only) and money received */
    private function payment(array $input): array
    {
        $modes = (array) ($input['mode'] ?? []);
        $payload = [];
        foreach (self::MODE_FIELDS as $mode => $fields) {
            $on = in_array($mode, $modes, true);
            foreach ($fields as $field) {
                $value = $on ? ($input[$field] ?? null) : null;
                if ($value === '') {
                    $value = null;
                }
                if ($value !== null && in_array($field, self::AMOUNT_FIELDS, true)) {
                    $value = round((float) $value, 2);
                }
                $payload[$field] = $value;
            }
        }

        $money = round(array_sum(array_map(fn ($f) => (float) ($payload[$f] ?? 0), self::AMOUNT_FIELDS)), 2);

        return [$payload, $money];
    }

    private function lockOpenInvoices(int $clientId, array $ids)
    {
        $ids = array_map('intval', $ids);
        if (! $ids) {
            return collect();
        }

        $invoices = Invoice::where('client_id', $clientId)
            ->whereIn('id', $ids)
            ->whereIn('status', ['unpaid', 'uncompleted'])
            ->withSum('allocations as settled_sum', 'settled')
            ->orderBy('invoice_month')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($invoices->count() !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['invoices' => 'One or more of the invoices is already fully paid or does not belong to this client. Reload the page and try again.']);
        }

        return $invoices;
    }

    /** Money from this receipt already given to other invoices with "Apply credit". */
    public function creditUsed(Receipt $receipt): float
    {
        return round((float) \App\Models\ReceiptAllocation::where('receipt_id', $receipt->id)->where('source', 'credit')->sum('amount_applied'), 2);
    }

    private function plan($invoices, array $input, float $money, float $creditUsed = 0.0): array
    {
        $lines = $invoices->map(function (Invoice $invoice) use ($input) {
            $row = $input['invoices'][$invoice->id] ?? $input['invoices'][(string) $invoice->id] ?? [];

            return [
                'invoice_id' => $invoice->id,
                'outstanding' => max(0, round((float) $invoice->total - (float) $invoice->settled_sum, 2)),
                'applied' => $row['applied'] ?? null,
                'wht' => $row['wht'] ?? 0,
                'vat7' => $row['vat7'] ?? 0,
                'deduction' => $row['deduction'] ?? 0,
            ];
        })->values()->all();

        $plan = $this->allocator->plan($lines, $money);
        $credits = array_sum(array_column($plan['lines'], 'wht_amount'))
            + array_sum(array_column($plan['lines'], 'vat7_amount'))
            + array_sum(array_column($plan['lines'], 'deduction_amount'));

        $errors = $plan['errors'];

        if ($money <= 0 && $credits <= 0 && $creditUsed <= 0) {
            $errors[] = 'Enter the amount received for the payment mode(s) you ticked.';
        }
        foreach ($plan['lines'] as $line) {
            if ($line['settled'] <= 0) {
                $errors[] = 'Nothing is being paid on invoice FWSSi' . $line['invoice_id'] . ' — enter an amount or untick it.';
            }
        }
        if ($plan['unapplied'] > 0 && empty($input['keep_credit'])) {
            $errors[] = $invoices->isEmpty() && $creditUsed <= 0
                ? 'Tick at least one invoice, or tick "Keep the extra as client credit (advance)" to record an advance payment.'
                : 'GH₵' . number_format($plan['unapplied'], 2) . ' is more than the invoice(s) need. '
                    . 'Check the amount, or tick "Keep the extra as client credit (advance)".';
        }

        if ($errors) {
            throw ValidationException::withMessages(['invoices' => array_values(array_unique($errors))]);
        }

        return $plan;
    }

    /** Set every computed receipt field. Same meanings the reports already use. */
    private function fill(Receipt $receipt, array $input, array $payment, float $money, array $plan, $invoices): void
    {
        $lines = collect($plan['lines']);
        $wht = round($lines->sum('wht_amount'), 2);
        $vat = round($lines->sum('vat7_amount'), 2);
        $ded = round($lines->sum('deduction_amount'), 2);

        $receipt->fill($payment);
        $receipt->mode = (array) $input['mode'];
        $receipt->invoice_id = $invoices->first()?->id;     // first invoice paid, for older screens and reports
        $receipt->from = $input['from'];
        $receipt->receipt_month = $input['receipt_month'];
        $receipt->unapplied_amount = $plan['unapplied'];
        $receipt->dAmount = $ded > 0 ? $ded : null;
        $receipt->description = $ded > 0 ? ($input['description'] ?? null) : null;
        $receipt->wht_amount = $wht > 0 ? $wht : null;
        // Legacy meaning kept for the dashboards: "after WHT" is only filled when WHT applies.
        $receipt->amount_received = $wht > 0 ? $money : null;
        $receipt->vat7_value = $vat > 0 ? $vat : null;
        $receipt->vat7_amount = $vat > 0 ? round(($wht > 0 ? $money : 0) - $vat, 2) : null;
        // money + WHT + VAT; deductions kept apart in dAmount (as before).
        $receipt->total = round($money + $wht + $vat, 2);
        $receipt->status = $lines->isNotEmpty() && $lines->every(fn ($l) => $l['completes']) ? 'completed' : 'uncompleted';
        $receipt->advance_payment = $this->isAdvance($receipt, $plan, $invoices) ? 'advance' : null;
    }

    /**
     * "Advance" is no longer a box the cashier ticks. A receipt is an advance when
     *  - part of the money is kept as client credit for future invoices, or
     *  - it is dated before the month of an invoice it pays (paid ahead of time).
     */
    private function isAdvance(Receipt $receipt, array $plan, $invoices): bool
    {
        if ($plan['unapplied'] > 0) {
            return true;
        }
        if (! $receipt->receipt_month) {
            return false;
        }
        $paidMonth = \Carbon\Carbon::parse($receipt->receipt_month)->startOfMonth();

        return $invoices->contains(fn ($inv) => $inv->invoice_month && $paidMonth->lt(\Carbon\Carbon::parse($inv->invoice_month)->startOfMonth()));
    }

    private function allocate(Receipt $receipt, $invoices, array $plan, bool $revising): void
    {
        $lines = collect($plan['lines'])->keyBy('invoice_id');
        foreach ($invoices as $invoice) {
            $this->settlement->allocate($receipt, $invoice, $lines[$invoice->id]);
            $assigned = $this->workflow->assignCategory($receipt, $invoice, $revising);
            if (! $assigned && ! $revising) {
                $this->notes[] = 'Client already has a category for ' . optional($invoice->invoice_month)->format('F Y') . '.';
            }
        }
    }

    private function writeCollection(Receipt $receipt, ?Client $client): void
    {
        $collection = Collection::firstOrNew(['receipt_id' => $receipt->id]);
        if (! $collection->exists) {
            $collection->user_id = Auth::id();
            $collection->status = 'undeposited';
        }
        $collection->field_id = $client?->field_id;
        $collection->cash_amount = $receipt->cash_amount;
        $collection->momo_amount = $receipt->momo_amount;
        $collection->cheque_amount = $receipt->cheque_amount;
        $collection->transfer_amount = $receipt->transfer_amount;
        $collection->total_amount = $receipt->total;
        $collection->save();
    }
}
