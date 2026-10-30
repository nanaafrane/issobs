<?php

namespace App\Services\Banking;

use App\Models\AccountPayment;
use App\Models\AccountTransfer;
use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Money moving between our own accounts, and money paid out.
 *
 *  transfer  cheque / transfer / withdrawal from one account into another (e.g. several
 *            cheques from several banks to fund Main Cash). Debit on the source
 *            ("transfer_out"), credit on the destination ("transfer_in"). Not income or
 *            expense — reports leave it out of money in / money out.
 *  payment   paid to someone else: cheque or transfer from a bank, cash from a cash
 *            account. One debit ("payment"). Can pay approved expenses, which are then
 *            marked paid. Cheques stay "issued" until cleared by the bank.
 *  adjustment  a correction with a reason (cash count, bank charge found on the
 *            statement…), Finance Manager / Director only.
 *
 * Nothing is edited or deleted: a mistake is reversed / cancelled with a reason, which
 * posts the opposite line. A cash account can never go below zero.
 */
class AccountBook
{
    /**
     * Move money between our accounts: any number of sources into any number of destinations,
     * by transfer, cheque (deposited, or cashed and shared out), withdrawal or cash. The plan
     * (who funded what, and the lines each account's statement shows) comes from MovePlan,
     * which the screen also uses for its live preview. Saved as one batch.
     *
     * @param  array{date?:?string, notes?:?string, sources:array, destinations:array}  $input
     * @return Collection<int, AccountTransfer>  one row per "who funded what" pair
     */
    public function move(array $input): Collection
    {
        return DB::transaction(function () use ($input) {
            $ids = collect((array) ($input['sources'] ?? []))->pluck('bank_id')
                ->merge(collect((array) ($input['destinations'] ?? []))->pluck('bank_id'))
                ->filter()->map(fn ($id) => (int) $id)->unique()->values();
            $banks = Bank::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $plan = MovePlan::make($input, $banks);

            $batch = (string) Str::uuid();
            $notes = $this->clean($input['notes'] ?? null, 500);
            $made = [];
            foreach ($plan->pairs as $k => $p) {
                $s = $plan->sources[$p['out']];
                $d = $plan->destinations[$p['in']];
                $made[$k] = AccountTransfer::create([
                    'batch' => $batch,
                    'leg_out' => $p['out'],
                    'leg_in' => $p['in'],
                    'from_bank_id' => $s['bank']->id,
                    'to_bank_id' => $d['bank']->id,
                    'amount' => $p['amount'],
                    'method' => $p['method'],
                    'reference' => $s['reference'],
                    'in_method' => $p['in_method'],
                    'in_reference' => $d['reference'] ?? ($p['in_method'] === 'cheque' ? $s['reference'] : null),
                    'transfer_date' => $s['date']->toDateString(),
                    'in_date' => $d['date']->equalTo($s['date']) ? null : $d['date']->toDateString(),
                    'notes' => $notes,
                    'user_id' => Auth::id(),
                ]);
            }

            // Money out first (a cash account must hold what it gives), then money in.
            $outs = array_filter($plan->lines, fn ($l) => $l['amount'] < 0);
            $ins = array_filter($plan->lines, fn ($l) => $l['amount'] > 0);
            foreach (array_merge($outs, $ins) as $l) {
                $field = $l['amount'] < 0 ? 'sources.' . $plan->pairs[$l['pair']]['out'] . '.amount' : null;
                $this->post($banks[$l['bank']->id], $l['amount'], $l['channel'], $l['date'], $l['narration'], ['transfer_id' => $made[$l['pair']]->id], $field);
            }

            return new \Illuminate\Database\Eloquent\Collection(array_values($made));
        });
    }

