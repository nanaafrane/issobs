<?php

namespace App\Services\Receipts;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Receipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Uses a client's unused credit (receipts.unapplied_amount) to pay invoices.
 * No money moves — collections and banks are untouched; only allocation lines
 * (source "credit") are added and the invoices re-derived.
 *
 *  apply()      the "Apply credit" form on a receipt: chosen invoices, optional amounts
 *  autoApply()  a new invoice for a client with auto_apply_credit on: oldest credit first
 */
class CreditApplier
{
    public function __construct(
        private ReceiptAllocator $allocator,
        private InvoiceSettlement $settlement,
        private ReceiptWorkflow $workflow,
        private ClientLedger $ledger,
    ) {
    }

    /**
     * @param  array<int, float|string|null>  $invoices  invoice_id => amount (null = as much as possible)
     */
    public function apply(Receipt $receipt, array $invoices): Receipt
    {
        return DB::transaction(function () use ($receipt, $invoices) {
            $receipt = Receipt::whereKey($receipt->id)->lockForUpdate()->firstOrFail();
            $credit = round((float) $receipt->unapplied_amount, 2);
            if ($credit <= 0) {
                throw ValidationException::withMessages(['invoices' => 'This receipt has no credit left to apply.']);
            }

            $open = $this->openInvoices($receipt->client_id, array_keys($invoices));
            if ($open->isEmpty()) {
                throw ValidationException::withMessages(['invoices' => 'Tick at least one open invoice to apply the credit to.']);
            }

            $plan = $this->allocator->plan($open->map(fn (Invoice $inv) => [
                'invoice_id' => $inv->id,
                'outstanding' => max(0, round((float) $inv->total - (float) $inv->settled_sum, 2)),
                'applied' => ($invoices[$inv->id] ?? null) === '' ? null : ($invoices[$inv->id] ?? null),
            ])->values()->all(), $credit);

            $errors = $plan['errors'];
            foreach ($plan['lines'] as $line) {
                if ($line['settled'] <= 0) {
                    $errors[] = 'Nothing is being applied to invoice FWSSi' . $line['invoice_id'] . ' — enter an amount or untick it.';
                }
            }
            if ($errors) {
                throw ValidationException::withMessages(['invoices' => array_values(array_unique($errors))]);
            }

            $this->allocate($receipt, $open, $plan);
            $this->ledger->sync($receipt->client_id);

            return $receipt;
        });
    }

    /**
     * Pay a newly raised invoice from the client's unused credit, oldest credit
     * first, if the client has automatic credit switched on.
     *
     * @return float amount applied
     */
    public function autoApply(Invoice $invoice): float
    {
        $client = Client::find($invoice->client_id);
        if (! $client || ! $client->auto_apply_credit) {
            return 0.0;
        }

        return DB::transaction(function () use ($invoice) {
            $applied = 0.0;
            $receipts = Receipt::where('client_id', $invoice->client_id)
                ->where('unapplied_amount', '>', 0)
                ->orderBy('receipt_month')->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($receipts as $receipt) {
                $open = $this->openInvoices($invoice->client_id, [$invoice->id]);
                if ($open->isEmpty()) {
                    break;   // invoice fully paid
                }
                $plan = $this->allocator->plan([[
                    'invoice_id' => $invoice->id,
                    'outstanding' => max(0, round((float) $open->first()->total - (float) $open->first()->settled_sum, 2)),
                    'applied' => null,
                ]], round((float) $receipt->unapplied_amount, 2));

                if ($plan['errors'] || $plan['lines'][0]['settled'] <= 0) {
                    continue;
                }
                $this->allocate($receipt, $open, $plan);
                $applied += $plan['lines'][0]['amount_applied'];
            }

            if ($applied > 0) {
                $this->ledger->sync($invoice->client_id);
            }

            return round($applied, 2);
        });
    }

    private function allocate(Receipt $receipt, $open, array $plan): void
    {
        foreach (collect($plan['lines'])->keyBy('invoice_id') as $invoiceId => $line) {
            $invoice = $open->firstWhere('id', $invoiceId);
            $this->settlement->allocate($receipt, $invoice, $line, 'credit');
            $this->workflow->assignCategory($receipt, $invoice);
        }

        $receipt->unapplied_amount = $plan['unapplied'];
        $receipt->invoice_id = $receipt->invoice_id ?: $open->first()->id;
        $receipt->status = Invoice::whereIn('id', $receipt->allocations()->pluck('invoice_id'))
            ->where('status', '!=', 'completed')->doesntExist() ? 'completed' : 'uncompleted';
        $receipt->save();
    }

    private function openInvoices(int $clientId, array $ids)
    {
        return Invoice::where('client_id', $clientId)
            ->whereIn('id', array_map('intval', $ids))
            ->whereIn('status', ['unpaid', 'uncompleted'])
            ->withSum('allocations as settled_sum', 'settled')
            ->orderBy('invoice_month')->orderBy('id')
            ->lockForUpdate()
            ->get();
    }
}
