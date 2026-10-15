<?php

namespace App\Support;

use App\Models\Salary;
use App\Models\SalaryTopUps;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Salary payment runs: which payment ("1st", "2nd", ...) of a payroll month paid whom.
 *
 * Rules (the report depends on them):
 *  - A payroll month has at most ONE open run. Every approval while it is open (approve
 *    button, MoMo confirmation, top-up approval) is recorded in it automatically. If no run
 *    is open, the approval opens the next one (marked "opened automatically"), so a payment
 *    is never lost. The Finance Manager closes a run to start the next payment.
 *  - What was PAID is fixed in salary_payments at the moment of payment.
 *    What is OWED is always the salary's CURRENT net. So a net changed after payment (an
 *    edit, an attendance deduction, a re-upload) shows as a difference: owed when it went
 *    up, overpaid when it went down. Underpaid differences can be paid in a later run.
 *  - A payment in a CLOSED run is history: it cannot be unapproved / reversed away.
 *    One in the OPEN run can (a mistake corrected before the payment is closed).
 */
class PaymentRuns
{
    public const ROW_MAIN = 'main';
    public const ROW_HOLD = 'hold';
    public const ROW_TOPUP = 'topup';

    /* ------------------------------------------------------------------
     | Runs
     * ------------------------------------------------------------------ */

    public static function month($month): Carbon
    {
        return Carbon::parse($month instanceof CarbonInterface ? $month->toDateString() : (string) $month)->startOfMonth();
    }

    public static function openRun($month): ?object
    {
        return DB::table('payment_runs')->where('salary_month', self::month($month)->toDateString())->where('status', 'open')
            ->orderByDesc('number')->first();
    }

    public static function runs($month)
    {
        return DB::table('payment_runs')->where('salary_month', self::month($month)->toDateString())->orderBy('number')->get();
    }

    public static function label(object $run): string
    {
        return self::month($run->salary_month)->format('F') . ' · Payment ' . $run->number . ' · ' . Carbon::parse($run->run_date)->format('d M Y');
    }

    /** The open run of the month, opening the next one if none is open. */
    public static function ensureOpenRun($month, bool $auto = true): object
    {
        return self::openRun($month) ?? self::start($month, null, null, null, null, $auto);
    }