    /**
     * The older "fund one account" form: several lines (one cheque per bank…) into ONE account.
     * Kept for callers that still send it; it is a move with one destination.
     *
     * @param  array{to_bank_id:int, transfer_date:string, notes?:?string,
     *               lines: array<int, array{from_bank_id:int, amount:float|string, method:string, reference?:?string}>}  $input
     * @return Collection<int, AccountTransfer>
     */
    public function transfer(array $input): Collection
    {
        $lines = array_values(array_filter((array) ($input['lines'] ?? []), fn ($l) => (float) ($l['amount'] ?? 0) > 0));
        if (! $lines) {
            throw ValidationException::withMessages(['lines' => 'Enter at least one cheque or transfer with an amount.']);
        }
        $this->date($input['transfer_date'] ?? null, 'transfer_date');
        $to = Bank::find((int) ($input['to_bank_id'] ?? 0));
        $this->mustOperate($to, 'to_bank_id');

        $sources = [];
        foreach ($lines as $i => $line) {
            $from = Bank::find((int) ($line['from_bank_id'] ?? 0));
            $this->mustOperate($from, "lines.$i.from_bank_id");
            if ($from->id === $to->id) {
                throw ValidationException::withMessages(["lines.$i.from_bank_id" => 'Money cannot be moved from ' . $to->name . ' into itself.']);
            }
            $method = (string) ($line['method'] ?? '');
            $fits = self::methodsBetween($from, $to);
            if (! in_array($method, $fits, true)) {
                throw ValidationException::withMessages(["lines.$i.method" => 'From ' . $from->name . ' to ' . $to->name . ' the money moves by '
                    . implode(' or ', array_map(fn ($m) => strtolower(AccountTransfer::METHODS[$m]), $fits)) . '.']);
            }
            $sources[] = ['bank_id' => $from->id, 'how' => in_array($method, ['deposit', 'handover'], true) ? 'cash' : $method,
                'amount' => $line['amount'], 'reference' => $line['reference'] ?? null];
        }
        $arrive = collect($sources)->pluck('how')->unique();
        $destinations = [];
        if ($to->isCash() || $arrive->every(fn ($h) => in_array($h, ['cash', 'withdrawal'], true))) {
            $destinations[] = ['bank_id' => $to->id, 'how' => 'cash', 'amount' => collect($sources)->sum('amount')];
        } else {
            // bank -> bank: each cheque deposited, transfers received, cash paid in.
            foreach ($sources as $i => $s) {
                $destinations[] = ['bank_id' => $to->id, 'how' => $s['how'] === 'cheque' ? 'cheque' : ($s['how'] === 'transfer' ? 'transfer' : 'cash'),
                    'amount' => $s['amount'], 'cheque' => $s['how'] === 'cheque' ? $i : null];
            }
        }

        try {
            return $this->move(['date' => $input['transfer_date'] ?? null, 'notes' => $input['notes'] ?? null, 'sources' => $sources, 'destinations' => $destinations]);
        } catch (ValidationException $e) {
            $errors = [];
            foreach ($e->errors() as $key => $messages) {
                $errors[preg_match('/^sources\.(\d+)\.(\w+)$/', $key, $m) ? "lines.{$m[1]}.{$m[2]}" : 'lines'] = $messages;
            }
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * How money can move between two kinds of account.
     *   bank → bank   cheque / transfer
     *   bank → cash   cheque (cashed) / cash withdrawal
     *   cash → bank   cash paid into the bank
     *   cash → cash   cash handed over
     *
     * @return array<int, string>
     */
    public static function methodsBetween(Bank $from, Bank $to): array
    {
        return match (true) {
            ! $from->isCash() && ! $to->isCash() => ['cheque', 'transfer'],
            ! $from->isCash() && $to->isCash() => ['cheque', 'withdrawal'],
            $from->isCash() && ! $to->isCash() => ['deposit'],
            default => ['handover'],
        };
    }

    /**
     * Undo a move entered in error: every line it posted gets its opposite, so each account is
     * back where it was. Works for moves of any shape (and for older transfers).
     */
    public function reverseTransfer(AccountTransfer $transfer, string $reason): Collection
    {
        $reason = $this->reason($reason, 'reversal_reason');

        return DB::transaction(function () use ($transfer, $reason) {
            $rows = AccountTransfer::where('batch', $transfer->batch)->whereNull('reversed_at')->lockForUpdate()->get();
            if ($rows->isEmpty()) {
                throw ValidationException::withMessages(['reversal_reason' => 'This has already been reversed.']);
            }
            $lines = BankTransaction::whereIn('transfer_id', $rows->pluck('id'))->orderBy('id')->get();
            $banks = Bank::whereIn('id', $lines->pluck('bank_id')->unique())->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            // Take money back out of the accounts that received it first, then return it.
            foreach ($lines->sortBy(fn ($l) => (float) $l->credit > 0 ? 0 : 1) as $l) {
                $amount = round((float) $l->debit - (float) $l->credit, 2);
                $this->post($banks[$l->bank_id], $amount, $amount < 0 ? 'transfer_out' : 'transfer_in', now(),
                    'Reversal (' . $reason . '): ' . $l->narration, ['transfer_id' => $l->transfer_id], 'reversal_reason');
            }
            foreach ($rows as $t) {
                $t->reversed_at = now();
                $t->reversed_by = Auth::id();
                $t->reversal_reason = $reason;
                $t->save();
            }

            return $rows;
        });
    }

    /**
     * Pay someone. With expense_ids, the payment pays those approved expenses and its
     * amount is their total.
     *
     * @param  array{bank_id:int, payee:string, method:string, reference?:?string, amount?:float|string,
     *               payment_date:string, category:string, description?:?string, expense_ids?:array<int>}  $input
     */
    public function pay(array $input): AccountPayment
    {
        $date = $this->date($input['payment_date'] ?? null, 'payment_date');
        $expenseIds = array_values(array_unique(array_map('intval', array_filter((array) ($input['expense_ids'] ?? [])))));

        return DB::transaction(function () use ($input, $date, $expenseIds) {
            $bank = Bank::whereKey($input['bank_id'])->lockForUpdate()->first();
            $this->mustOperate($bank, 'bank_id');

            $method = (string) $input['method'];
            $allowed = $bank->isCash() ? ['cash'] : ['cheque', 'transfer', 'momo'];
            if (! in_array($method, $allowed, true)) {
                throw ValidationException::withMessages(['method' => $bank->isCash()
                    ? $bank->name . ' is cash: pay in cash, or pay by cheque/transfer from a bank account.'
                    : 'From a bank account, pay by cheque, transfer or mobile money.']);
            }
            if ($method === 'cheque' && trim((string) ($input['reference'] ?? '')) === '') {
                throw ValidationException::withMessages(['reference' => 'Enter the cheque number.']);
            }
            if (! array_key_exists((string) $input['category'], AccountPayment::CATEGORIES)) {
                throw ValidationException::withMessages(['category' => 'Choose what the payment is for.']);
            }

            $expenses = collect();
            if ($expenseIds) {
                $expenses = Expense::whereIn('id', $expenseIds)->lockForUpdate()->get();
                $bad = $expenses->filter(fn ($e) => $e->status_3 !== 'approved' || $e->isPaid())->pluck('id')
                    ->merge(array_diff($expenseIds, $expenses->pluck('id')->all()));
                if ($bad->isNotEmpty()) {
                    throw ValidationException::withMessages(['expense_ids' => 'Expense #' . $bad->implode(', #') . ' is not approved or has already been paid. Refresh and try again.']);
                }
                $amount = round((float) $expenses->sum('amount'), 2);
            } else {
                $amount = round((float) ($input['amount'] ?? 0), 2);
            }
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Enter the amount paid.']);
            }

            $payment = AccountPayment::create([
                'bank_id' => $bank->id,
                'payee' => $this->clean($input['payee'] ?? null, 150) ?? throw ValidationException::withMessages(['payee' => 'Who was paid?']),
                'method' => $method,
                'reference' => $this->clean($input['reference'] ?? null, 60),
                'amount' => $amount,
                'payment_date' => $date->toDateString(),
                // Pay-outs (overtime, fuel) keep their own category; any other expense payment is "Approved expenses".
                'category' => $expenses->isNotEmpty() && ! in_array($input['category'], ['overtime', 'fuel'], true) ? 'expenses' : (string) $input['category'],
                'description' => $this->clean($input['description'] ?? null, 500)
                    ?? ($expenses->isNotEmpty() ? 'Expenses #' . $expenses->pluck('id')->implode(', #') : null),
                'status' => $method === 'cheque' ? 'issued' : 'paid',
                'user_id' => Auth::id(),
            ]);

            foreach ($expenses as $e) {
                $e->payment_id = $payment->id;
                $e->save();
            }

            $this->post($bank, -$amount, 'payment', $date,
                $this->paymentLabel($payment) . ' to ' . $payment->payee . ' — ' . $payment->category_label
                . ($payment->description ? ': ' . $payment->description : ''),
                ['payment_id' => $payment->id], 'amount');

            return $payment;
        });
    }

    /** The bank has paid out the cheque (shows on the bank statement). */
    public function clearCheque(AccountPayment $payment, ?string $date = null): AccountPayment
    {
        if ($payment->status !== 'issued') {
            throw ValidationException::withMessages(['cleared_date' => 'Only an issued cheque can be marked cleared.']);
        }
        $payment->status = 'cleared';
        $payment->cleared_date = $this->date($date, 'cleared_date')->toDateString();
        $payment->save();

        return $payment;
    }

    /** Cancel a payment entered in error, or a cheque that was stopped / never presented. */
    public function cancelPayment(AccountPayment $payment, string $reason): AccountPayment
    {
        $reason = $this->reason($reason, 'cancel_reason');

        return DB::transaction(function () use ($payment, $reason) {
            $payment = AccountPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($payment->status === 'cancelled') {
                throw ValidationException::withMessages(['cancel_reason' => 'This payment is already cancelled.']);
            }
            $bank = Bank::whereKey($payment->bank_id)->lockForUpdate()->firstOrFail();
            $this->post($bank, (float) $payment->amount, 'payment', now(),
                'Cancelled: ' . $this->paymentLabel($payment) . ' to ' . $payment->payee . ' (' . $reason . ')', ['payment_id' => $payment->id]);

            Expense::where('payment_id', $payment->id)->update(['payment_id' => null]);   // back to "awaiting payment"

            $payment->status = 'cancelled';
            $payment->cancelled_at = now();
            $payment->cancelled_by = Auth::id();
            $payment->cancel_reason = $reason;
            $payment->save();

            return $payment;
        });
    }

    /**
     * Part of a payment came back (e.g. overtime cash not handed out, a MoMo transfer that
     * bounced). Posted as money in on the account it is paid back into, linked to the payment.
     */
    public function refundPart(AccountPayment $payment, Bank $into, float $amount, string $reason): BankTransaction
    {
        $reason = $this->reason($reason, 'reason');
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Nothing to return.']);
        }

        return DB::transaction(function () use ($payment, $into, $amount, $reason) {
            $bank = Bank::whereKey($into->id)->lockForUpdate()->firstOrFail();
            $this->mustOperate($bank, 'bank_id');

            return $this->post($bank, $amount, 'payment', now(), 'Returned from ' . $this->paymentLabel($payment) . ' to ' . $payment->payee . ': ' . $reason, ['payment_id' => $payment->id]);
        });
    }

