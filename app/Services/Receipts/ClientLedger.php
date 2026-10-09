<?php

namespace App\Services\Receipts;

use App\Models\Client;
use App\Models\ClientLedgerEntry;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\ReceiptAllocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The client's account as permanent entries (client_ledger_entries).
 *
 * Posting rules — one entry per "source key":
 *   invoice:{id}            debit  invoice total                  dated invoice month
 *   receipt:{id}:money      credit money received                  dated receipt date
 *   receipt:{id}:wht|vat|deduction  credit WHT / 7% VAT withheld / deductions
 *   allocation:{id}         no amount — credit applied to an invoice (it only moves money)
 *
 * sync() compares what the documents say now with what is posted:
 *   - a posted entry whose document changed or disappeared gets a REVERSAL entry
 *     (opposite amounts, dated today, pointing at it);
 *   - the corrected entry is posted — dated the document's own date the first
 *     time, and today when it corrects an earlier posting.
 * Nothing is ever updated or deleted, so a statement sent last month reads the
 * same today; the correction shows up on the day it was made.
 *
 * sync() is called at the end of every invoice/receipt change and before a
 * statement is shown, so the ledger can never drift from the documents.
 */
class ClientLedger
{
    public const MONEY_FIELDS = ['cash_amount', 'momo_amount', 'cheque_amount', 'transfer_amount', 'other_payment_amnt'];

    /** Bring one client's ledger in line with its documents. Returns the number of entries posted. */
    public function sync(int $clientId, ?string $reason = null): int
    {
        return DB::transaction(function () use ($clientId, $reason) {
            // One sync per client at a time, so the same change is never posted twice.
            Client::whereKey($clientId)->lockForUpdate()->first();

            $desired = $this->desired($clientId);
            $live = $this->live($clientId);
            $postedKeys = ClientLedgerEntry::where('client_id', $clientId)->whereNull('reverses_id')->distinct()->pluck('source_key')->flip();
            $today = now()->startOfDay();
            $count = 0;

            // Reverse what no longer matches its document.
            foreach ($live as $key => $entry) {
                if (isset($desired[$key]) && $this->same($entry, $desired[$key])) {
                    continue;
                }
                $why = isset($desired[$key]) ? 'corrected' : ($reason ?: 'removed');
                ClientLedgerEntry::create([
                    'client_id' => $clientId,
                    'entry_date' => $today,
                    'kind' => 'reversal',
                    'source_key' => $key,
                    'invoice_id' => $entry->invoice_id,
                    'receipt_id' => $entry->receipt_id,
                    'debit' => $entry->credit,
                    'credit' => $entry->debit,
                    'description' => 'Reversal of: ' . $entry->description,
                    'details' => ucfirst($why) . ' on ' . $today->format('d M Y') . ' (was dated ' . $entry->entry_date->format('d M Y') . ')',
                    'reverses_id' => $entry->id,
                    'user_id' => Auth::id(),
                ]);
                unset($live[$key]);
                $count++;
            }

            // Post what is not posted yet.
            foreach ($desired as $key => $want) {
                if (isset($live[$key])) {
                    continue;
                }
                $isCorrection = isset($postedKeys[$key]);
                // array_merge (not +): the values below must override the document's own date/details.
                ClientLedgerEntry::create(array_merge($want, [
                    'client_id' => $clientId,
                    'source_key' => $key,
                    'user_id' => Auth::id(),
                    'entry_date' => $isCorrection ? $today : $want['entry_date'],
                    'details' => $isCorrection
                        ? trim(($want['details'] ? $want['details'] . ' · ' : '') . 'Corrected entry, document dated ' . Carbon::parse($want['entry_date'])->format('d M Y'))
                        : $want['details'],
                ]));
                $count++;
            }

            return $count;
        });
    }

