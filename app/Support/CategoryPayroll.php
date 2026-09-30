<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Category <-> payroll, in ONE place, so the category page, the payroll master
 * and the category export always agree.
 *
 * Rule: a client holds at most ONE category per month - its latest categories
 * row for that month (highest id). Duplicate rows (e.g. A and B in the same
 * month) no longer make a client's salaries count twice.
 *
 * Money follows the payroll master:
 *   "net"  = pending + approved salaries (what will be / was paid)
 *   "held" = hold + rejected, reported separately
 */
class CategoryPayroll
{
    public const CATEGORIES = ['Category A', 'Category B', 'Category C', 'Category D'];
    public const LIVE = ['pending', 'approved'];
    public const HELD = ['hold', 'rejected'];

    /** client_id => category name for the month (latest row wins). */
    public static function latestCategorySub(Carbon $month)
    {
        [$start, $end] = PayrollMonth::span($month);

        $latest = DB::table('categories')
            ->selectRaw('client_id, MAX(id) as category_pk')
            ->whereBetween('category_month', [$start, $end])
            ->whereNotNull('client_id')
            ->groupBy('client_id');

        return DB::table('categories as cat_row')
            ->joinSub($latest, 'cat_latest', 'cat_latest.category_pk', '=', 'cat_row.id')
            ->select(['cat_row.client_id', 'cat_row.name']);
    }

    /** @return array<string, int[]> category name => client ids (latest rule) */
    public static function clientIdsByCategory(Carbon $month): array
    {
        $out = array_fill_keys(self::CATEGORIES, []);
        foreach (self::latestCategorySub($month)->get() as $row) {
            if (isset($out[$row->name])) {
                $out[$row->name][] = (int) $row->client_id;
            }
        }

        return $out;
    }

    public static function hasPayroll(Carbon $month): bool
    {
        return DB::table('salaries')->whereBetween('salary_month', PayrollMonth::span($month))->exists();
    }

