<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMultiInvoiceReceiptRequest;
use App\Models\Bank;
use App\Models\Client;
use App\Models\Collection;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\User;
use App\Models\Wht;
use App\Services\Receipts\BankPosting;
use App\Services\Receipts\InvoiceSettlement;
use App\Services\Receipts\ReceiptAllocator;
use App\Services\Receipts\ReceiptWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * One receipt, many invoices.
 *
 * The cashier picks a client, ticks the invoices the payment covers, enters
 * the money received once (cash / momo / cheque / transfer / other), and the
 * amount is spread over the ticked invoices oldest first (or as typed).
 * Anything left over stays on the receipt as client credit and can be
 * applied to later invoices from the receipt page.
 */
class MultiInvoiceReceiptController extends Controller
{
    /** Payment fields that belong to each mode. Unticked modes are always stored as null. */
    private const MODE_FIELDS = [
        'cheque' => ['cheque_reference', 'cheque_amount', 'cheque_bank', 'cheque_to_bank_id'],
        'transfer' => ['transfer_reference', 'transfer_amount', 'transfer_bank', 'transfer_to_bank_id'],
        'momo' => ['momo_transactin_id', 'momo_amount'],
        'other payments' => ['other_payment_descri', 'other_payment_amnt'],
        'cash' => ['cash_amount'],
    ];

    private const AMOUNT_FIELDS = ['cheque_amount', 'transfer_amount', 'momo_amount', 'other_payment_amnt', 'cash_amount'];

    public function __construct(
        private ReceiptAllocator $allocator,
        private InvoiceSettlement $settlement,
        private BankPosting $bankPosting,
        private ReceiptWorkflow $workflow,
    ) {
        $this->middleware('auth');
    }

    public function create(Request $request)
    {
        $clients = $this->scopeToUserFields(Client::query())
            ->whereHas('invoices', fn ($q) => $q->whereIn('status', ['unpaid', 'uncompleted']))
            ->with('field')
            ->orderBy('business_name')
            ->get();

        $client = null;
        $invoices = collect();
        $creditReceipts = collect();

        if ($request->filled('client_id')) {
            $client = $this->scopeToUserFields(Client::query())->with('field')->findOrFail($request->integer('client_id'));
            $invoices = $this->openInvoices($client->id)->get();
            $creditReceipts = Receipt::where('client_id', $client->id)
                ->where('unapplied_amount', '>', 0)
                ->orderBy('id')
                ->get();
        }

        $mode = DB::table('receipt_mode')->get();
        $banks = Bank::orderBy('name')->get();
        $wht_rate = new Wht();
        $assign_staff = $this->assignableStaff();

        return view('sales.receipt_multi_create', compact(
            'clients', 'client', 'invoices', 'creditReceipts', 'mode', 'banks', 'wht_rate', 'assign_staff'
        ));
    }

