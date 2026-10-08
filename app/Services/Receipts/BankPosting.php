<?php

namespace App\Services\Receipts;

use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\Collection;
use App\Models\Receipt;

/**
 * Puts cheque and transfer money straight into the bank account chosen on the
 * receipt — the moment the receipt is written, not later through Bank Deposit.
 *
 *   cash      -> Collections -> Bank Deposit (unchanged)
 *   cheque    -> credited to receipts.cheque_to_bank_id immediately
 *   transfer  -> credited to receipts.transfer_to_bank_id immediately
 *   momo      -> not a bank (unchanged)
 *
 * Editing a receipt re-posts it; deleting it reverses it. Nothing is ever
 * deleted from the bank ledger: a change adds a reversal line (debit) and a
 * new posting, so every movement stays visible.
 *
 * Ledger rows written here have receipt_id + channel ('cheque' | 'transfer')
 * and no deposit_id.
 */
class BankPosting
{
    public const CHANNELS = [
        'cheque' => ['amount' => 'cheque_amount', 'bank' => 'cheque_to_bank_id', 'ref' => 'cheque_reference', 'label' => 'Cheque received'],
        'transfer' => ['amount' => 'transfer_amount', 'bank' => 'transfer_to_bank_id', 'ref' => 'transfer_reference', 'label' => 'Transfer received'],
    ];

    /** Make the bank ledger match the receipt's current cheque and transfer. */
    public function sync(Receipt $receipt): void
    {
        foreach (array_keys(self::CHANNELS) as $channel) {
            $this->syncChannel($receipt, $channel);
        }
        $this->syncCollection($receipt);
    }

    /** @deprecated kept for callers written before cheques posted directly */
    public function syncTransfer(Receipt $receipt): void
    {
        $this->sync($receipt);
    }

    /** Take every cheque/transfer posting of this receipt back out of the bank. */
    public function reverse(Receipt $receipt, string $why = 'Reversal (receipt deleted)'): void
    {
        foreach (array_keys(self::CHANNELS) as $channel) {
            foreach ($this->netPostings($receipt, $channel) as $bankId => $amount) {
                $this->post($receipt, $channel, $bankId, -$amount, $why);
            }
        }
    }

    /** True when this receipt's cheque has already gone straight into a bank. */
    public static function chequePostedDirectly(?int $receiptId): bool
    {
        if (! $receiptId) {
            return false;
        }

        return BankTransaction::where('receipt_id', $receiptId)->where('channel', 'cheque')->exists();
    }

    /**
     * What Bank Deposit still has to take to the bank for a collection:
     * the cash, plus the cheque only if it was never posted directly
     * (cheques received before this change).
     *
     * @return array{cash: float, cheque: float, total: float}
     */
    public static function depositable(Collection $collection): array
    {
        $cash = round((float) $collection->cash_amount, 2);
        $cheque = self::chequePostedDirectly($collection->receipt_id) ? 0.0 : round((float) $collection->cheque_amount, 2);

        return ['cash' => $cash, 'cheque' => $cheque, 'total' => round($cash + $cheque, 2)];
    }

    private function syncChannel(Receipt $receipt, string $channel): void
    {
        $cfg = self::CHANNELS[$channel];
        $bankId = (int) ($receipt->{$cfg['bank']} ?? 0);
        $amount = round((float) ($receipt->{$cfg['amount']} ?? 0), 2);
        $wanted = $bankId && $amount > 0 ? [$bankId => $amount] : [];
        $current = $this->netPostings($receipt, $channel);

        if ($this->same($wanted, $current)) {
            return;
        }

        foreach ($current as $oldBank => $oldAmount) {
            $this->post($receipt, $channel, $oldBank, -$oldAmount, 'Reversal (receipt edited)');
        }
        foreach ($wanted as $newBank => $newAmount) {
            $this->post($receipt, $channel, $newBank, $newAmount, $cfg['label']);
        }
    }

    /**
     * A cheque that went straight to the bank must not be deposited again,
     * so a cheque/transfer collection with no cash is marked "Banked".
     * Anything with cash still to deposit stays "undeposited".
     */
    private function syncCollection(Receipt $receipt): void
    {
        $collection = Collection::where('receipt_id', $receipt->id)->first();
        if (! $collection || strcasecmp((string) $collection->status, 'deposited') === 0) {
            return;
        }

        $wentToBank = (float) $receipt->cheque_amount > 0 || (float) $receipt->transfer_amount > 0;
        if (self::depositable($collection)['total'] > 0 || ! $wentToBank) {
            $collection->status = 'undeposited';   // cash still to deposit (or momo only: never a bank)
        } else {
            $collection->status = 'Banked';        // everything already went straight to the bank
        }
        $collection->save();
    }

    /** @return array<int, float> bank_id => net amount currently posted for this receipt and channel */
    private function netPostings(Receipt $receipt, string $channel): array
    {
        return BankTransaction::where('receipt_id', $receipt->id)
            ->where('channel', $channel)
            ->whereNull('deposit_id')
            ->get()
            ->groupBy('bank_id')
            ->map(fn ($rows) => round((float) $rows->sum('credit') - (float) $rows->sum('debit'), 2))
            ->filter(fn ($net) => abs($net) > 0.004)
            ->all();
    }

    private function post(Receipt $receipt, string $channel, int $bankId, float $amount, string $what): void
    {
        $bank = Bank::whereKey($bankId)->lockForUpdate()->first();
        if (! $bank || abs($amount) < 0.005) {
            return;
        }

        $newBalance = round((float) $bank->total + $amount, 2);
        $ref = $receipt->{self::CHANNELS[$channel]['ref']} ?? null;

        BankTransaction::create([
            'bank_id' => $bank->id,
            'receipt_id' => $receipt->id,
            'channel' => $channel,
            'credit' => $amount > 0 ? $amount : null,
            'debit' => $amount < 0 ? abs($amount) : null,
            'balance' => $newBalance,
            'narration' => $what . ' — receipt FWSSR' . $receipt->id . ($ref ? ' (ref ' . $ref . ')' : ''),
        ]);

        $bank->total = $newBalance;
        $bank->save();
    }

    private function same(array $a, array $b): bool
    {
        ksort($a);
        ksort($b);
        if (array_keys($a) !== array_keys($b)) {
            return false;
        }
        foreach ($a as $k => $v) {
            if (abs($v - $b[$k]) > 0.004) {
                return false;
            }
        }

        return true;
    }
}
