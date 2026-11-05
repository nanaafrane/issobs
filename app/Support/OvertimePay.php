<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\Overtime;
use App\Models\Salary;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Overtime types, what the officer asks for, and overtime paid with the salary.
 *
 *  Types       off_day      a guard works his weekly day off (his own post, nobody absent)
 *              replacement  a guard was absent / sick / on pass and another covered
 *              shortage     nobody absent, the post is short: a guard works it after his own shift
 *              other        extra post, event, ...
 *
 *  Pay by      the officer's choice on the entry. Finance pays it through a pay-out
 *              (App\Services\Overtime\OvertimePayouts) of the same kind:
 *              cash    a cash / MoMo pay-out: one expense and one payment for the office
 *              salary  a payroll pay-out: the amount goes on the guard's salary of that month
 *                      (salaries.overtime; the part from entries in overtime_from_entries)
 *
 *  Month end   generating a month's salaries sends the approved "with salary" overtime up
 *              to that month to payroll by itself. Approved after that, it stays owed and
 *              goes with next month's salaries (or Finance sends it to payroll now).
 *  Shortage is paid with the salary; cash is an exception and needs a note.
 *  Weekly off day: a check only - a warning when the guard already had an off day that week.
 */
class OvertimePay
{
    public const TYPES = [
        'replacement' => 'Replacement',
        'shortage' => 'Shortage (double duty)',
        'off_day' => 'Off-day work',
        'other' => 'Other',
    ];

    public const PAY_BY = [
        'cash' => 'Cash today',
        'salary' => 'With salary',
    ];

    public const DEFAULT_AMOUNT = 30.0;

    // ------------------------------------------------------------------ settings

    public static function defaultAmount(): float
    {
        if (! Schema::hasTable('overtime_settings')) {
            return self::DEFAULT_AMOUNT;
        }
        $v = DB::table('overtime_settings')->orderByDesc('id')->value('default_amount');

        return $v === null ? self::DEFAULT_AMOUNT : round((float) $v, 2);
    }

    public static function settings(): array
    {
        $row = Schema::hasTable('overtime_settings') ? DB::table('overtime_settings')->orderByDesc('id')->first() : null;

        return [
            'default_amount' => $row ? round((float) $row->default_amount, 2) : self::DEFAULT_AMOUNT,
            'updated_at' => $row->updated_at ?? null,
            'user_id' => $row->user_id ?? null,
        ];
    }