    public function store(StoreMultiInvoiceReceiptRequest $request)
    {
        $client = $this->scopeToUserFields(Client::query())->with('field')->findOrFail($request->integer('client_id'));
        $selectedModes = (array) $request->input('mode', []);
        $payment = $this->paymentPayload($request, $selectedModes);
        $moneyReceived = round(array_sum(array_map(fn ($f) => (float) ($payment[$f] ?? 0), self::AMOUNT_FIELDS)), 2);

        if ($moneyReceived <= 0) {
            throw ValidationException::withMessages(['mode' => 'Enter the amount received for the payment mode(s) you ticked.']);
        }

        $receipt = DB::transaction(function () use ($request, $client, $payment, $moneyReceived) {
            $invoiceInput = collect((array) $request->input('invoices', []))
                ->filter(fn ($row) => ($row['selected'] ?? null) === '1');

            // Lock the ticked invoices so two cashiers can't settle the same balance twice.
            $invoices = $this->openInvoices($client->id)
                ->whereIn('id', $invoiceInput->keys()->map(fn ($k) => (int) $k)->all())
                ->lockForUpdate()
                ->get();

            if ($invoiceInput->isNotEmpty() && $invoices->count() !== $invoiceInput->count()) {
                throw ValidationException::withMessages(['invoices' => 'One or more ticked invoices are already fully paid or do not belong to this client. Reload the page and try again.']);
            }

            $plan = $this->allocator->plan($this->planLines($invoices, $invoiceInput), $moneyReceived);
            $this->guardPlan($plan, $request->input('keep_credit') === '1', $invoices->isEmpty());

            $lines = collect($plan['lines'])->keyBy('invoice_id');
            $totals = [
                'wht' => round($lines->sum('wht_amount'), 2),
                'vat' => round($lines->sum('vat7_amount'), 2),
                'ded' => round($lines->sum('deduction_amount'), 2),
            ];

            $receipt = new Receipt();
            $receipt->fill($payment);
            $receipt->mode = (array) $request->input('mode');
            $receipt->client_id = $client->id;
            $receipt->invoice_id = $invoices->first()?->id;   // primary invoice, for older screens and reports
            $receipt->from = $request->input('from');
            $receipt->receipt_month = $request->input('receipt_month');
            $receipt->advance_payment = $plan['unapplied'] > 0 ? 'advance' : null;
            $receipt->unapplied_amount = $plan['unapplied'];
            $receipt->dAmount = $totals['ded'] > 0 ? $totals['ded'] : null;
            $receipt->description = $totals['ded'] > 0 ? $request->input('description') : null;
            $receipt->wht_amount = $totals['wht'] > 0 ? $totals['wht'] : null;
            $receipt->amount_received = $totals['wht'] > 0 ? $moneyReceived : null;
            $receipt->vat7_value = $totals['vat'] > 0 ? $totals['vat'] : null;
            $receipt->vat7_amount = $totals['vat'] > 0 ? round(($receipt->amount_received ?? 0) - $totals['vat'], 2) : null;
            // Same meaning as the single-invoice receipt: money + WHT + VAT (deductions kept in dAmount).
            $receipt->total = round($moneyReceived + $totals['wht'] + $totals['vat'], 2);
            $receipt->status = $lines->every(fn ($l) => $l['completes']) ? 'completed' : 'uncompleted';
            $receipt->user_id = Auth::id();
            $receipt->image = $request->file('image')?->store('images', 'public_html_disk');
            $this->workflow->applyApproval($receipt, $request->input('staff'));
            $receipt->save();

            foreach ($invoices as $invoice) {
                $this->settlement->allocate($receipt, $invoice, $lines[$invoice->id]);
                $this->workflow->assignCategory($receipt, $invoice);
            }

            Collection::create([
                'user_id' => Auth::id(),
                'receipt_id' => $receipt->id,
                'cash_amount' => $receipt->cash_amount,
                'momo_amount' => $receipt->momo_amount,
                'cheque_amount' => $receipt->cheque_amount,
                'transfer_amount' => $receipt->transfer_amount,
                'total_amount' => $receipt->total,
                'field_id' => $client->field?->id,
            ]);

            $this->bankPosting->syncTransfer($receipt);

            return $receipt;
        });

        $count = $receipt->allocations()->count();
        $message = 'Receipt FWSSR' . $receipt->id . ' created for ' . $count . ' invoice' . ($count === 1 ? '' : 's') . '.';
        if ($receipt->hasUnappliedCredit()) {
            $message .= ' GH₵' . number_format($receipt->unapplied_amount, 2) . ' kept as client credit.';
        }

        return redirect()->route('receipt.show', ['receipt' => $receipt->id])->with('primary', $message);
    }

