<?php

namespace App\Services\Overtime;

use App\Models\AccountPayment;
use App\Models\Bank;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\Field;
use App\Models\Overtime;
use App\Models\OvertimePayout;
use App\Models\Salary;
use App\Services\Banking\AccountBook;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Paying overtime: approved daily entries are paid through pay-outs, one per office and
 * period, instead of one bank payment per guard per shift.
 *
 *  create()     cash / MoMo: one expense (type Overtime) + one payment from a bank or Main Cash
 *               for the ticked entries. Payroll: no money moves; entries are paid with salaries.
 *  disburse()   the office ticks each guard as paid (cash, or the MoMo reference).
 *  giveBack()   entries not paid out (guard did not collect, MoMo bounced): their money goes
 *               back into an account and the entries are owed again.
 *  cancel()     the whole pay-out, while nothing on it has been paid out.
 *  payrollCheck()  guards whose payroll overtime does not match the overtime paid with salary.
 */
class OvertimePayouts
{
    public function __construct(private AccountBook $book)
    {
    }

    /** Approved overtime not on any pay-out yet. */
    public function owed(?array $fieldIds = null, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        return Overtime::with(['otEmployee', 'client', 'field', 'absentEmployee'])->owed()
            ->when($fieldIds !== null, fn ($q) => $q->whereIn('field_id', $fieldIds ?: [0]))
            ->when($from, fn ($q) => $q->whereDate('entry_date', '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->whereDate('entry_date', '<=', $to->toDateString()))
            ->orderBy('entry_date')->orderBy('shift')->orderBy('id')
            ->get();
    }

    /**
     * @param  array{field_id:int, period_from:string, period_to:string, method:string, overtime_ids:array<int>,
     *               bank_id?:?int, pay_method?:?string, reference?:?string, held_by?:?string, payment_date?:?string,
     *               payroll_month?:?string, notes?:?string}  $input
     */
    public function create(array $input): OvertimePayout
    {
        $method = (string) ($input['method'] ?? '');
        if (! array_key_exists($method, OvertimePayout::METHODS)) {
            throw ValidationException::withMessages(['method' => 'Choose how this overtime is paid.']);
        }
        $from = Carbon::parse($input['period_from'])->startOfDay();
        $to = Carbon::parse($input['period_to'])->startOfDay();
        if ($from->gt($to)) {
            throw ValidationException::withMessages(['period_to' => 'The period ends before it starts.']);
        }
        $ids = array_values(array_unique(array_map('intval', array_filter((array) ($input['overtime_ids'] ?? [])))));
        if (! $ids) {
            throw ValidationException::withMessages(['overtime_ids' => 'Tick the overtime entries this pay-out covers.']);
        }
        if ($method === 'cash' && trim((string) ($input['held_by'] ?? '')) === '') {
            throw ValidationException::withMessages(['held_by' => 'Who is given the cash to pay the guards?']);
        }
        if ($method === 'payroll' && empty($input['payroll_month'])) {
            throw ValidationException::withMessages(['payroll_month' => 'Which month\'s salaries will carry this overtime?']);
        }

        return DB::transaction(function () use ($input, $method, $from, $to, $ids) {
            $field = Field::findOrFail((int) $input['field_id']);
            $entries = Overtime::whereIn('id', $ids)->lockForUpdate()->get();
            $bad = $entries->filter(fn (Overtime $o) => $o->status_3 !== 'approved' || $o->payout_id || $o->disbursed_at
                || (int) $o->field_id !== $field->id || $o->entry_date->lt($from) || $o->entry_date->gt($to))->pluck('id')
                ->merge(array_diff($ids, $entries->pluck('id')->all()));
            if ($bad->isNotEmpty()) {
                throw ValidationException::withMessages(['overtime_ids' => 'Overtime #' . $bad->implode(', #')
                    . ' is not approved, already on a pay-out, or not ' . $field->name . ' overtime between those dates. Refresh and try again.']);
            }
            $total = round((float) $entries->sum('amount'), 2);
            if ($total <= 0) {
                throw ValidationException::withMessages(['overtime_ids' => 'The ticked entries add up to GH₵ 0.00.']);
            }

            $payout = OvertimePayout::create([
                'field_id' => $field->id,
                'period_from' => $from,
                'period_to' => $to,
                'method' => $method,
                'payroll_month' => $method === 'payroll' ? $input['payroll_month'] : null,
                'held_by' => $method === 'cash' ? mb_substr(trim((string) $input['held_by']), 0, 120) : null,
                'total' => $total,
                'notes' => ($n = trim((string) ($input['notes'] ?? ''))) === '' ? null : mb_substr($n, 0, 500),
                'user_id' => Auth::id(),
            ]);
            Overtime::withoutEvents(fn () => Overtime::whereIn('id', $ids)->update(['payout_id' => $payout->id]));

            if ($method === 'payroll') {
                // Paid with the salaries: nothing leaves an account here.
                $this->markPaid($payout, $ids, Carbon::parse($input['payroll_month'])->endOfMonth(), 'payroll', 'Salary ' . Carbon::parse($input['payroll_month'])->format('M Y'));
                $this->closeIfDone($payout);

                return $payout->fresh();
            }

            $bank = Bank::find((int) ($input['bank_id'] ?? 0));
            if (! $bank) {
                throw ValidationException::withMessages(['bank_id' => 'Choose the account the overtime money comes from.']);
            }
            if ($method === 'momo' && $bank->isCash()) {
                throw ValidationException::withMessages(['bank_id' => 'MoMo is sent from a bank account, not from ' . $bank->name . '.']);
            }
            $payMethod = $bank->isCash() ? 'cash' : ($method === 'momo' ? 'momo' : (string) ($input['pay_method'] ?? 'cheque'));

            $expense = Expense::create([
                'description' => $payout->label() . ' · ' . count($ids) . ' entries',
                'amount' => $total,
                'expense_date' => $to->toDateString(),
                'field_id' => $field->id,
                'expense_type_id' => ExpenseType::firstOrCreate(['name' => 'Overtime', 'scope' => 'field'], ['created_by' => Auth::id()])->id,
                // Each entry has been through the overtime approval chain; the pay-out itself is raised by finance.
                'user_1' => Auth::id(), 'status_1' => 'approved', 'date_1' => now(),
                'user_2' => Auth::id(), 'status_2' => 'approved', 'date_2' => now(),
                'user_3' => Auth::id(), 'status_3' => 'approved',
            ]);

            $payment = $this->book->pay([
                'bank_id' => $bank->id,
                'payee' => $method === 'cash' ? $payout->held_by . ' (' . $field->name . ' overtime)' : 'MoMo to ' . $entries->pluck('ot_employee_id')->unique()->count() . ' guards (' . $field->name . ')',
                'method' => $payMethod,
                'reference' => $input['reference'] ?? null,
                'payment_date' => $input['payment_date'] ?? now()->toDateString(),
                'category' => 'overtime',
                'description' => $payout->label() . ' · ' . count($ids) . ' entries',
                'expense_ids' => [$expense->id],
            ]);

            $payout->bank_id = $bank->id;
            $payout->payment_id = $payment->id;
            $payout->expense_id = $expense->id;
            $payout->save();

            return $payout->fresh();
        });
    }

    /**
     * The office records guards as paid.
     *
     * @param  array<int>  $ids
     * @param  array<int, string>  $refs  MoMo transaction IDs by overtime id
     */
    public function disburse(OvertimePayout $payout, array $ids, ?string $date = null, array $refs = []): int
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids))));
        if (! $ids) {
            throw ValidationException::withMessages(['overtime_ids' => 'Tick the guards who have been paid.']);
        }

        return DB::transaction(function () use ($payout, $ids, $date, $refs) {
            $payout = OvertimePayout::whereKey($payout->id)->lockForUpdate()->firstOrFail();
            $this->mustBeOpen($payout);
            $when = $date ? Carbon::parse($date) : now();
            if ($when->gt(now()->endOfDay()) || $when->lt($payout->period_from)) {
                throw ValidationException::withMessages(['disbursed_at' => 'The date paid must be between the start of the period and today.']);
            }
            $entries = $payout->entries()->whereIn('id', $ids)->whereNull('disbursed_at')->lockForUpdate()->get();
            if ($entries->count() !== count($ids)) {
                throw ValidationException::withMessages(['overtime_ids' => 'Some of the ticked guards are already paid or not on this pay-out. Refresh and try again.']);
            }
            if ($payout->method === 'momo') {
                $missing = $entries->filter(fn ($e) => trim((string) ($refs[$e->id] ?? '')) === '');
                if ($missing->isNotEmpty()) {
                    throw ValidationException::withMessages(['refs' => 'Enter the MoMo transaction ID for each ticked guard.']);
                }
            }
            foreach ($entries as $e) {
                $ref = $payout->method === 'momo' ? mb_substr(trim((string) $refs[$e->id]), 0, 80) : 'Cash from ' . $payout->held_by;
                $this->markPaid($payout, [$e->id], $when, $payout->method, $ref);
            }
            $this->closeIfDone($payout);

            return $entries->count();
        });
    }

    /**
     * Entries the guards did not get (not collected, MoMo failed): the money goes back into an
     * account and the entries are owed again (they can go on the next pay-out).
     *
     * @param  array<int>  $ids
     */
    public function giveBack(OvertimePayout $payout, array $ids, int $intoBankId, string $reason): float
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids))));
        if (! $ids) {
            throw ValidationException::withMessages(['overtime_ids' => 'Tick the entries whose money is being returned.']);
        }

        return DB::transaction(function () use ($payout, $ids, $intoBankId, $reason) {
            $payout = OvertimePayout::whereKey($payout->id)->lockForUpdate()->firstOrFail();
            $this->mustBeOpen($payout);
            if ($payout->method === 'payroll') {
                throw ValidationException::withMessages(['overtime_ids' => 'A payroll pay-out holds no money: cancel it instead.']);
            }
            $entries = $payout->entries()->whereIn('id', $ids)->whereNull('disbursed_at')->lockForUpdate()->get();
            if ($entries->count() !== count($ids)) {
                throw ValidationException::withMessages(['overtime_ids' => 'Only entries not yet paid out can be returned.']);
            }
            $amount = round((float) $entries->sum('amount'), 2);
            $this->book->refundPart(AccountPayment::findOrFail($payout->payment_id), Bank::findOrFail($intoBankId), $amount,
                count($ids) . ' overtime entries not paid out (' . trim($reason) . ')');

            Overtime::withoutEvents(fn () => Overtime::whereIn('id', $ids)->update(['payout_id' => null]));
            $payout->returned = round((float) $payout->returned + $amount, 2);
            $payout->save();
            if ($payout->expense_id) {
                Expense::whereKey($payout->expense_id)->update(['amount' => round((float) $payout->total - (float) $payout->returned, 2)]);
            }
            $this->closeIfDone($payout);

            return $amount;
        });
    }

    /** Undo a pay-out entered in error, while none of its money has been paid to guards. */
    public function cancel(OvertimePayout $payout, string $reason): OvertimePayout
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages(['cancel_reason' => 'Give a reason (at least 5 characters).']);
        }

        return DB::transaction(function () use ($payout, $reason) {
            $payout = OvertimePayout::whereKey($payout->id)->lockForUpdate()->firstOrFail();
            if ($payout->status === 'cancelled') {
                throw ValidationException::withMessages(['cancel_reason' => 'Already cancelled.']);
            }
            if ($payout->method !== 'payroll' && ($payout->entries()->whereNotNull('disbursed_at')->exists() || (float) $payout->returned > 0)) {
                throw ValidationException::withMessages(['cancel_reason' => 'Some of this pay-out has already been paid out or returned, so it cannot be cancelled. Return the entries not paid instead.']);
            }

            if ($payout->payment_id) {
                $this->book->cancelPayment(AccountPayment::findOrFail($payout->payment_id), 'Overtime pay-out #' . $payout->id . ' cancelled: ' . $reason);
            }
            if ($payout->expense_id) {
                Expense::whereKey($payout->expense_id)->delete();
            }
            Overtime::withoutEvents(fn () => $payout->entries()->update([
                'payout_id' => null, 'disbursed_at' => null, 'disbursed_method' => null, 'disbursed_ref' => null, 'disbursed_by' => null,
            ]));

            $payout->status = 'cancelled';
            $payout->cancelled_at = now();
            $payout->cancelled_by = Auth::id();
            $payout->cancel_reason = mb_substr($reason, 0, 500);
            $payout->save();

            return $payout;
        });
    }

    /**
     * Payroll month check, per guard: overtime paid with the salary (payroll pay-outs) against
     * the "overtime" figure on the guard's salary for that month.
     *
     * @return Collection<int, array{employee_id:int, name:string, daily:float, salary:float, difference:float}>
     */
    public function payrollCheck(Carbon $month, ?array $fieldIds = null): Collection
    {
        $month = $month->copy()->startOfMonth();
        $daily = Overtime::query()
            ->join('overtime_payouts', 'overtime_payouts.id', '=', 'overtimes.payout_id')
            ->where('overtime_payouts.method', 'payroll')->where('overtime_payouts.status', '!=', 'cancelled')
            ->where('overtime_payouts.payroll_month', $month->toDateString())
            ->when($fieldIds !== null, fn ($q) => $q->whereIn('overtimes.field_id', $fieldIds ?: [0]))
            ->groupBy('overtimes.ot_employee_id')
            ->selectRaw('overtimes.ot_employee_id AS employee_id, SUM(overtimes.amount) AS amount')
            ->pluck('amount', 'employee_id');

        $salaries = Salary::whereBetween('salary_month', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->where(fn ($q) => $q->whereIn('employee_id', $daily->keys())->orWhere('overtime', '>', 0))
            ->when($fieldIds !== null, fn ($q) => $q->whereIn('field_id', $fieldIds ?: [0]))
            ->groupBy('employee_id')->selectRaw('employee_id, SUM(COALESCE(overtime,0)) AS overtime')
            ->pluck('overtime', 'employee_id');

        $names = DB::table('employees')->whereIn('id', $daily->keys()->merge($salaries->keys())->unique())->pluck('name', 'id');

        return $daily->keys()->merge($salaries->keys())->unique()->map(function ($id) use ($daily, $salaries, $names) {
            $d = round((float) ($daily[$id] ?? 0), 2);
            $s = round((float) ($salaries[$id] ?? 0), 2);

            return ['employee_id' => (int) $id, 'name' => $names[$id] ?? 'Employee #' . $id, 'daily' => $d, 'salary' => $s, 'difference' => round($s - $d, 2)];
        })->filter(fn ($r) => abs($r['difference']) > 0.004)->sortByDesc(fn ($r) => abs($r['difference']))->values();
    }

    private function markPaid(OvertimePayout $payout, array $ids, Carbon $when, string $method, string $ref): void
    {
        Overtime::withoutEvents(fn () => Overtime::whereIn('id', $ids)->update([
            'disbursed_at' => $when->toDateString(),
            'disbursed_method' => $method,
            'disbursed_ref' => mb_substr($ref, 0, 80),
            'disbursed_by' => Auth::id(),
        ]));
    }

    private function closeIfDone(OvertimePayout $payout): void
    {
        if (! $payout->entries()->whereNull('disbursed_at')->exists()) {
            $payout->status = 'closed';
            $payout->closed_at = now();
            $payout->save();
        }
    }

    private function mustBeOpen(OvertimePayout $payout): void
    {
        if ($payout->status !== 'open') {
            throw ValidationException::withMessages(['overtime_ids' => 'This pay-out is ' . $payout->status . '.']);
        }
    }
}
