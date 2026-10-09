<?php

namespace App\Services\Receipts;

use App\Models\Client;
use App\Models\ClientLedgerEntry;
use App\Models\Invoice;
use App\Models\Receipt;
use Carbon\Carbon;
use Illuminate\Support\Collection as SupportCollection;

/**
 * A client's statement of account, read from the client ledger
 * (see ClientLedger for the posting rules).
 *
 *   Debit   each invoice
 *   Credit  each receipt once (money received) + WHT / VAT withheld / deductions
 *   Note    credit applied to an invoice (no amount: it only moves money)
 *   Reversal  a correction or a voided receipt, on the day it happened
 *
 * Running balance: positive = client owes us, negative = in credit ("CR").
 *
 * Check: closing balance (all dates) must equal
 *     SUM(invoice total − allocations settled) − SUM(unused credit)
 */
class ClientStatement
{
    public function __construct(private ClientLedger $ledger)
    {
    }

    public function build(Client $client, ?Carbon $from = null, ?Carbon $to = null, bool $sync = true): array
    {
        if ($sync) {
            $this->ledger->sync($client->id);   // never show a ledger that has drifted from the documents
        }

        $from = $from?->copy()->startOfDay();
        $to = $to?->copy()->endOfDay();
        $order = ['invoice' => 0, 'receipt' => 1, 'wht' => 2, 'vat' => 2, 'deduction' => 2, 'credit_applied' => 3, 'reversal' => -1];   // on a correction day: undo the old line, then post the new one

        $entries = ClientLedgerEntry::where('client_id', $client->id)->get()
            ->sortBy(fn ($e) => [$e->entry_date->format('Y-m-d'), $order[$e->kind] ?? 9, $e->id])
            ->values();
        $notes = $this->receiptNotes($client);

        $opening = 0.0;
        $running = 0.0;
        $lines = [];
        foreach ($entries as $e) {
            $running = round($running + $e->debit - $e->credit, 2);
            if ($from && $e->entry_date->lt($from)) {
                $opening = $running;
                continue;
            }
            if ($to && $e->entry_date->gt($to)) {
                continue;
            }
            $lines[] = [
                'id' => $e->id,
                'date' => $e->entry_date,
                'kind' => $e->kind,
                'description' => $e->description,
                'details' => trim(implode(' · ', array_filter([$e->details, $e->kind === 'receipt' ? ($notes[$e->receipt_id] ?? null) : null]))),
                'debit' => $e->debit,
                'credit' => $e->credit,
                'balance' => $running,
                'link' => $e->kind === 'invoice' ? url('invoice/' . $e->invoice_id) : ($e->receipt_id && isset($notes[$e->receipt_id]) ? url('receipt/' . $e->receipt_id) : null),
            ];
        }

        $period = collect($lines);
        $closing = $period->isNotEmpty() ? $period->last()['balance'] : $opening;
        $credits = fn (array $kinds) => round($period->whereIn('kind', $kinds)->sum('credit') - $period->whereIn('kind', $kinds)->sum('debit'), 2);
        $reversals = $period->where('kind', 'reversal');

        return [
            'client' => $client,
            'from' => $from,
            'to' => $to,
            'opening' => $opening,
            'lines' => $lines,
            'invoiced' => round($period->where('kind', 'invoice')->sum('debit'), 2),
            'received' => $credits(['receipt']),
            'withheld' => $credits(['wht', 'vat', 'deduction']),
            'corrections' => round($reversals->sum('debit') - $reversals->sum('credit'), 2),
            'closing' => $closing,
            'open_invoices' => $this->openInvoices($client),
            'credit_receipts' => Receipt::where('client_id', $client->id)->where('unapplied_amount', '>', 0)->orderBy('id')->get(),
            'check' => $this->check($client, round($entries->sum('debit') - $entries->sum('credit'), 2)),
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
            'corrections' => $s['corrections'],
            'balance' => $s['closing'],
            'owed_on_invoices' => round($s['open_invoices']->sum('outstanding'), 2),
            'credit' => round($s['credit_receipts']->sum('unapplied_amount'), 2),
            'check' => $s['check'],
            'lines' => $s['lines'],
        ];
    }

    /** "1,250.00" when owed, "300.00 CR" when the client is in credit. */
    public static function money(float $balance): string
    {
        return number_format(abs($balance), 2) . ($balance < -0.004 ? ' CR' : '');
    }

    /** Invoices still owing, worked out from what was paid against them. */
    public function openInvoices(Client $client): SupportCollection
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

    /** Live notes for receipt lines (what it paid, credit kept, approval) — they can change without a posting. */
    private function receiptNotes(Client $client): array
    {
        return Receipt::where('client_id', $client->id)->with('allocations')->get()->mapWithKeys(function ($r) {
            $paid = $r->allocations->where('source', '!=', 'credit')
                ->map(fn ($a) => 'FWSSi' . $a->invoice_id . ' ' . number_format($a->amount_applied, 2))->implode(', ');
            $kept = (float) $r->unapplied_amount + (float) $r->allocations->where('source', 'credit')->sum('amount_applied');

            return [$r->id => implode(' · ', array_filter([
                $paid ? 'Paid: ' . $paid : null,
                $kept > 0.004 ? 'Kept as credit: ' . number_format($kept, 2) : null,
                $r->ho_status !== 'approved' ? 'Awaiting approval' : null,
            ]))];
        })->all();
    }

    /** Does the ledger agree with the invoice-by-invoice position? */
    private function check(Client $client, float $ledgerBalance): array
    {
        $perInvoice = round((float) Invoice::where('client_id', $client->id)->withSum('allocations as s', 'settled')->get()
            ->sum(fn ($i) => (float) $i->total - (float) $i->s), 2);
        $credit = round((float) Receipt::where('client_id', $client->id)->sum('unapplied_amount'), 2);
        $expected = round($perInvoice - $credit, 2);

        return ['ok' => abs($ledgerBalance - $expected) < 0.01, 'statement' => $ledgerBalance, 'per_invoice' => $expected, 'difference' => round($ledgerBalance - $expected, 2)];
    }
}