    /** A correction with a reason: + adds to the balance, − takes from it. */
    public function adjust(Bank $bank, float $amount, string $reason, ?string $date = null): BankTransaction
    {
        $reason = $this->reason($reason, 'reason');
        $amount = round($amount, 2);
        if (abs($amount) < 0.005) {
            throw ValidationException::withMessages(['amount' => 'Enter the amount to add (+) or take off (−).']);
        }
        $date = $this->date($date, 'adjust_date');

        return DB::transaction(function () use ($bank, $amount, $reason, $date) {
            $bank = Bank::whereKey($bank->id)->lockForUpdate()->firstOrFail();

            return $this->post($bank, $amount, 'adjustment', $date, 'Adjustment by ' . (Auth::user()?->name ?? 'user #' . Auth::id()) . ': ' . $reason, [], 'amount');
        });
    }

    /**
     * One ledger line for another module (e.g. creditors: funding in, repayment out),
     * with the same rules: account must be open, cash never below zero.
     */
    public function record(Bank $bank, float $amount, string $channel, Carbon $date, string $narration, array $links = [], ?string $errorField = null): BankTransaction
    {
        return DB::transaction(function () use ($bank, $amount, $channel, $date, $narration, $links, $errorField) {
            $bank = Bank::whereKey($bank->id)->lockForUpdate()->firstOrFail();
            $this->mustOperate($bank, $errorField ?? 'bank_id');

            return $this->post($bank, $amount, $channel, $date, $narration, $links, $errorField);
        });
    }