    /** Sync every client that has documents (used once after deploying, and as a nightly safety net). */
    public function syncAll(?callable $progress = null): array
    {
        $ids = Invoice::whereNotNull('client_id')->distinct()->pluck('client_id')
            ->merge(Receipt::whereNotNull('client_id')->distinct()->pluck('client_id'))
            ->merge(ClientLedgerEntry::distinct()->pluck('client_id'))
            ->unique()->sort()->values();

        $posted = 0;
        foreach ($ids as $id) {
            $posted += $this->sync((int) $id);
            $progress && $progress($id);
        }

        return ['clients' => $ids->count(), 'entries' => $posted];
    }

    /** Entries currently in force (not reversals, not reversed), keyed by source. */
    private function live(int $clientId): array
    {
        $reversed = ClientLedgerEntry::where('client_id', $clientId)->whereNotNull('reverses_id')->pluck('reverses_id')->flip();

        return ClientLedgerEntry::where('client_id', $clientId)->whereNull('reverses_id')->orderBy('id')->get()
            ->reject(fn ($e) => isset($reversed[$e->id]))
            ->keyBy('source_key')
            ->all();
    }

    private function same(ClientLedgerEntry $posted, array $want): bool
    {
        return abs($posted->debit - $want['debit']) < 0.005
            && abs($posted->credit - $want['credit']) < 0.005
            && $posted->description === $want['description']
            // A correction is posted with today's date, so only first postings must match the document date.
            && ($posted->entry_date->toDateString() === Carbon::parse($want['entry_date'])->toDateString()
                || str_contains((string) $posted->details, 'Corrected entry'));
    }

    /** What the documents say the account should contain. */
    private function desired(int $clientId): array
    {
        $out = [];

        foreach (Invoice::where('client_id', $clientId)->orderBy('id')->get() as $inv) {
            $out['invoice:' . $inv->id] = $this->row('invoice', $inv->invoice_month ?? $inv->created_at,
                'Invoice FWSSi' . $inv->id . ($inv->invoice_month ? ' — ' . $inv->invoice_month->format('F Y') : ''),
                null, (float) $inv->total, 0, $inv->id, null);
        }

        foreach (Receipt::where('client_id', $clientId)->with('allocations')->orderBy('id')->get() as $r) {
            $date = $r->receipt_month ?? $r->created_at;
            $money = round(array_sum(array_map(fn ($f) => (float) $r->{$f}, self::MONEY_FIELDS)), 2);
            $how = $this->how($r);
            if ($money > 0) {
                $out['receipt:' . $r->id . ':money'] = $this->row('receipt', $date, 'Receipt FWSSR' . $r->id, $how, 0, $money, $r->invoice_id, $r->id);
            }
            foreach ([
                'wht' => [(float) $r->wht_amount, 'Withholding tax (WHT) on FWSSR' . $r->id],
                'vat' => [(float) $r->vat7_value, '7% VAT withheld on FWSSR' . $r->id],
                'deduction' => [(float) $r->dAmount, 'Deduction on FWSSR' . $r->id . ($r->description ? ' — ' . $r->description : '')],
            ] as $kind => [$amount, $label]) {
                if ($amount > 0.004) {
                    $out['receipt:' . $r->id . ':' . $kind] = $this->row($kind, $date, $label, $money > 0 ? null : $how, 0, round($amount, 2), $r->invoice_id, $r->id);
                }
            }
        }

        $allocations = ReceiptAllocation::where('client_id', $clientId)->where('source', 'credit')->orderBy('id')->get();
        foreach ($allocations as $a) {
            $out['allocation:' . $a->id] = $this->row('credit_applied', $a->created_at,
                'Credit from FWSSR' . $a->receipt_id . ' applied to FWSSi' . $a->invoice_id,
                'GH₵' . number_format($a->settled, 2) . ' moved from the client\'s credit to this invoice', 0, 0, $a->invoice_id, $a->receipt_id);
        }

        return $out;
    }

    private function row(string $kind, $date, string $description, ?string $details, float $debit, float $credit, ?int $invoiceId, ?int $receiptId): array
    {
        return [
            'kind' => $kind,
            'entry_date' => Carbon::parse($date)->startOfDay(),
            'description' => mb_substr($description, 0, 255),
            'details' => $details ? mb_substr($details, 0, 500) : null,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'invoice_id' => $invoiceId,
            'receipt_id' => $receiptId,
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
}