    /** Start the next payment of the month; an open one is closed first. */
    public static function start($month, ?string $date = null, ?float $funds = null, ?string $reference = null, ?string $notes = null, bool $auto = false): object
    {
        $m = self::month($month);
        if ($current = self::openRun($m)) {
            self::close($current->id);
        }
        $number = (int) DB::table('payment_runs')->where('salary_month', $m->toDateString())->max('number') + 1;

        $id = DB::table('payment_runs')->insertGetId([
            'salary_month' => $m->toDateString(),
            'number' => $number,
            'run_date' => $date ?: now()->toDateString(),
            'status' => 'open',
            'funds_released' => $funds,
            'reference' => $reference,
            'notes' => $notes,
            'auto_opened' => $auto,
            'opened_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('payment_runs')->find($id);
    }

    public static function close(int $runId): void
    {
        DB::table('payment_runs')->where('id', $runId)->where('status', 'open')
            ->update(['status' => 'closed', 'closed_by' => Auth::id(), 'closed_at' => now(), 'updated_at' => now()]);
    }

    /* ------------------------------------------------------------------
     | Recording payments
     * ------------------------------------------------------------------ */

    /** Total recorded as paid for a salary (salary + difference rows). */
    public static function paidForSalary(int $salaryId): float
    {
        return (float) DB::table('salary_payments')->where('salary_id', $salaryId)->sum('amount');
    }

    /**
     * Record an approved salary as paid in its month's open run (opening one if needed).
     * Does nothing if the salary is already recorded. Returns the run used, or null.
     */
    public static function recordSalary(Salary $salary, string $source = 'approve'): ?object
    {
        if (DB::table('salary_payments')->where('salary_id', $salary->id)->where('kind', 'salary')->exists()) {
            return null;
        }
        $run = self::ensureOpenRun($salary->salary_month);
        DB::table('salary_payments')->insert([
            'payment_run_id' => $run->id,
            'salary_month' => self::month($salary->salary_month)->toDateString(),
            'salary_id' => $salary->id,
            'kind' => 'salary',
            'source' => $source,
            'amount' => round((float) $salary->net_salary, 2),
            'net_at_payment' => round((float) $salary->net_salary, 2),
            'user_id' => Auth::id(),
            'created_at' => now(),
        ]);

        return $run;
    }

    public static function recordTopUp(SalaryTopUps $topUp): ?object
    {
        if (DB::table('salary_payments')->where('salary_top_up_id', $topUp->id)->exists()) {
            return null;
        }
        $run = self::ensureOpenRun($topUp->salary_month);
        DB::table('salary_payments')->insert([
            'payment_run_id' => $run->id,
            'salary_month' => self::month($topUp->salary_month)->toDateString(),
            'salary_top_up_id' => $topUp->id,
            'salary_id' => null,
            'kind' => 'topup',
            'source' => 'topup',
            'amount' => round((float) $topUp->top_up_amount, 2),
            'user_id' => Auth::id(),
            'created_at' => now(),
        ]);

        return $run;
    }

    /**
     * Take a salary's payment out again (unapprove). Allowed only while every payment row of
     * that salary is in an OPEN run. Returns null when done, or the closed run blocking it.
     */
    public static function removeSalaryPayment(int $salaryId): ?object
    {
        return self::removePayments('salary_id', $salaryId);
    }

    public static function removeTopUpPayment(int $topUpId): ?object
    {
        return self::removePayments('salary_top_up_id', $topUpId);
    }

    private static function removePayments(string $column, int $id): ?object
    {
        $closed = DB::table('salary_payments')->join('payment_runs', 'payment_runs.id', '=', 'salary_payments.payment_run_id')
            ->where("salary_payments.{$column}", $id)->where('payment_runs.status', 'closed')
            ->orderByDesc('payment_runs.number')->first(['payment_runs.*']);
        if ($closed) {
            return $closed;
        }
        DB::table('salary_payments')->where($column, $id)->delete();

        return null;
    }

    /** Does a closed run hold a payment of this salary? (approved before runs counts as paid) */
    public static function salaryPaidInClosedRun(int $salaryId): ?object
    {
        return DB::table('salary_payments')->join('payment_runs', 'payment_runs.id', '=', 'salary_payments.payment_run_id')
            ->where('salary_payments.salary_id', $salaryId)->where('payment_runs.status', 'closed')
            ->orderByDesc('payment_runs.number')->first(['payment_runs.*']);
    }

    /**
     * Salaries whose current net differs from what was recorded as paid.
     * Only salaries with at least one payment row (approved before runs: nothing to compare).
     */
    public static function changedAfterPayment($month, ?array $fieldIds = null)
    {
        $paid = DB::table('salary_payments')->whereNotNull('salary_id')->where('salary_month', self::month($month)->toDateString())
            ->selectRaw('salary_id, SUM(amount) as paid, MIN(net_at_payment) as first_net, GROUP_CONCAT(DISTINCT payment_run_id) as run_ids')->groupBy('salary_id');

        return DB::table('salaries')->joinSub($paid, 'p', 'p.salary_id', '=', 'salaries.id')
            ->leftJoin('employees', 'employees.id', '=', 'salaries.employee_id')
            ->leftJoin('fields', 'fields.id', '=', 'salaries.field_id')
            ->when($fieldIds !== null, fn ($q) => $q->whereIn('salaries.field_id', $fieldIds ?: [0]))
            ->whereRaw('ABS(COALESCE(salaries.net_salary,0) - p.paid) >= 0.01')
            ->orderByRaw('(COALESCE(salaries.net_salary,0) - p.paid) DESC')
            ->get(['salaries.id', 'salaries.employee_id', 'salaries.payment_status', 'salaries.net_salary', 'employees.name as employee_name',
                'fields.name as field_name', 'p.paid', 'p.first_net', 'p.run_ids'])
            ->map(function ($r) {
                $r->difference = round((float) $r->net_salary - (float) $r->paid, 2);

                return $r;
            });
    }

    /**
     * Pay the underpaid differences (net went up after payment) into the open run.
     *
     * @return array{count:int, amount:float, run:?object}
     */
    public static function payDifferences($month, ?array $salaryIds = null, ?array $fieldIds = null): array
    {
        $rows = self::changedAfterPayment($month, $fieldIds)->filter(fn ($r) => $r->difference > 0
            && in_array($r->payment_status, ['approved'], true)
            && ($salaryIds === null || in_array((int) $r->id, $salaryIds, true)));
        if ($rows->isEmpty()) {
            return ['count' => 0, 'amount' => 0.0, 'run' => null];
        }

        $run = self::ensureOpenRun($month);
        $now = now();
        DB::table('salary_payments')->insert($rows->map(fn ($r) => [
            'payment_run_id' => $run->id,
            'salary_month' => self::month($month)->toDateString(),
            'salary_id' => $r->id,
            'kind' => 'difference',
            'source' => 'difference',
            'amount' => $r->difference,
            'net_at_payment' => round((float) $r->net_salary, 2),
            'user_id' => Auth::id(),
            'created_at' => $now,
        ])->values()->all());

        return ['count' => $rows->count(), 'amount' => round($rows->sum('difference'), 2), 'run' => $run];
    }

    /* ------------------------------------------------------------------
     | Report: the "salary payment report" sheet, from the data
     * ------------------------------------------------------------------ */

    /**
     * Rows: per field office a main row (pending + approved salaries), a hold row
     * (hold + rejected) and a top-ups row. Columns: "Before payment runs" (approved
     * without a payment row) when it has anything, then every run.
     *
     * @param int[]|null $fieldIds null = every field office
     */
    public static function report($month, ?array $fieldIds = null): array
    {
        $m = self::month($month);
        [$from, $to] = [$m->toDateString(), $m->copy()->endOfMonth()->toDateString()];
        $runs = self::runs($m);
        $scope = fn ($q, string $col) => $fieldIds === null ? $q : $q->whereIn($col, $fieldIds ?: [0]);

        // Pay tables (what is owed now: CURRENT nets).
        $rowSql = "CASE WHEN salaries.payment_status IN ('hold','rejected') THEN 'hold' ELSE 'main' END";
        $salaryOwed = $scope(DB::table('salaries')->whereBetween('salaries.salary_month', [$from, $to])
            ->whereIn('salaries.payment_status', ['pending', 'approved', 'hold', 'rejected']), 'salaries.field_id')
            ->selectRaw("salaries.field_id, {$rowSql} as row_key, COALESCE(SUM(salaries.net_salary),0) as owed, COUNT(*) as staff")
            ->groupBy('salaries.field_id', 'row_key')->get();
        $topUpOwed = $scope(DB::table('salary_top_ups')->whereBetween('salary_month', [$from, $to])
            ->whereIn('status', ['saved', 'approved'])->where('top_up_amount', '>', 0), 'salary_top_ups.field_id')
            ->selectRaw("field_id, 'topup' as row_key, COALESCE(SUM(top_up_amount),0) as owed, COUNT(*) as staff")->groupBy('field_id')->get();

        // Paid per run (FIXED amounts), allocated to the salary's current row / office.
        $salaryPaid = $scope(DB::table('salary_payments')->join('salaries', 'salaries.id', '=', 'salary_payments.salary_id')
            ->where('salary_payments.salary_month', $from), 'salaries.field_id')
            ->selectRaw("salaries.field_id, {$rowSql} as row_key, salary_payments.payment_run_id as run_id, SUM(salary_payments.amount) as paid, COUNT(DISTINCT salary_payments.salary_id) as staff")
            ->groupBy('salaries.field_id', 'row_key', 'run_id')->get();
        $topUpPaid = $scope(DB::table('salary_payments')->join('salary_top_ups', 'salary_top_ups.id', '=', 'salary_payments.salary_top_up_id')
            ->where('salary_payments.salary_month', $from), 'salary_top_ups.field_id')
            ->selectRaw("salary_top_ups.field_id, 'topup' as row_key, salary_payments.payment_run_id as run_id, SUM(salary_payments.amount) as paid, COUNT(*) as staff")
            ->groupBy('salary_top_ups.field_id', 'run_id')->get();

        // Paid before payment runs existed: approved with no payment row (their current net / amount).
        $beforeSalaries = $scope(DB::table('salaries')->whereBetween('salaries.salary_month', [$from, $to])
            ->where('salaries.payment_status', 'approved')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('salary_payments')->whereColumn('salary_payments.salary_id', 'salaries.id')), 'salaries.field_id')
            ->selectRaw("salaries.field_id, 'main' as row_key, 'before' as run_id, SUM(salaries.net_salary) as paid, COUNT(*) as staff")->groupBy('salaries.field_id')->get();
        $beforeTopUps = $scope(DB::table('salary_top_ups')->whereBetween('salary_month', [$from, $to])->where('status', 'approved')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('salary_payments')->whereColumn('salary_payments.salary_top_up_id', 'salary_top_ups.id')), 'salary_top_ups.field_id')
            ->selectRaw("field_id, 'topup' as row_key, 'before' as run_id, SUM(top_up_amount) as paid, COUNT(*) as staff")->groupBy('field_id')->get();

        $columns = [];
        if ($beforeSalaries->sum('paid') + $beforeTopUps->sum('paid') > 0) {
            $columns[] = ['key' => 'before', 'label' => 'Before payment runs', 'run' => null];
        }
        foreach ($runs as $run) {
            $columns[] = ['key' => (string) $run->id, 'label' => 'Payment ' . $run->number, 'date' => Carbon::parse($run->run_date)->format('d.m.y'), 'run' => $run];
        }

        $names = DB::table('fields')->pluck('name', 'id')->all();
        $rows = [];
        $add = function ($field, string $rowKey) use (&$rows, $names): string {
            $k = ((int) $field) . '|' . $rowKey;
            $rows[$k] ??= [
                'field_id' => (int) $field ?: null,
                'office' => $names[$field] ?? 'No field office',
                'row' => $rowKey,
                'owed' => 0.0, 'staff' => 0, 'paid' => [], 'paid_staff' => [],
            ];

            return $k;
        };
        foreach ($salaryOwed->concat($topUpOwed) as $r) {
            $k = $add($r->field_id, $r->row_key);
            $rows[$k]['owed'] += (float) $r->owed;
            $rows[$k]['staff'] += (int) $r->staff;
        }
        foreach ($salaryPaid->concat($topUpPaid)->concat($beforeSalaries)->concat($beforeTopUps) as $r) {
            $k = $add($r->field_id, $r->row_key);
            $rows[$k]['paid'][(string) $r->run_id] = ($rows[$k]['paid'][(string) $r->run_id] ?? 0) + (float) $r->paid;
            $rows[$k]['paid_staff'][(string) $r->run_id] = ($rows[$k]['paid_staff'][(string) $r->run_id] ?? 0) + (int) $r->staff;
        }

        // Balance after each column, totals.
        $order = [self::ROW_MAIN => 0, self::ROW_HOLD => 1, self::ROW_TOPUP => 2];
        uasort($rows, fn ($a, $b) => strcasecmp($a['office'], $b['office']) ?: $order[$a['row']] <=> $order[$b['row']]);
        $totals = ['owed' => 0.0, 'paid' => [], 'balance' => [], 'paid_total' => 0.0];
        foreach ($rows as &$row) {
            $balance = $row['owed'];
            $row['balance'] = [];
            foreach ($columns as $c) {
                $p = round($row['paid'][$c['key']] ?? 0, 2);
                $balance -= $p;
                $row['balance'][$c['key']] = round($balance, 2);
                $totals['paid'][$c['key']] = ($totals['paid'][$c['key']] ?? 0) + $p;
                $totals['balance'][$c['key']] = ($totals['balance'][$c['key']] ?? 0) + round($balance, 2);
            }
            $row['owed'] = round($row['owed'], 2);
            $row['paid_total'] = round(array_sum($row['paid']), 2);
            $row['outstanding'] = round($row['owed'] - $row['paid_total'], 2);
            $totals['owed'] += $row['owed'];
            $totals['paid_total'] += $row['paid_total'];
        }
        unset($row);
        $totals['outstanding'] = round($totals['owed'] - $totals['paid_total'], 2);

        // Funds released vs paid, per run.
        $funds = [];
        foreach ($columns as $c) {
            if ($c['run'] && $c['run']->funds_released !== null) {
                $funds[$c['key']] = ['released' => (float) $c['run']->funds_released, 'difference' => round((float) $c['run']->funds_released - ($totals['paid'][$c['key']] ?? 0), 2)];
            }
        }

        // Where the outstanding is.
        $changed = self::changedAfterPayment($m, $fieldIds);
        $changedPaid = $changed->where('payment_status', 'approved');
        $pending = (float) $scope(DB::table('salaries')->whereBetween('salary_month', [$from, $to])->where('payment_status', 'pending'), 'salaries.field_id')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('salary_payments')->whereColumn('salary_payments.salary_id', 'salaries.id'))
            ->sum('net_salary');
        $held = collect($rows)->where('row', self::ROW_HOLD)->sum('outstanding');
        $topUps = collect($rows)->where('row', self::ROW_TOPUP)->sum('outstanding');
        $breakdown = [
            'not_paid' => round($pending, 2),
            'held' => round($held, 2),
            'topups' => round($topUps, 2),
            'owed_after_change' => round($changedPaid->where('difference', '>', 0)->sum('difference'), 2),
            'overpaid_after_change' => round($changedPaid->where('difference', '<', 0)->sum('difference'), 2),
        ];
        // Anything not explained above (e.g. a paid salary later put on hold) - should be 0.
        $breakdown['other'] = round($totals['outstanding'] - array_sum($breakdown), 2);