    /** Post one line and move the balance. Cash accounts may not go below zero. */
    private function post(Bank $bank, float $amount, string $channel, Carbon $date, string $narration, array $links = [], ?string $errorField = null): BankTransaction
    {
        $balance = round((float) $bank->total + $amount, 2);
        if ($amount < 0 && $bank->isCash() && $balance < -0.004) {
            throw ValidationException::withMessages([$errorField ?? 'amount' => $bank->name . ' only holds GH₵ ' . number_format((float) $bank->total, 2)
                . ' — it cannot pay out GH₵ ' . number_format(-$amount, 2) . '. Fund it from a bank first.']);
        }

        $line = BankTransaction::create(array_merge([
            'bank_id' => $bank->id,
            'entry_date' => $date->toDateString(),
            'channel' => $channel,
            'credit' => $amount > 0 ? round($amount, 2) : null,
            'debit' => $amount < 0 ? round(-$amount, 2) : null,
            'balance' => $balance,
            'narration' => mb_substr($narration, 0, 255),
        ], $links));

        $bank->total = $balance;
        $bank->save();

        return $line;
    }

    private function paymentLabel(AccountPayment $p): string
    {
        return (AccountPayment::METHODS[$p->method] ?? $p->method) . ($p->reference ? ' ' . $p->reference : '');
    }

    private function mustOperate(?Bank $bank, string $field): void
    {
        if (! $bank) {
            throw ValidationException::withMessages([$field => 'Choose the account.']);
        }
        if ($bank->is_active === false) {
            throw ValidationException::withMessages([$field => $bank->name . ' is closed.']);
        }
    }

    private function date(?string $value, string $field): Carbon
    {
        try {
            $d = $value ? Carbon::parse($value)->startOfDay() : now()->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([$field => 'Enter a valid date.']);
        }
        if ($d->gt(now()->endOfDay())) {
            throw ValidationException::withMessages([$field => 'The date cannot be in the future.']);
        }

        return $d;
    }

    private function reason(string $reason, string $field): string
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages([$field => 'Give a reason (at least 5 characters).']);
        }

        return mb_substr($reason, 0, 500);
    }

    private function note(?string $notes): string
    {
        return $notes ? ' — ' . $notes : '';
    }

    private function clean(?string $value, int $max): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }
}
