<?php

namespace App\Services\Receipts;

use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\Receipt;

/**
 * Posts bank transfers straight to the receiving bank account.
 *
 * Cash and cheques reach a bank through Collections -> Bank Deposit, but a
 * transfer is already sitting in our account the moment the receipt is
 * written, so it never went through a deposit and never showed in any bank
 * balance. This posts it (credit) to the bank picked on the receipt, and
 * reverses it (a matching debit line, nothing deleted) if the receipt is
 * edited or deleted, so the bank ledger keeps a full audit trail.
 *
 * Ledger rows written here have receipt_id set and deposit_id null.
 */
class BankPosting
{
    /** Make the bank ledger match the receipt's current transfer amount and bank. */
    public function syncTransfer(Receipt $receipt): void
    {
        $wantBank = (int) ($receipt->transfer_to_bank_id ?? 0);
        $wantAmount = round((float) ($receipt->transfer_amount ?? 0), 2);
        $wanted = $wantBank && $wantAmount > 0 ? [$wantBank => $wantAmount] : [];

        $current = $this->netPostings($receipt);

        if ($this->same($wanted, $current)) {
            return;
        }

        $this->reverse($receipt, 'Reversal (receipt edited)');

        foreach ($wanted as $bankId => $amount) {
            $this->post($receipt, $bankId, $amount, 'Transfer received');
        }
    }

    /** Take every transfer posting of this receipt back out of the bank. */
    public function reverse(Receipt $receipt, string $why = 'Reversal (receipt deleted)'): void
    {
        foreach ($this->netPostings($receipt) as $bankId => $amount) {
            if (abs($amount) > 0.004) {
                $this->post($receipt, $bankId, -$amount, $why);
            }
        }
    }

    /** @return array<int, float> bank_id => net amount currently posted for this receipt */
    private function netPostings(Receipt $receipt): array
    {
        return BankTransaction::where('receipt_id', $receipt->id)
            ->whereNull('deposit_id')
            ->get()
            ->groupBy('bank_id')
            ->map(fn ($rows) => round((float) $rows->sum('credit') - (float) $rows->sum('debit'), 2))
            ->filter(fn ($net) => abs($net) > 0.004)
            ->all();
    }

    private function post(Receipt $receipt, int $bankId, float $amount, string $what): void
    {
        $bank = Bank::whereKey($bankId)->lockForUpdate()->first();
        if (! $bank) {
            return;
        }

        $newBalance = round((float) $bank->total + $amount, 2);

        BankTransaction::create([
            'bank_id' => $bank->id,
            'receipt_id' => $receipt->id,
            'credit' => $amount > 0 ? $amount : null,
            'debit' => $amount < 0 ? abs($amount) : null,
            'balance' => $newBalance,
            'narration' => $what . ' — receipt FWSSR' . $receipt->id
                . ($receipt->transfer_reference ? ' (ref ' . $receipt->transfer_reference . ')' : ''),
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
