<?php

namespace App\Services\Receipts;

use App\Models\BankTransaction;
use App\Models\Collection;
use App\Models\Receipt;
use App\Models\ReceiptVoid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Voids a receipt instead of silently deleting it.
 *
 *  - a reason is required, and who/when is recorded;
 *  - a full copy (receipt, what it paid, collection, bank lines) is kept in receipt_voids;
 *  - cheque/transfer money is reversed out of the bank with a visible reversal line;
 *  - the invoices it paid are re-derived from the receipts that remain;
 *  - the client ledger gets reversal entries dated today, so a statement already
 *    sent still reads the same and the next one shows "voided" on the day it happened.
 *
 * The receipt then leaves the receipts table, so every existing report, dashboard
 * and export keeps working without having to filter voided rows out.
 */
class ReceiptVoider
{
    public function __construct(
        private BankPosting $bankPosting,
        private InvoiceSettlement $settlement,
        private ClientLedger $ledger,
    ) {
    }

    public function void(Receipt $receipt, string $reason): ReceiptVoid
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages(['void_reason' => 'Give a reason for voiding this receipt (at least 5 characters).']);
        }

        return DB::transaction(function () use ($receipt, $reason) {
            $receipt = Receipt::whereKey($receipt->id)->lockForUpdate()->firstOrFail();
            $collection = Collection::where('receipt_id', $receipt->id)->first();

            if ($collection && strcasecmp((string) $collection->status, 'deposited') === 0) {
                throw ValidationException::withMessages(['void_reason' => 'The cash on this receipt has already been deposited, so it cannot be voided here.']);
            }

            $money = round(array_sum(array_map(fn ($f) => (float) $receipt->{$f}, ClientLedger::MONEY_FIELDS)), 2);
            $void = ReceiptVoid::create([
                'receipt_id' => $receipt->id,
                'client_id' => $receipt->client_id,
                'amount' => $money,
                'reason' => mb_substr($reason, 0, 500),
                'voided_by' => Auth::id(),
                'snapshot' => [
                    'receipt' => $receipt->getAttributes(),
                    'allocations' => $receipt->allocations()->get()->map->getAttributes()->all(),
                    'collection' => $collection?->getAttributes(),
                    'bank_lines' => BankTransaction::where('receipt_id', $receipt->id)->get()->map->getAttributes()->all(),
                ],
            ]);

            $this->bankPosting->reverse($receipt, 'Receipt voided: ' . $reason);
            $collection?->delete();
            $this->settlement->release($receipt);
            $clientId = $receipt->client_id;
            $receipt->delete();

            if ($clientId) {
                $this->ledger->sync($clientId, 'Receipt FWSSR' . $receipt->id . ' voided: ' . $reason);
            }

            return $void;
        });
    }
}
