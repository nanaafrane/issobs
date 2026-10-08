<?php

namespace App\Services\Receipts;

use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\ReceiptAllocation;
use Illuminate\Support\Facades\DB;

/**
 * Re-derives every invoice's status and balance from the receipts on it, to
 * repair what the old receipt code got wrong (edits that subtracted twice,
 * "completed" chosen while money was still owed, negative balances from
 * overpayments, WHT overwritten on edit, ...).
 *
 * Read-only unless $apply is true. Each invoice is put in one bucket:
 *
 *   ok            stored status/balance already match the receipts
 *   fix           wrong status or balance — corrected on apply
 *   closed_short  marked completed but receipts don't cover it. Could be a
 *                 deliberate write-off/discount, so it is only reopened
 *                 with $reopenShort
 *   overpaid      receipts exceed the invoice. With $overpaymentsToCredit the
 *                 extra cash is moved off the invoice into the latest
 *                 receipt's client credit (unapplied_amount)
 *
 * Receipts that never got an allocation line are given one first (same rule
 * as the backfill migration).
 */
class BalanceRecompute
{
    public const TOL = 0.01;

    public function __construct(private InvoiceSettlement $settlement)
    {
    }

    /**
     * @return array{rows: array<int, array>, summary: array<string, int>, missing_allocations: int}
     */
    public function run(bool $apply = false, bool $reopenShort = false, bool $overpaymentsToCredit = false, ?int $clientId = null): array
    {
        $missing = $this->backfillMissing($apply, $clientId);

        $rows = [];
        $summary = ['ok' => 0, 'fix' => 0, 'closed_short' => 0, 'overpaid' => 0];

        // Paid per invoice = its allocation lines, plus (dry run only) receipts that do not
        // have a line yet, counted the way the backfill will count them: total + deductions.
        $lines = DB::table('receipt_allocations')->select('invoice_id', 'settled')
            ->unionAll(
                DB::table('receipts')->whereNotNull('invoice_id')
                    ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('receipt_allocations')->whereColumn('receipt_allocations.receipt_id', 'receipts.id'))
                    ->selectRaw('invoice_id, COALESCE(total, 0) + COALESCE(' . DB::getQueryGrammar()->wrap('receipts.dAmount') . ', 0) as settled')
            );
        $settled = DB::query()->fromSub($lines, 'l')->selectRaw('invoice_id, SUM(settled) as settled')->groupBy('invoice_id');

        Invoice::query()
            ->leftJoinSub($settled, 's', 's.invoice_id', '=', 'invoices.id')
            ->leftJoin('clients', 'clients.id', '=', 'invoices.client_id')
            ->when($clientId, fn ($q) => $q->where('invoices.client_id', $clientId))
            ->select('invoices.*', DB::raw('COALESCE(s.settled, 0) as settled_sum'), 'clients.business_name', 'clients.name as client_name')
            ->orderBy('invoices.id')
            ->chunk(500, function ($invoices) use (&$rows, &$summary, $apply, $reopenShort, $overpaymentsToCredit) {
                foreach ($invoices as $invoice) {
                    $row = $this->check($invoice);
                    $summary[$row['issue']]++;
                    if ($row['issue'] === 'ok') {
                        continue;
                    }
                    $row['action'] = $apply ? $this->repair($invoice, $row, $reopenShort, $overpaymentsToCredit) : 'none (dry run)';
                    $rows[] = $row;
                }
            });