    /**
     * Totals for the category tiles, one grouped query.
     *
     * @return array{has_payroll: bool, tiles: array<string, array>}
     *   tiles keyed by: 'all', 'Category A'..'Category D', 'outstanding'
     */
    public static function summary(Carbon $month): array
    {
        $blank = fn () => [
            'net' => 0.0, 'staff' => 0,
            'priority_net' => 0.0, 'priority_staff' => 0,
            'urgent_net' => 0.0, 'urgent_staff' => 0,
            'early_net' => 0.0, 'early_staff' => 0,
            'held_net' => 0.0, 'held_staff' => 0,
        ];
        $tiles = ['all' => $blank()] + array_fill_keys(self::CATEGORIES, null) + ['outstanding' => null];
        foreach ($tiles as $k => $v) {
            $tiles[$k] = $blank();
        }

        if (! self::hasPayroll($month)) {
            return ['has_payroll' => false, 'tiles' => $tiles];
        }

        $live = "salaries.payment_status IN ('pending','approved')";
        $held = "salaries.payment_status IN ('hold','rejected')";

        $rows = DB::table('salaries')
            ->leftJoinSub(self::latestCategorySub($month), 'cat', 'cat.client_id', '=', 'salaries.client_id')
            ->whereBetween('salaries.salary_month', PayrollMonth::span($month))
            ->selectRaw("
                cat.name as category_name,
                CASE WHEN salaries.client_id IS NULL THEN 1 ELSE 0 END as no_client,
                COALESCE(SUM(CASE WHEN {$live} THEN salaries.net_salary END), 0) as net,
                COUNT(CASE WHEN {$live} THEN 1 END) as staff,
                COALESCE(SUM(CASE WHEN {$live} AND salaries.pay_priority = 2 THEN salaries.net_salary END), 0) as urgent_net,
                COUNT(CASE WHEN {$live} AND salaries.pay_priority = 2 THEN 1 END) as urgent_staff,
                COALESCE(SUM(CASE WHEN {$live} AND salaries.pay_priority = 1 THEN salaries.net_salary END), 0) as early_net,
                COUNT(CASE WHEN {$live} AND salaries.pay_priority = 1 THEN 1 END) as early_staff,
                COALESCE(SUM(CASE WHEN {$held} THEN salaries.net_salary END), 0) as held_net,
                COUNT(CASE WHEN {$held} THEN 1 END) as held_staff
            ")
            ->groupBy('cat.name', DB::raw('CASE WHEN salaries.client_id IS NULL THEN 1 ELSE 0 END'))
            ->get();

        foreach ($rows as $row) {
            $keys = ['all'];
            if (in_array($row->category_name, self::CATEGORIES, true)) {
                $keys[] = $row->category_name;
            } elseif (! $row->no_client) {
                $keys[] = 'outstanding'; // has a client, but that client has no category this month
            }

            foreach ($keys as $key) {
                foreach (['net', 'urgent_net', 'early_net', 'held_net'] as $m) {
                    $tiles[$key][$m] += (float) $row->$m;
                }
                foreach (['staff', 'urgent_staff', 'early_staff', 'held_staff'] as $m) {
                    $tiles[$key][$m] += (int) $row->$m;
                }
            }
        }

        foreach ($tiles as &$t) {
            $t['priority_net'] = $t['urgent_net'] + $t['early_net'];
            $t['priority_staff'] = $t['urgent_staff'] + $t['early_staff'];
        }

        return ['has_payroll' => true, 'tiles' => $tiles];
    }

    /**
     * Who works for this client, for the drill-down panel.
     *
     * mode "payroll": this month's salary rows (net, status, the priority payroll acted on).
     * mode "roster" : no salary for this client this month -> today's active employees with
     *                 contract pay (basic + allowances) and their current priority.
     */
    public static function clientRoster(int $clientId, Carbon $month): array
    {
        $span = PayrollMonth::span($month);

        $salaries = DB::table('salaries')
            ->leftJoin('employees', 'employees.id', '=', 'salaries.employee_id')
            ->where('salaries.client_id', $clientId)
            ->whereBetween('salaries.salary_month', $span)
            ->orderByDesc('salaries.pay_priority')->orderBy('employees.name')->orderBy('salaries.id')
            ->get([
                'salaries.employee_id', 'employees.name', 'salaries.location', 'salaries.pay_priority',
                'salaries.pay_priority_reason', 'salaries.net_salary', 'salaries.payment_status', 'salaries.payment_type',
            ]);

        if ($salaries->isNotEmpty()) {
            $live = $salaries->whereIn('payment_status', self::LIVE);
            $held = $salaries->whereIn('payment_status', self::HELD);

            return [
                'mode' => 'payroll',
                'rows' => $salaries->map(fn ($s) => [
                    'employee_id' => (int) $s->employee_id,
                    'name' => (string) $s->name,
                    'location' => (string) $s->location,
                    'pay_priority' => (int) $s->pay_priority,
                    'pay_priority_reason' => $s->pay_priority_reason,
                    'amount' => (float) $s->net_salary,
                    'status' => (string) $s->payment_status,
                    'payment_type' => (string) $s->payment_type,
                ])->values()->all(),
                'totals' => [
                    'staff' => $live->count(),
                    'net' => (float) $live->sum('net_salary'),
                    'priority_staff' => $live->where('pay_priority', '>', 0)->count(),
                    'priority_net' => (float) $live->where('pay_priority', '>', 0)->sum('net_salary'),
                    'held_staff' => $held->count(),
                    'held_net' => (float) $held->sum('net_salary'),
                ],
            ];
        }

        $employees = DB::table('employees')
            ->where('client_id', $clientId)
            ->where('status', 'Active')
            ->where('ho_status', 'approved')
            ->orderByDesc('pay_priority')->orderBy('name')
            ->get(['id', 'name', 'location', 'pay_priority', 'pay_priority_reason', 'basic_salary', 'allowances', 'payment_type']);

        return [
            'mode' => 'roster',
            'rows' => $employees->map(fn ($e) => [
                'employee_id' => (int) $e->id,
                'name' => (string) $e->name,
                'location' => (string) $e->location,
                'pay_priority' => (int) $e->pay_priority,
                'pay_priority_reason' => $e->pay_priority_reason,
                'amount' => (float) $e->basic_salary + (float) $e->allowances,
                'status' => 'not generated',
                'payment_type' => (string) $e->payment_type,
            ])->values()->all(),
            'totals' => [
                'staff' => $employees->count(),
                'net' => (float) $employees->sum(fn ($e) => (float) $e->basic_salary + (float) $e->allowances),
                'priority_staff' => $employees->where('pay_priority', '>', 0)->count(),
                'priority_net' => (float) $employees->where('pay_priority', '>', 0)->sum(fn ($e) => (float) $e->basic_salary + (float) $e->allowances),
                'held_staff' => 0,
                'held_net' => 0.0,
            ],
        ];
    }
}
