<?php

namespace App\Support\Attendance;

use App\Models\Attendance;
use App\Models\Salary;
use App\Support\PayrollMonth;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * salaries.absent from guard attendance.
 *
 *   absent deduction = days marked Absent in the month x daily rate
 *   daily rate       = (basic, or basic + allowances) / (days in the month, or 30, or 26)
 *
 * Which pay a day is worth is a Finance setting (Payroll > Absent deductions); the
 * default is basic salary / days in the month, because guards work every day and the
 * old time sheet counted against all 28-31 days.
 *
 * Rules that protect what is already there:
 *  - Only employees with attendance recorded in that month are touched. Office staff,
 *    and guards at clients not yet on attendance, keep whatever was typed in.
 *  - Paid (approved) salaries are never changed.
 *  - Only "Absent" counts. Off / Sick / Pass are paid; "?" (sign-ins awaiting review)
 *    and blank days are not absences, and are shown as warnings instead.
 *  - The deduction never goes above the pay it is worked out from.
 *  - When the salary's totals are already worked out, total_deductions, net_salary and
 *    cost_to_company move by the same difference (absent is a plain deduction: no tax or SSNIT effect).
 */
class AbsentDeduction
{
    public const BASES = ['basic' => 'Basic salary', 'basic_allowances' => 'Basic + allowances'];
    public const DIVISORS = ['month_days' => 'Days in the month (28-31)', '30' => '30 days', '26' => '26 days'];

    /** Salaries that can still change (not paid). */
    public const EDITABLE_STATUSES = ['pending', 'rejected', 'hold'];

    /** @return array{basis: string, divisor: string, auto_on_create: bool, updated_at: ?string, user_id: ?int} */
    public static function settings(): array
    {
        $row = DB::table('absent_deduction_settings')->orderByDesc('id')->first();

        return [
            'basis' => isset(self::BASES[$row->basis ?? '']) ? $row->basis : 'basic',
            'divisor' => isset(self::DIVISORS[$row->divisor ?? '']) ? $row->divisor : 'month_days',
            'auto_on_create' => $row ? (bool) $row->auto_on_create : true,
            'updated_at' => $row->updated_at ?? null,
            'user_id' => $row->user_id ?? null,
        ];
    }