        return [
            'month' => $m,
            'runs' => $runs,
            'open' => $runs->firstWhere('status', 'open'),
            'columns' => $columns,
            'rows' => array_values($rows),
            'totals' => $totals,
            'funds' => $funds,
            'changed' => $changed,
            'breakdown' => $breakdown,
        ];
    }

    /** Who was paid in one cell of the report (run x office x row). */
    public static function cell($month, string $runKey, ?int $fieldId, string $rowKey, ?array $fieldIds = null)
    {
        $m = self::month($month);
        [$from, $to] = [$m->toDateString(), $m->copy()->endOfMonth()->toDateString()];
        if ($fieldIds !== null && ! in_array((int) $fieldId, $fieldIds, true)) {
            return collect();
        }
        $fieldWhere = fn ($q, $col) => $fieldId ? $q->where($col, $fieldId) : $q->where(fn ($w) => $w->whereNull($col)->orWhere($col, 0));
        $holdSql = "salaries.payment_status IN ('hold','rejected')";

        if ($rowKey === self::ROW_TOPUP) {
            $q = DB::table('salary_top_ups')->leftJoin('salaries', 'salaries.id', '=', 'salary_top_ups.salary_id')
                ->leftJoin('employees', 'employees.id', '=', 'salaries.employee_id')->whereBetween('salary_top_ups.salary_month', [$from, $to]);
            $fieldWhere($q, 'salary_top_ups.field_id');
            if ($runKey === 'before') {
                $q->where('salary_top_ups.status', 'approved')
                    ->whereNotExists(fn ($s) => $s->select(DB::raw(1))->from('salary_payments')->whereColumn('salary_payments.salary_top_up_id', 'salary_top_ups.id'))
                    ->selectRaw("employees.id as employee_id, employees.name, 'topup' as kind, salary_top_ups.top_up_amount as amount");
            } else {
                $q->join('salary_payments', 'salary_payments.salary_top_up_id', '=', 'salary_top_ups.id')->where('salary_payments.payment_run_id', (int) $runKey)
                    ->selectRaw('employees.id as employee_id, employees.name, salary_payments.kind, salary_payments.amount');
            }

            return $q->orderBy('employees.name')->get();
        }

        $q = DB::table('salaries')->leftJoin('employees', 'employees.id', '=', 'salaries.employee_id')->whereBetween('salaries.salary_month', [$from, $to]);
        $fieldWhere($q, 'salaries.field_id');
        $rowKey === self::ROW_HOLD ? $q->whereRaw($holdSql) : $q->whereRaw("NOT ({$holdSql})");
        if ($runKey === 'before') {
            $q->where('salaries.payment_status', 'approved')
                ->whereNotExists(fn ($s) => $s->select(DB::raw(1))->from('salary_payments')->whereColumn('salary_payments.salary_id', 'salaries.id'))
                ->selectRaw("employees.id as employee_id, employees.name, 'salary' as kind, salaries.net_salary as amount");
        } else {
            $q->join('salary_payments', 'salary_payments.salary_id', '=', 'salaries.id')->where('salary_payments.payment_run_id', (int) $runKey)
                ->selectRaw('employees.id as employee_id, employees.name, salary_payments.kind, salary_payments.amount');
        }

        return $q->orderBy('employees.name')->get();
    }
}