    public static function saveDefaultAmount(float $amount, ?int $userId): void
    {
        DB::table('overtime_settings')->insert([
            'default_amount' => round($amount, 2), 'user_id' => $userId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** A type from the entry's guard and reason, for entries saved without one. */
    public static function guessType(?int $absentId, ?string $reason): string
    {
        if ($absentId) {
            return 'replacement';
        }

        return match ($reason) {
            'Shortage' => 'shortage',
            'Off-day work' => 'off_day',
            default => 'other',
        };
    }

    // ------------------------------------------------------------------ checks

    /** Warnings for an entry (saved on it): nothing blocks, they ask to look again. */
    public static function checks(Overtime $o): array
    {
        $out = [];
        if (! $o->ot_employee_id || ! $o->entry_date) {
            return $out;
        }
        $date = Carbon::parse($o->entry_date)->startOfDay();
        $guard = (int) $o->ot_employee_id;

        if ($o->type === 'off_day') {
            // One day off a week (Monday to Sunday): did he already have one?
            $others = Attendance::where('employee_id', $guard)->where('status', Attendance::OFF)
                ->whereBetween('work_date', [$date->copy()->startOfWeek()->toDateString(), $date->copy()->endOfWeek()->toDateString()])
                ->where('work_date', '!=', $date->toDateString())
                ->orderBy('work_date')->pluck('work_date');
            if ($others->isNotEmpty()) {
                $out[] = 'Already had an off day this week (' . $others->map(fn ($d) => Carbon::parse($d)->format('D j M'))->implode(', ') . '): is this really his off day?';
            }
        }

        if ($o->type === 'shortage') {
            // Double duty: he should have worked his own shift next to this one.
            $own = Attendance::where('employee_id', $guard)->whereIn('status', [Attendance::PRESENT, Attendance::PENDING])
                ->where(fn ($q) => $q->whereNull('overtime_id')->orWhere('overtime_id', '!=', $o->id))
                ->where(function ($q) use ($date, $o) {
                    $other = $o->shift === 'day' ? 'night' : 'day';
                    $q->where(fn ($q) => $q->where('work_date', $date->toDateString())->where('shift', $other));
                    if ($o->shift === 'day') {
                        $q->orWhere(fn ($q) => $q->where('work_date', $date->copy()->subDay()->toDateString())->where('shift', 'night'));
                    }
                })->exists();
            if (! $own) {
                $out[] = 'No own shift found next to this ' . $o->shift . ' shift on the attendance sheet: a double duty follows his own shift.';
            }
            if ($o->pay_by === 'cash') {
                $out[] = 'Shortage paid in cash (exception): ' . ($o->pay_note ?: 'no reason given') . '.';
            }
        }

        return $out;
    }

    // ------------------------------------------------------------------ salaries

    /** Overtime on payroll pay-outs for this guard and month (not cancelled). */
    public static function payrollAmount(int $employeeId, CarbonInterface|string $month): float
    {
        $m = Carbon::parse(PayrollMonth::span($month)[0])->startOfMonth()->toDateString();

        return round((float) Overtime::query()
            ->join('overtime_payouts', 'overtime_payouts.id', '=', 'overtimes.payout_id')
            ->where('overtime_payouts.method', 'payroll')->where('overtime_payouts.status', '!=', 'cancelled')
            ->where('overtime_payouts.payroll_month', $m)
            ->where('overtimes.ot_employee_id', $employeeId)
            ->sum('overtimes.amount'), 2);
    }

    /**
     * Put the salary's overtime in step with its payroll pay-outs. Overtime typed by hand on
     * the salary stays: only the part from pay-outs (overtime_from_entries) is replaced.
     * A salary already approved (paid) is not changed: returns false.
     */
    public static function syncSalary(Salary $salary): bool
    {
        $total = self::payrollAmount((int) $salary->employee_id, $salary->salary_month);
        $was = round((float) $salary->overtime_from_entries, 2);
        if ($was === $total) {
            return true;
        }
        if (! in_array($salary->payment_status, SalaryUpload::EDITABLE_STATUSES, true)) {
            return false;
        }
        $typed = max(0, round((float) $salary->overtime - $was, 2));
        $salary->loadMissing('employee.paymentInfo');
        SalaryCalculator::fill($salary, ['overtime' => ($typed + $total) ?: null]);
        $salary->overtime_from_entries = $total ?: null;
        $salary->save();

        return true;
    }

    /** The month's salaries of these guards, in step with their payroll pay-outs. */
    public static function syncGuards(array $employeeIds, CarbonInterface|string $month): array
    {
        $paid = [];
        $salaries = Salary::with('employee')->whereBetween('salary_month', PayrollMonth::span($month))
            ->whereIn('employee_id', $employeeIds ?: [0])->get();
        foreach ($salaries as $s) {
            if (! self::syncSalary($s)) {
                $paid[(int) $s->employee_id] = $s->employee?->name ?? ('FWSS ' . $s->employee_id);
            }
        }

        return $paid;
    }

    /** Guards whose salary for the month is already approved (paid): payroll overtime can't go on it. */
    public static function paidSalaries(array $employeeIds, CarbonInterface|string $month): array
    {
        return Salary::with('employee')->whereBetween('salary_month', PayrollMonth::span($month))
            ->whereIn('employee_id', $employeeIds ?: [0])->whereNotIn('payment_status', SalaryUpload::EDITABLE_STATUSES)
            ->get()->mapWithKeys(fn ($s) => [(int) $s->employee_id => $s->employee?->name ?? ('FWSS ' . $s->employee_id)])->all();
    }

    /**
     * Salaries just generated: the approved "with salary" overtime of these guards, dated up
     * to the month's end and not yet on a pay-out, is sent to payroll - one payroll pay-out
     * per office - and every salary is put in step (pay-outs Finance raised earlier included).
     *
     * @return array{entries: int, guards: int, amount: float, payouts: int}
     */
    public static function salariesGenerated(CarbonInterface|string $month, array $employeeIds): array
    {
        $month = Carbon::parse(PayrollMonth::span($month)[0])->startOfMonth();
        $out = ['entries' => 0, 'guards' => 0, 'amount' => 0.0, 'payouts' => 0];
        $owed = Overtime::owed()->where('pay_by', 'salary')->whereIn('ot_employee_id', $employeeIds ?: [0])
            ->whereDate('entry_date', '<=', $month->copy()->endOfMonth()->toDateString())->get();

        foreach ($owed->groupBy('field_id') as $fieldId => $entries) {
            app(\App\Services\Overtime\OvertimePayouts::class)->create([
                'field_id' => (int) $fieldId,
                'period_from' => $entries->min('entry_date')->toDateString(),
                'period_to' => $entries->max('entry_date')->toDateString(),
                'method' => 'payroll',
                'payroll_month' => $month->toDateString(),
                'overtime_ids' => $entries->pluck('id')->all(),
                'notes' => 'Sent to payroll when the ' . $month->format('F Y') . ' salaries were generated.',
            ]);
            $out['payouts']++;
        }
        $out['entries'] = $owed->count();
        $out['guards'] = $owed->pluck('ot_employee_id')->unique()->count();
        $out['amount'] = round((float) $owed->sum('amount'), 2);
        self::syncGuards($employeeIds, $month);

        return $out;
    }

    /** Can change the default amount per shift: head-office finance (they fund pay-outs). */
    public static function canManage(?User $user): bool
    {
        return (bool) $user?->hasRole(\App\Http\Controllers\BankController::VIEW_ROLES);
    }
}