    /**
     * Apply a receipt's unapplied credit (advance / overpayment) to the
     * client's open invoices. No new money moves, so collections and bank
     * balances are untouched.
     */
    public function applyCredit(Request $request, Receipt $receipt)
    {
        $request->validate([
            'invoices' => ['required', 'array'],
            'invoices.*.selected' => ['nullable', 'in:1'],
            'invoices.*.applied' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Only from a client this user may work with.
        $this->scopeToUserFields(Client::query())->findOrFail($receipt->client_id);

        DB::transaction(function () use ($request, $receipt) {
            $receipt = Receipt::whereKey($receipt->id)->lockForUpdate()->firstOrFail();
            $credit = round((float) $receipt->unapplied_amount, 2);

            if ($credit <= 0) {
                throw ValidationException::withMessages(['invoices' => 'This receipt has no credit left to apply.']);
            }

            $invoiceInput = collect((array) $request->input('invoices', []))
                ->filter(fn ($row) => ($row['selected'] ?? null) === '1')
                ->map(fn ($row) => ['applied' => $row['applied'] ?? null]);

            $invoices = $this->openInvoices($receipt->client_id)
                ->whereIn('id', $invoiceInput->keys()->map(fn ($k) => (int) $k)->all())
                ->lockForUpdate()
                ->get();

            if ($invoices->isEmpty()) {
                throw ValidationException::withMessages(['invoices' => 'Tick at least one open invoice to apply the credit to.']);
            }

            $plan = $this->allocator->plan($this->planLines($invoices, $invoiceInput), $credit);
            $this->guardPlan($plan, true, false);

            foreach (collect($plan['lines'])->keyBy('invoice_id') as $invoiceId => $line) {
                $invoice = $invoices->firstWhere('id', $invoiceId);
                $this->settlement->allocate($receipt, $invoice, $line, 'credit');
                $this->workflow->assignCategory($receipt, $invoice);
            }

            $receipt->unapplied_amount = $plan['unapplied'];
            $receipt->invoice_id = $receipt->invoice_id ?: $invoices->first()->id;
            $allDone = Invoice::whereIn('id', $receipt->allocations()->pluck('invoice_id'))
                ->where('status', '!=', 'completed')
                ->doesntExist();
            $receipt->status = $allDone ? 'completed' : 'uncompleted';
            $receipt->save();
        });

        return redirect()->route('receipt.show', ['receipt' => $receipt->id])
            ->with('primary', 'Credit applied. Remaining credit on this receipt: GH₵' . number_format($receipt->fresh()->unapplied_amount, 2));
    }

    /** Invoices still owing money, oldest first, with what has already been settled. */
    private function openInvoices(int $clientId)
    {
        return Invoice::where('client_id', $clientId)
            ->whereIn('status', ['unpaid', 'uncompleted'])
            ->withSum('allocations as settled_sum', 'settled')
            ->orderBy('invoice_month')
            ->orderBy('id');
    }

    private function planLines($invoices, $invoiceInput): array
    {
        return $invoices->map(function (Invoice $invoice) use ($invoiceInput) {
            $row = $invoiceInput[$invoice->id] ?? $invoiceInput[(string) $invoice->id] ?? [];

            return [
                'invoice_id' => $invoice->id,
                'outstanding' => max(0, round((float) $invoice->total - (float) $invoice->settled_sum, 2)),
                'applied' => $row['applied'] ?? null,
                'wht' => $row['wht'] ?? 0,
                'vat7' => $row['vat7'] ?? 0,
                'deduction' => $row['deduction'] ?? 0,
            ];
        })->values()->all();
    }

    private function guardPlan(array $plan, bool $keepCredit, bool $noInvoices): void
    {
        $errors = $plan['errors'];

        foreach ($plan['lines'] as $line) {
            if ($line['settled'] <= 0) {
                $errors[] = 'Nothing is being paid on invoice FWSSi' . $line['invoice_id'] . ' — enter an amount or untick it.';
            }
        }

        if ($plan['unapplied'] > 0 && ! $keepCredit) {
            $errors[] = 'GH₵' . number_format($plan['unapplied'], 2) . ' of the money is not applied to any invoice. '
                . 'Tick "Keep the extra as client credit (advance)" or tick more invoices.';
        }

        if ($noInvoices && ! $keepCredit) {
            $errors[] = 'Tick at least one invoice, or tick "Keep the extra as client credit (advance)" to record an advance payment.';
        }

        if ($errors) {
            throw ValidationException::withMessages(['invoices' => array_values(array_unique($errors))]);
        }
    }

    /** Payment fields of ticked modes only; everything else is null. */
    private function paymentPayload(Request $request, array $selectedModes): array
    {
        $payload = [];
        foreach (self::MODE_FIELDS as $mode => $fields) {
            $on = in_array($mode, $selectedModes, true);
            foreach ($fields as $field) {
                $value = $on ? $request->input($field) : null;
                $payload[$field] = in_array($field, self::AMOUNT_FIELDS, true) && $value !== null
                    ? round((float) $value, 2)
                    : ($value === '' ? null : $value);
            }
        }

        return $payload;
    }

    /** Same office rules as the receipt list: head office sees all, Tema also sees Shai Hills. */
    private function scopeToUserFields($query)
    {
        $user = Auth::user();
        if ($user?->hasRole(['Finance Manager', 'Invoice'])) {
            return $query;
        }

        $fieldIds = $user?->field_id == 3 ? [3, 7] : array_filter([$user?->field_id]);

        return $fieldIds ? $query->whereIn('field_id', $fieldIds) : $query->whereRaw('1 = 0');
    }

    /** Same "assign to" list as the single-invoice receipt form. */
    private function assignableStaff()
    {
        $user = Auth::user();
        $staff = User::all();

        if ($user?->role?->name == 'Admin Assistant') {
            return $staff->where('department_id', '7')->where('role_id', '3')
                ->filter(fn ($u) => $u->field_id == $user->field_id);
        }
        if ($user?->role?->name == 'Manager') {
            return $staff->where('department_id', '1')->where('role_id', '2');
        }

        return collect();
    }
}
