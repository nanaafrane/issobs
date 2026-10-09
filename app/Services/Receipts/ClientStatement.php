<?php

namespace App\Services\Receipts;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\ReceiptAllocation;
use Carbon\Carbon;
use Illuminate\Support\Collection as SupportCollection;

/**
 * A client's statement of account, built from the source documents.
 *
 *   Debit   each invoice, for its total
 *   Credit  each receipt ONCE, for the money received (cash + momo + cheque + transfer + other)
 *   Credit  WHT, 7% VAT withheld and other deductions on each receipt, as their own lines
 *   Note    each time unused credit is applied to an invoice — no amount, because
 *           the money was already credited when it was received; applying it only
 *           moves it between invoices and does not change what the client owes.
 *
 * Running balance: positive = client owes us, negative = client is in credit (shown "CR").
 *
 * The old statement read the transactions table and credited the WHOLE receipt
 * on every row for that receipt, so a receipt paying two invoices, or credit
 * applied later, was counted again (and VAT withheld was never credited).
 *
 * Check: closing balance (all dates) must equal
 *     SUM(invoice total − allocations settled) − SUM(unapplied credit)
 * Any difference points at old data that `php artisan receipts:recompute` repairs.
 */
class ClientStatement
{
    public const MONEY_FIELDS = ['cash_amount', 'momo_amount', 'cheque_amount', 'transfer_amount', 'other_payment_amnt'];

    public function build(Client $client, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from?->copy()->startOfDay();
        $to = $to?->copy()->endOfDay();

        $all = $this->entries($client)->sortBy(fn ($e) => [$e['date']->format('Y-m-d'), $e['order'], $e['seq']])->values();

        // Opening balance = everything before the period.
        $opening = 0.0;
        $lines = [];
        $running = 0.0;
        foreach ($all as $e) {
            $running = round($running + $e['debit'] - $e['credit'], 2);
            if ($from && $e['date']->lt($from)) {
                $opening = $running;
                continue;
            }
            if ($to && $e['date']->gt($to)) {
                continue;
            }
            $e['balance'] = $running;
            $lines[] = $e;
        }

        $period = collect($lines);
        $closing = $period->isNotEmpty() ? $period->last()['balance'] : $opening;

        return [
            'client' => $client,
            'from' => $from,
            'to' => $to,
            'opening' => $opening,
            'lines' => $lines,
            'invoiced' => round($period->sum('debit'), 2),
            'received' => round($period->where('kind', 'receipt')->sum('credit'), 2),
            'withheld' => round($period->whereIn('kind', ['wht', 'vat', 'deduction'])->sum('credit'), 2),
            'closing' => $closing,
            'open_invoices' => $this->openInvoices($client),
            'credit_receipts' => $this->creditReceipts($client),
            'check' => $this->check($client, $all),
        ];
    }

    /** Small summary for the client page. */
    public function summary(Client $client): array
    {
        $s = $this->build($client);

        return [
            'invoiced' => $s['invoiced'],
            'received' => $s['received'],
            'withheld' => $s['withheld'],
            'balance' => $s['closing'],
            'owed_on_invoices' => round($s['open_invoices']->sum('outstanding'), 2),
            'credit' => round($s['credit_receipts']->sum('unapplied_amount'), 2),
            'check' => $s['check'],
        ];
    }

    /** "1,250.00" when owed, "300.00 CR" when the client is in credit. */
    public static function money(float $balance): string
    {
        return number_format(abs($balance), 2) . ($balance < -0.004 ? ' CR' : '');
    }

    /* ------------------------------------------------------------------ */