        return ['rows' => $rows, 'summary' => $summary, 'missing_allocations' => $missing];
    }

    /** What the invoice should look like according to its receipts. */
    private function check(Invoice $invoice): array
    {
        $total = round((float) $invoice->total, 2);
        $settled = round((float) $invoice->settled_sum, 2);
        $storedStatus = strtolower(trim((string) $invoice->status));
        $storedBalance = round((float) $invoice->balance, 2);

        if ($settled <= self::TOL) {
            [$status, $balance] = ['unpaid', 0.0];
        } elseif ($total - $settled > self::TOL) {
            [$status, $balance] = ['uncompleted', round($total - $settled, 2)];
        } else {
            $diff = round($total - $settled, 2);
            [$status, $balance] = ['completed', abs($diff) <= self::TOL ? 0.0 : $diff];
        }

        $issue = 'ok';
        if ($settled - $total > self::TOL) {
            $issue = 'overpaid';
        } elseif ($storedStatus === 'completed' && $status !== 'completed') {
            $issue = 'closed_short';
        } elseif ($storedStatus !== $status || abs($storedBalance - $balance) > self::TOL) {
            $issue = 'fix';
        }

        return [
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'client' => $invoice->business_name ?: $invoice->client_name,
            'invoice_month' => optional($invoice->invoice_month)->format('Y-m'),
            'total' => $total,
            'paid_per_receipts' => $settled,
            'stored_status' => $storedStatus,
            'stored_balance' => $storedBalance,
            'correct_status' => $status,
            'correct_balance' => $balance,
            'issue' => $issue,
            'action' => '',
        ];
    }

    private function repair(Invoice $invoice, array $row, bool $reopenShort, bool $overpaymentsToCredit): string
    {
        return DB::transaction(function () use ($invoice, $row, $reopenShort, $overpaymentsToCredit) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->first();

            if ($row['issue'] === 'closed_short' && ! $reopenShort) {
                return 'left closed (review: GH₵' . number_format($row['total'] - $row['paid_per_receipts'], 2) . ' not covered)';
            }

            $note = '';
            if ($row['issue'] === 'overpaid') {
                if (! $overpaymentsToCredit) {
                    $this->settlement->recalculate($invoice);

                    return 'recalculated; overpayment of GH₵' . number_format(-$row['correct_balance'], 2) . ' left on invoice';
                }
                $left = $this->moveExcessToCredit($invoice, round($row['paid_per_receipts'] - $row['total'], 2));
                $note = $left > 0 ? '; GH₵' . number_format($left, 2) . ' of the excess is WHT/deductions — review' : '';
            }

            $this->settlement->recalculate($invoice);
            $invoice->refresh();

            return 'set to ' . $invoice->status . ' / ' . number_format((float) $invoice->balance, 2) . $note;
        });
    }

    /** Move overpaid cash from the newest receipts on the invoice into those receipts' client credit. */
    private function moveExcessToCredit(Invoice $invoice, float $excess): float
    {
        $allocations = ReceiptAllocation::where('invoice_id', $invoice->id)->orderByDesc('id')->lockForUpdate()->get();

        foreach ($allocations as $allocation) {
            if ($excess <= 0) {
                break;
            }
            $take = min((float) $allocation->amount_applied, $excess);
            if ($take <= 0) {
                continue;
            }
            $allocation->amount_applied = round($allocation->amount_applied - $take, 2);
            $allocation->settled = round($allocation->settled - $take, 2);
            $allocation->save();

            $receipt = Receipt::whereKey($allocation->receipt_id)->lockForUpdate()->first();
            $receipt->unapplied_amount = round((float) $receipt->unapplied_amount + $take, 2);
            $receipt->advance_payment = 'advance';
            $receipt->save();

            $excess = round($excess - $take, 2);
        }

        return max(0.0, $excess);
    }

    /** Receipts with an invoice but no allocation line (should be none after the migration). */
    private function backfillMissing(bool $apply, ?int $clientId): int
    {
        $query = Receipt::whereNotNull('invoice_id')
            ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('receipt_allocations')->whereColumn('receipt_allocations.receipt_id', 'receipts.id'));

        $count = (clone $query)->count();
        if (! $apply || $count === 0) {
            return $count;
        }

        $query->orderBy('id')->each(function (Receipt $r) {
            $wht = round((float) $r->wht_amount, 2);
            $vat = round((float) $r->vat7_value, 2);
            $ded = round((float) $r->dAmount, 2);
            $applied = round((float) $r->total - $wht - $vat, 2);
            ReceiptAllocation::create([
                'receipt_id' => $r->id, 'invoice_id' => $r->invoice_id, 'client_id' => $r->client_id,
                'amount_applied' => $applied, 'wht_amount' => $wht, 'vat7_amount' => $vat, 'deduction_amount' => $ded,
                'settled' => round($applied + $wht + $vat + $ded, 2), 'source' => 'legacy', 'user_id' => $r->user_id,
            ]);
        });

        return $count;
    }
}