    public static function saveSettings(string $basis, string $divisor, bool $auto, ?int $userId): void
    {
        DB::table('absent_deduction_settings')->insert([
            'basis' => $basis, 'divisor' => $divisor, 'auto_on_create' => $auto,
            'user_id' => $userId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** What one day is worth for this pay, in this month. */
    public static function dailyRate(float $basic, float $allowances, CarbonInterface|string $month, ?array $settings = null): float
    {
        $s = $settings ?? self::settings();
        $base = $basic + ($s['basis'] === 'basic_allowances' ? $allowances : 0);
        $days = $s['divisor'] === 'month_days' ? PayrollMonth::parse($month)->daysInMonth : (int) $s['divisor'];

        return $days > 0 ? round($base / $days, 2) : 0.0;
    }

    public static function amount(int $absentDays, float $basic, float $allowances, CarbonInterface|string $month, ?array $settings = null): float
    {
        $s = $settings ?? self::settings();
        $cap = $basic + ($s['basis'] === 'basic_allowances' ? $allowances : 0);

        return round(min($cap, $absentDays * self::dailyRate($basic, $allowances, $month, $s)), 2);
    }

    /**
     * Attendance per employee for a month: [employee_id => ['absent' => n, 'pending' => n]].
     * An employee missing from the result has no attendance that month (not tracked).
     */
    public static function attendanceFor(array $employeeIds, CarbonInterface|string $month): array
    {
        if (! $employeeIds) {
            return [];
        }
        [$start, $end] = PayrollMonth::span($month);
        $out = [];
        foreach (array_chunk(array_values(array_unique($employeeIds)), 1000) as $chunk) {
            $rows = DB::table('attendances')->whereIn('employee_id', $chunk)->whereBetween('work_date', [$start, $end])
                ->selectRaw('employee_id, status, COUNT(*) as n')->groupBy('employee_id', 'status')->get();
            foreach ($rows as $r) {
                $out[(int) $r->employee_id] ??= ['absent' => 0, 'pending' => 0];
                if ($r->status === Attendance::ABSENT) {
                    $out[(int) $r->employee_id]['absent'] += (int) $r->n;
                } elseif ($r->status === Attendance::PENDING) {
                    $out[(int) $r->employee_id]['pending'] += (int) $r->n;
                }
            }
        }

        return $out;
    }

    /**
     * Fill 'absent' on salary rows about to be inserted (SalaryController::store).
     * Rows must have employee_id, basic_salary, allowances. Returns [rows, employee ids that were filled].
     *
     * @return array{0: Collection, 1: array<int>}
     */
    public static function fillNewRows(Collection $rows, CarbonInterface|string $month): array
    {
        $s = self::settings();
        if (! $s['auto_on_create'] || $rows->isEmpty()) {
            return [$rows, []];
        }

        $att = self::attendanceFor($rows->pluck('employee_id')->map(fn ($id) => (int) $id)->all(), $month);
        $filled = [];
        $rows = $rows->map(function ($row) use ($att, $month, $s, &$filled) {
            $a = $att[(int) $row['employee_id']] ?? null;
            if ($a && $a['absent'] > 0) {
                $row['absent'] = self::amount($a['absent'], (float) $row['basic_salary'], (float) $row['allowances'], $month, $s);
                $filled[] = (int) $row['employee_id'];
            }

            return $row;
        });

        return [$rows, $filled];
    }

    /**
     * Log the rows that fillNewRows() filled, once they have ids.
     */
    public static function logNewRows(CarbonInterface|string $month, array $employeeIds, ?int $userId): void
    {
        if (! $employeeIds) {
            return;
        }
        $s = self::settings();
        [$start, $end] = PayrollMonth::span($month);
        $salaries = Salary::whereBetween('salary_month', [$start, $end])->whereIn('employee_id', $employeeIds)
            ->get(['id', 'employee_id', 'basic_salary', 'allowances', 'absent']);
        $att = self::attendanceFor($salaries->pluck('employee_id')->all(), $month);

        $now = now();
        $log = $salaries->filter(fn ($sal) => ($att[(int) $sal->employee_id]['absent'] ?? 0) > 0)->map(fn ($sal) => [
            'salary_id' => $sal->id, 'employee_id' => $sal->employee_id, 'salary_month' => $start,
            'absent_days' => $att[(int) $sal->employee_id]['absent'],
            'daily_rate' => self::dailyRate((float) $sal->basic_salary, (float) $sal->allowances, $month, $s),
            'old_amount' => null, 'new_amount' => $sal->absent, 'basis' => $s['basis'], 'divisor' => $s['divisor'],
            'trigger' => 'salaries_added', 'user_id' => $userId, 'created_at' => $now, 'updated_at' => $now,
        ])->values();

        foreach ($log->chunk(500) as $chunk) {
            DB::table('absent_deduction_changes')->insert($chunk->all());
        }
    }

    /**
     * One line per salary in the month for employees with attendance: current absent vs from attendance.
     *
     * @return Collection<int, array>
     */
    public static function preview(CarbonInterface|string $month, ?int $fieldId = null): Collection
    {
        $s = self::settings();
        [$start, $end] = PayrollMonth::span($month);

        $salaries = Salary::query()
            ->leftJoin('employees', 'employees.id', '=', 'salaries.employee_id')
            ->leftJoin('clients', 'clients.id', '=', 'salaries.client_id')
            ->whereBetween('salaries.salary_month', [$start, $end])
            ->when($fieldId, fn ($q) => $q->where('salaries.field_id', $fieldId))
            ->orderBy('employees.name')
            ->get(['salaries.*', 'employees.name as employee_name', DB::raw('COALESCE(clients.business_name, clients.name) as client_name')]);

        $att = self::attendanceFor($salaries->pluck('employee_id')->map(fn ($id) => (int) $id)->all(), $month);

        return $salaries->filter(fn ($sal) => isset($att[(int) $sal->employee_id]))->map(function ($sal) use ($att, $month, $s) {
            $a = $att[(int) $sal->employee_id];
            $new = self::amount($a['absent'], (float) $sal->basic_salary, (float) $sal->allowances, $month, $s);
            $old = $sal->absent === null ? null : round((float) $sal->absent, 2);
            $editable = in_array($sal->payment_status, self::EDITABLE_STATUSES, true);

            return [
                'salary' => $sal,
                'absent_days' => $a['absent'],
                'pending_days' => $a['pending'],
                'daily_rate' => self::dailyRate((float) $sal->basic_salary, (float) $sal->allowances, $month, $s),
                'current' => $old,
                'new' => $new,
                'differs' => abs((float) $old - $new) >= 0.005,
                'editable' => $editable,
            ];
        })->values();
    }

    /**
     * Set salaries.absent from attendance for the given salary ids (only unpaid ones of that month).
     *
     * @return array{updated: int, skipped: int}
     */
    public static function apply(CarbonInterface|string $month, array $salaryIds, ?int $userId): array
    {
        $lines = self::preview($month)->keyBy(fn ($l) => (int) $l['salary']->id);
        $s = self::settings();
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($salaryIds, $lines, $s, $month, $userId, &$updated, &$skipped) {
            foreach (array_unique(array_map('intval', $salaryIds)) as $id) {
                $line = $lines->get($id);
                if (! $line || ! $line['editable'] || ! $line['differs']) {
                    $skipped++;
                    continue;
                }
                $salary = Salary::lockForUpdate()->find($id);
                if (! $salary || ! in_array($salary->payment_status, self::EDITABLE_STATUSES, true)) {
                    $skipped++;
                    continue;
                }

                $old = (float) $salary->absent;
                $delta = round($line['new'] - $old, 2);
                $salary->absent = $line['new'];
                // Totals already worked out (upload / edit): move them by the difference.
                if ($salary->total_deductions !== null) {
                    $salary->total_deductions = round((float) $salary->total_deductions + $delta, 2);
                }
                if ($salary->net_salary !== null) {
                    $salary->net_salary = round((float) $salary->net_salary - $delta, 2);
                }
                if ($salary->cost_to_company !== null) {
                    $salary->cost_to_company = round((float) $salary->cost_to_company - $delta, 2);
                }
                $salary->save();

                DB::table('absent_deduction_changes')->insert([
                    'salary_id' => $salary->id, 'employee_id' => $salary->employee_id,
                    'salary_month' => PayrollMonth::span($month)[0],
                    'absent_days' => $line['absent_days'], 'daily_rate' => $line['daily_rate'],
                    'old_amount' => $line['current'], 'new_amount' => $line['new'],
                    'basis' => $s['basis'], 'divisor' => $s['divisor'], 'trigger' => 'applied',
                    'user_id' => $userId, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $updated++;
            }
        });

        return ['updated' => $updated, 'skipped' => $skipped];
    }
}