    private function entries(Client $client): SupportCollection
    {
        $entries = collect();
        $seq = 0;

        foreach (Invoice::where('client_id', $client->id)->orderBy('id')->get() as $inv) {
            $entries->push($this->entry(
                $inv->invoice_month ?? $inv->created_at, 0, ++$seq, 'invoice',
                'Invoice FWSSi' . $inv->id . ($inv->invoice_month ? ' — ' . $inv->invoice_month->format('F Y') : ''),
                null, (float) $inv->total, 0, url('invoice/' . $inv->id)
            ));
        }

        $receipts = Receipt::where('client_id', $client->id)->with('allocations')->orderBy('id')->get();
        foreach ($receipts as $r) {
            $date = $r->receipt_month ?? $r->created_at;
            $money = round(array_sum(array_map(fn ($f) => (float) $r->{$f}, self::MONEY_FIELDS)), 2);
            $paid = $r->allocations->where('source', '!=', 'credit')
                ->map(fn ($a) => 'FWSSi' . $a->invoice_id . ' ' . number_format($a->amount_applied, 2))->implode(', ');
            $kept = (float) $r->unapplied_amount + (float) $r->allocations->where('source', 'credit')->sum('amount_applied');
            $notes = array_filter([
                $this->how($r),
                $paid ? 'Paid: ' . $paid : null,
                $kept > 0.004 ? 'Kept as credit: ' . number_format($kept, 2) : null,
                $r->ho_status !== 'approved' ? 'Awaiting approval' : null,
            ]);

            if ($money > 0) {
                $entries->push($this->entry($date, 1, ++$seq, 'receipt', 'Receipt FWSSR' . $r->id, implode(' · ', $notes), 0, $money, url('receipt/' . $r->id)));
            }
            foreach ([
                'wht' => [(float) $r->wht_amount, 'Withholding tax (WHT) on FWSSR' . $r->id],
                'vat' => [(float) $r->vat7_value, '7% VAT withheld on FWSSR' . $r->id],
                'deduction' => [(float) $r->dAmount, 'Deduction on FWSSR' . $r->id . ($r->description ? ' — ' . $r->description : '')],
            ] as $kind => [$amount, $label]) {
                if ($amount > 0.004) {
                    $entries->push($this->entry($date, 2, ++$seq, $kind, $label, $money > 0 ? null : implode(' · ', $notes), 0, round($amount, 2), url('receipt/' . $r->id)));
                }
            }
        }

        // Credit applied to later invoices: information only, no amount.
        $applied = ReceiptAllocation::where('client_id', $client->id)->where('source', 'credit')->orderBy('id')->get();
        foreach ($applied as $a) {
            $entries->push($this->entry(
                $a->created_at, 3, ++$seq, 'credit_applied',
                'Credit from FWSSR' . $a->receipt_id . ' applied to FWSSi' . $a->invoice_id,
                'GH₵' . number_format($a->settled, 2) . ' moved from the client\'s credit to this invoice', 0, 0, url('receipt/' . $a->receipt_id)
            ));
        }

        return $entries;
    }

    private function entry($date, int $order, int $seq, string $kind, string $description, ?string $details, float $debit, float $credit, ?string $link): array
    {
        return [
            'date' => Carbon::parse($date)->startOfDay(),
            'order' => $order,   // same day: invoices, then receipts, then their deductions, then credit notes
            'seq' => $seq,
            'kind' => $kind,
            'description' => $description,
            'details' => $details,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'link' => $link,
            'balance' => 0.0,
        ];
    }

    private function how(Receipt $r): string
    {
        $parts = [];
        if ((float) $r->cash_amount > 0) {
            $parts[] = 'Cash ' . number_format($r->cash_amount, 2);
        }
        if ((float) $r->momo_amount > 0) {
            $parts[] = 'MoMo ' . number_format($r->momo_amount, 2) . ($r->momo_transactin_id ? ' (' . $r->momo_transactin_id . ')' : '');
        }
        if ((float) $r->cheque_amount > 0) {
            $parts[] = 'Cheque ' . number_format($r->cheque_amount, 2) . rtrim(' ' . trim($r->cheque_bank . ' ' . $r->cheque_reference));
        }
        if ((float) $r->transfer_amount > 0) {
            $parts[] = 'Transfer ' . number_format($r->transfer_amount, 2) . rtrim(' ' . trim($r->transfer_bank . ' ' . $r->transfer_reference));
        }
        if ((float) $r->other_payment_amnt > 0) {
            $parts[] = 'Other ' . number_format($r->other_payment_amnt, 2) . ($r->other_payment_descri ? ' (' . $r->other_payment_descri . ')' : '');
        }

        return implode(', ', $parts);
    }

    /** Invoices still owing, worked out from what was paid against them. */
    private function openInvoices(Client $client): SupportCollection
    {
        return Invoice::where('client_id', $client->id)
            ->withSum('allocations as settled_sum', 'settled')
            ->orderBy('invoice_month')->orderBy('id')
            ->get()
            ->map(function ($inv) {
                $inv->outstanding = round((float) $inv->total - (float) $inv->settled_sum, 2);
                $due = $inv->due_date ? Carbon::parse($inv->due_date)->startOfDay() : null;
                $inv->days_overdue = $due && now()->startOfDay()->gt($due) ? (int) $due->diffInDays(now()->startOfDay()) : 0;

                return $inv;
            })
            ->filter(fn ($inv) => $inv->outstanding > 0.004)
            ->values();
    }

    private function creditReceipts(Client $client): SupportCollection
    {
        return Receipt::where('client_id', $client->id)->where('unapplied_amount', '>', 0)->orderBy('id')->get();
    }

    /** Does the statement agree with the invoice-by-invoice position? */
    private function check(Client $client, SupportCollection $all): array
    {
        $statement = round($all->sum('debit') - $all->sum('credit'), 2);
        $perInvoice = round((float) Invoice::where('client_id', $client->id)->withSum('allocations as s', 'settled')->get()
            ->sum(fn ($i) => (float) $i->total - (float) $i->s), 2);
        $credit = round((float) Receipt::where('client_id', $client->id)->sum('unapplied_amount'), 2);
        $expected = round($perInvoice - $credit, 2);

        return ['ok' => abs($statement - $expected) < 0.01, 'statement' => $statement, 'per_invoice' => $expected, 'difference' => round($statement - $expected, 2)];
    }
}
