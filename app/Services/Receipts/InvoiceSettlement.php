<?php

namespace App\Services\Receipts;

use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\ReceiptAllocation;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

/**
 * Keeps invoices, allocation lines and the client transaction ledger in step.
 *
 * The rule is simple: an invoice's state is derived from its allocation
 * lines, never typed in by hand.
 *
 *   settled  = SUM(receipt_allocations.settled)
 *   nothing settled         -> status "unpaid",      balance 0      (legacy convention)
 *   settled < total         -> status "uncompleted", balance = total - settled
 *   settled >= total        -> status "completed",   balance = total - settled (0, or negative if overpaid)
 */
class InvoiceSettlement
{
    /**
     * Save one allocation line and its matching client-ledger transaction.
     *
     * @param  array{invoice_id:int, amount_applied:float, wht_amount:float, vat7_amount:float, deduction_amount:float, settled:float}  $line
     */
    public function allocate(Receipt $receipt, Invoice $invoice, array $line, string $source = 'receipt'): ReceiptAllocation
    {
        $allocation = ReceiptAllocation::create([
            'receipt_id' => $receipt->id,
            'invoice_id' => $invoice->id,
            'client_id' => $receipt->client_id,
            'amount_applied' => $line['amount_applied'],
            'wht_amount' => $line['wht_amount'],
            'vat7_amount' => $line['vat7_amount'],
            'deduction_amount' => $line['deduction_amount'],
            'settled' => $line['settled'],
            'source' => $source,
            'user_id' => Auth::id(),
        ]);

        $this->recalculate($invoice);
        $invoice->refresh();

        Transaction::create([
            'client_id' => $receipt->client_id,
            'invoice_id' => $invoice->id,
            'invoice_amount' => $invoice->total,
            'receipt_id' => $receipt->id,
            'receipt_amount' => $line['settled'],
            'balance' => $invoice->status === 'unpaid' ? $invoice->total : $invoice->balance,
            'status' => $invoice->status === 'completed' ? 'completed' : 'uncompleted',
            'checks' => $invoice->status === 'completed' ? 'd' : null,
        ]);

        return $allocation;
    }

    /** Re-derive status, balance and tax totals of an invoice from its allocation lines. */
    public function recalculate(Invoice $invoice): Invoice
    {
        $allocations = ReceiptAllocation::where('invoice_id', $invoice->id)->get();

        $total = round((float) $invoice->total, 2);
        $settled = round((float) $allocations->sum('settled'), 2);
        $wht = round((float) $allocations->sum('wht_amount'), 2);
        $vat = round((float) $allocations->sum('vat7_amount'), 2);
        $appliedWithWht = round((float) $allocations->where('wht_amount', '>', 0)->sum('amount_applied'), 2);

        if ($settled <= ReceiptAllocator::TOLERANCE) {
            $invoice->status = 'unpaid';
            $invoice->balance = 0;
        } elseif ($total - $settled > ReceiptAllocator::TOLERANCE) {
            $invoice->status = 'uncompleted';
            $invoice->balance = round($total - $settled, 2);
        } else {
            $invoice->status = 'completed';
            $diff = round($total - $settled, 2);
            $invoice->balance = abs($diff) <= ReceiptAllocator::TOLERANCE ? 0 : $diff;
        }

        $invoice->wht_amount = $wht > 0 ? $wht : null;
        $invoice->amount_received = $appliedWithWht > 0 ? $appliedWithWht : null;
        $invoice->vat7_value = $vat > 0 ? $vat : null;
        $invoice->vat7_amount = $vat > 0 ? round($appliedWithWht - $vat, 2) : null;
        $invoice->save();

        // "d" marks every ledger line of a fully settled invoice as done.
        Transaction::where('invoice_id', $invoice->id)
            ->update(['checks' => $invoice->status === 'completed' ? 'd' : '']);

        return $invoice;
    }

    /**
     * Undo everything a receipt did to its invoices: remove its allocation
     * lines and ledger rows, then re-derive each touched invoice. Other
     * receipts on the same invoices are left intact.
     *
     * @return array<int, int> ids of invoices that were recalculated
     */
    public function release(Receipt $receipt): array
    {
        $invoiceIds = ReceiptAllocation::where('receipt_id', $receipt->id)->pluck('invoice_id')->all();

        // Receipts created before allocations existed and never backfilled.
        if (empty($invoiceIds) && $receipt->invoice_id) {
            $invoiceIds = [$receipt->invoice_id];
        }

        ReceiptAllocation::where('receipt_id', $receipt->id)->delete();
        Transaction::where('receipt_id', $receipt->id)->delete();

        $invoiceIds = array_values(array_unique($invoiceIds));
        foreach (Invoice::whereIn('id', $invoiceIds)->get() as $invoice) {
            $this->recalculate($invoice);
        }

        return $invoiceIds;
    }
}
