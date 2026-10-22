<?php

namespace App\Services\Banking;

use App\Models\Bank;
use App\Models\Creditor;
use App\Models\CreditorEntry;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Money the company borrows and pays back (a director's loan, a shareholder, a lender).
 * It is a liability, not income; paying it back is not an expense.
 *
 *  receive()   creditor's money into a company bank account or Main Cash:
 *              account + (channel loan_in), owed +.
 *  onBehalf()  the creditor paid something for the company from their own money (a supplier,
 *              ticked approved expenses): no company account moves, owed +; the expenses are paid.
 *  repay()     company pays the creditor back from a bank or Main Cash: account − (loan_out),
 *              owed −. Interest on top is a separate payment (category "Interest on loans").
 *  reverse()   any of the above entered in error (posts the opposite; nothing is deleted).
 */
class CreditorBook
{
    public function __construct(private AccountBook $book)
    {
    }

    public function create(array $input): Creditor
    {
        $opening = round((float) ($input['opening_balance'] ?? 0), 2);
        if ($opening < 0) {
            throw ValidationException::withMessages(['opening_balance' => 'The opening balance is what the company owes (0 or more).']);
        }

        return Creditor::create([
            'name' => trim((string) $input['name']),
            'kind' => $input['kind'],
            'phone' => $input['phone'] ?? null,
            'email' => $input['email'] ?? null,
            'notes' => $input['notes'] ?? null,
            'opening_balance' => $opening,
            'opening_date' => $input['opening_date'] ?? now()->toDateString(),
            'balance' => $opening,
            'user_id' => Auth::id(),
        ]);
    }

    /** @param array{bank_id:int, amount:float|string, entry_date:string, method:string, reference?:?string, description?:?string} $input */
    public function receive(Creditor $creditor, array $input): CreditorEntry
    {
        return DB::transaction(function () use ($creditor, $input) {
            $creditor = $this->lock($creditor);
            $bank = Bank::findOrFail((int) $input['bank_id']);
            $amount = $this->amount($input['amount'] ?? 0);
            $date = $this->date($input['entry_date'] ?? null);
            $method = $bank->isCash() ? 'cash' : (string) ($input['method'] ?? 'transfer');

            $entry = $this->entry($creditor, 'received', $amount, $date, [
                'bank_id' => $bank->id, 'method' => $method,
                'reference' => $this->clean($input['reference'] ?? null, 60),
                'description' => $this->clean($input['description'] ?? null, 500),
            ]);
            $this->book->record($bank, $amount, 'loan_in', $date,
                'Funding from ' . $creditor->name . ' (' . $creditor->kind_label . ', to be repaid)'
                . ($entry->reference ? ' — ref ' . $entry->reference : '') . ($entry->description ? ' — ' . $entry->description : ''),
                ['creditor_entry_id' => $entry->id], 'amount');

            return $entry;
        });
    }

    /**
     * @param array{amount?:float|string, entry_date:string, payee?:?string, description?:?string, expense_ids?:array<int>} $input
     */
    public function onBehalf(Creditor $creditor, array $input): CreditorEntry
    {
        return DB::transaction(function () use ($creditor, $input) {
            $creditor = $this->lock($creditor);
            $date = $this->date($input['entry_date'] ?? null);
            $ids = array_values(array_unique(array_map('intval', array_filter((array) ($input['expense_ids'] ?? [])))));
            $expenses = collect();
            if ($ids) {
                $expenses = Expense::whereIn('id', $ids)->lockForUpdate()->get();
                $bad = $expenses->filter(fn ($e) => $e->status_3 !== 'approved' || $e->isPaid())->pluck('id')
                    ->merge(array_diff($ids, $expenses->pluck('id')->all()));
                if ($bad->isNotEmpty()) {
                    throw ValidationException::withMessages(['expense_ids' => 'Expense #' . $bad->implode(', #') . ' is not approved or is already paid.']);
                }
                $amount = round((float) $expenses->sum('amount'), 2);
            } else {
                $amount = $this->amount($input['amount'] ?? 0);
            }
            $payee = $this->clean($input['payee'] ?? null, 150);
            if (! $expenses->count() && ! $payee) {
                throw ValidationException::withMessages(['payee' => 'Who did the creditor pay?']);
            }

            $entry = $this->entry($creditor, 'on_behalf', $amount, $date, [
                'reference' => $this->clean($input['reference'] ?? null, 60),
                'description' => mb_substr(trim(($payee ? 'Paid ' . $payee : 'Paid expenses #' . $expenses->pluck('id')->implode(', #'))
                    . (($d = $this->clean($input['description'] ?? null, 300)) ? ' — ' . $d : '')), 0, 500),
            ]);
            foreach ($expenses as $e) {
                $e->creditor_entry_id = $entry->id;
                $e->save();
            }

            return $entry;
        });
    }

    /** @param array{bank_id:int, amount:float|string, interest?:float|string|null, entry_date:string, method:string, reference?:?string, description?:?string} $input */
    public function repay(Creditor $creditor, array $input): CreditorEntry
    {
        return DB::transaction(function () use ($creditor, $input) {
            $creditor = $this->lock($creditor);
            $bank = Bank::findOrFail((int) $input['bank_id']);
            $amount = $this->amount($input['amount'] ?? 0);
            $interest = round((float) ($input['interest'] ?? 0), 2);
            $date = $this->date($input['entry_date'] ?? null);
            if ($amount > (float) $creditor->balance + 0.004) {
                throw ValidationException::withMessages(['amount' => 'The company owes ' . $creditor->name . ' GH₵ ' . number_format((float) $creditor->balance, 2)
                    . ' — it cannot repay GH₵ ' . number_format($amount, 2) . '. Put any interest in the interest box.']);
            }
            if ($interest < 0) {
                throw ValidationException::withMessages(['interest' => 'Interest cannot be negative.']);
            }
            $method = $bank->isCash() ? 'cash' : (string) ($input['method'] ?? 'transfer');
            if ($method === 'cheque' && trim((string) ($input['reference'] ?? '')) === '') {
                throw ValidationException::withMessages(['reference' => 'Enter the cheque number.']);
            }

            $entry = $this->entry($creditor, 'repaid', -$amount, $date, [
                'bank_id' => $bank->id, 'method' => $method,
                'reference' => $this->clean($input['reference'] ?? null, 60),
                'description' => $this->clean($input['description'] ?? null, 500),
                'interest' => $interest > 0 ? $interest : null,
            ]);
            $this->book->record($bank, -$amount, 'loan_out', $date,
                'Repayment to ' . $creditor->name . ' (' . $creditor->kind_label . ')'
                . ($entry->reference ? ' — ' . ($method === 'cheque' ? 'cheque ' : 'ref ') . $entry->reference : ''),
                ['creditor_entry_id' => $entry->id], 'amount');

            if ($interest > 0) {
                // Interest is a cost of borrowing: an ordinary payment, counted as money out.
                $p = $this->book->pay([
                    'bank_id' => $bank->id, 'payee' => $creditor->name, 'method' => in_array($method, ['cash', 'cheque', 'momo'], true) ? $method : 'transfer',
                    'reference' => $entry->reference, 'payment_date' => $date->toDateString(), 'category' => 'interest', 'amount' => $interest,
                    'description' => 'Interest with repayment #' . $entry->id,
                ]);
                $entry->interest_payment_id = $p->id;
                $entry->save();
            }

            return $entry;
        });
    }

    /** Undo an entry made in error: the opposite entry and, if money moved, the opposite bank line. */
    public function reverse(CreditorEntry $entry, string $reason): CreditorEntry
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages(['reversal_reason' => 'Give a reason (at least 5 characters).']);
        }

        return DB::transaction(function () use ($entry, $reason) {
            $entry = CreditorEntry::whereKey($entry->id)->lockForUpdate()->firstOrFail();
            if ($entry->reversed_at || $entry->kind === 'reversal') {
                throw ValidationException::withMessages(['reversal_reason' => 'This entry is already reversed (or is itself a reversal).']);
            }
            $creditor = $this->lock($entry->creditor);
            if ($entry->owed_change > 0 && (float) $creditor->balance - (float) $entry->owed_change < -0.004) {
                throw ValidationException::withMessages(['reversal_reason' => 'Reversing this would leave the company owing less than nothing — reverse the later repayment first.']);
            }

            $back = $this->entry($creditor, 'reversal', -(float) $entry->owed_change, now(), [
                'bank_id' => $entry->bank_id, 'reverses_id' => $entry->id,
                'description' => 'Reversal of ' . strtolower(CreditorEntry::KINDS[$entry->kind] ?? $entry->kind) . ' #' . $entry->id . ' (' . mb_substr($reason, 0, 200) . ')',
            ]);
            if ($entry->bank_id && in_array($entry->kind, ['received', 'repaid'], true)) {
                // received: money goes back out; repaid: money comes back in.
                $this->book->record(Bank::findOrFail($entry->bank_id), -(float) $entry->owed_change, $entry->kind === 'received' ? 'loan_in' : 'loan_out', now(),
                    'Reversal: ' . ($entry->kind === 'received' ? 'funding from ' : 'repayment to ') . $creditor->name . ' (' . mb_substr($reason, 0, 120) . ')',
                    ['creditor_entry_id' => $back->id], 'reversal_reason');
            }
            if ($entry->interest_payment_id) {
                $this->book->cancelPayment(\App\Models\AccountPayment::findOrFail($entry->interest_payment_id), 'Repayment #' . $entry->id . ' reversed: ' . $reason);
            }
            Expense::where('creditor_entry_id', $entry->id)->update(['creditor_entry_id' => null]);   // back to awaiting payment

            $entry->reversed_at = now();
            $entry->reversed_by = Auth::id();
            $entry->reversal_reason = mb_substr($reason, 0, 500);
            $entry->save();

            return $back;
        });
    }

    /**
     * Statement for a period: opening balance owed, each entry with running balance, closing.
     *
     * @return array{opening: float, closing: float, lines: Collection, received: float, on_behalf: float, repaid: float}
     */
    public function statement(Creditor $creditor, Carbon $from, Carbon $to): array
    {
        $before = CreditorEntry::where('creditor_id', $creditor->id)->where('entry_date', '<', $from->toDateString())->sum('owed_change');
        $opening = round((float) $creditor->opening_balance + (float) $before, 2);
        $running = $opening;
        $lines = CreditorEntry::with(['bank', 'user'])->where('creditor_id', $creditor->id)
            ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('entry_date')->orderBy('id')->get()
            ->map(function ($e) use (&$running) {
                $running = round($running + (float) $e->owed_change, 2);
                $e->running = $running;

                return $e;
            });
        $sum = fn (string $kind) => round((float) $lines->where('kind', $kind)->sum('owed_change'), 2);

        return [
            'opening' => $opening,
            'closing' => $running,
            'lines' => $lines,
            'received' => $sum('received'),
            'on_behalf' => $sum('on_behalf'),
            'repaid' => -$sum('repaid'),
            'reversals' => $sum('reversal'),
        ];
    }

    private function entry(Creditor $creditor, string $kind, float $owedChange, Carbon $date, array $extra): CreditorEntry
    {
        $entry = CreditorEntry::create(array_merge([
            'creditor_id' => $creditor->id, 'entry_date' => $date->toDateString(), 'kind' => $kind,
            'owed_change' => round($owedChange, 2), 'user_id' => Auth::id(),
        ], $extra));
        $creditor->balance = round((float) $creditor->balance + $owedChange, 2);
        $creditor->save();

        return $entry;
    }

    private function lock(Creditor $creditor): Creditor
    {
        $c = Creditor::whereKey($creditor->id)->lockForUpdate()->firstOrFail();
        if ($c->is_active === false) {
            throw ValidationException::withMessages(['amount' => $c->name . ' is closed.']);
        }

        return $c;
    }

    private function amount($v): float
    {
        $a = round((float) $v, 2);
        if ($a <= 0) {
            throw ValidationException::withMessages(['amount' => 'Enter the amount.']);
        }

        return $a;
    }

    private function date(?string $v): Carbon
    {
        $d = $v ? Carbon::parse($v)->startOfDay() : now()->startOfDay();
        if ($d->gt(now()->endOfDay())) {
            throw ValidationException::withMessages(['entry_date' => 'The date cannot be in the future.']);
        }

        return $d;
    }

    private function clean(?string $v, int $max): ?string
    {
        $v = trim((string) $v);

        return $v === '' ? null : mb_substr($v, 0, $max);
    }
}
