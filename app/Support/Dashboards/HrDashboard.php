<?php

namespace App\Support\Dashboards;

use App\Support\Attendance\Roster;
use App\Support\EmployeeMovementsReport;
use Illuminate\Support\Facades\DB;

/**
 * HR dashboard: headcount that reconciles, movements, workforce make-up, record quality
 * and the payroll cross-check, for one month.
 *
 * Headcount and movements come from EmployeeMovementsReport (approved, dated movements
 * only), so "start + recruits + re-instated + transfers in − terminated − transfers out
 * = end" holds here exactly as on the Employees report. The workforce figures below it
 * (status, gender, department, missing details) are today's employee records.
 */
class HrDashboard
{
    /** Details an active employee's record should have, and the column that holds each. */
    public const REQUIRED_DETAILS = [
        'date_of_joining' => 'Date of joining',
        'phone_number' => 'Phone number',
        'nia_number' => 'Ghana Card (NIA) number',
        'payment_infos_id' => 'Payment details',
        'gurantor_name' => 'Guarantor',
        'date_of_birth' => 'Date of birth',
    ];

    public static function build(DashboardContext $ctx): array
    {
        // The whole period, as the Employees report does (a whole month also enables its payroll cross-check).
        $report = EmployeeMovementsReport::build($ctx->from->copy(), $ctx->to->copy(), $ctx->fieldIds);

        $series = EmployeeMovementsReport::series(array_map(fn ($b) => [$b[0]->copy(), $b[1]->copy(), $b[2]], $ctx->trendBuckets()), $ctx->fieldIds);

        // Why and how soon people leave: a short period has too few terminations to read, so use
        // at least the 12 months up to the end of the period.
        $yearFrom = $ctx->to->copy()->subMonthsNoOverflow(12)->addDay()->min($ctx->from);
        $year = EmployeeMovementsReport::build($yearFrom, $ctx->to->copy(), $ctx->fieldIds);

        $t = $report['totals'];

        return [
            'kpi' => [
                'opening' => $t['opening'],
                'closing' => $t['closing'],
                'net' => $t['net'],
                'hires' => $t['hires'],
                'reinstated' => $t['reinstated'],
                'terminated' => $t['terminated'],
                'transfers_in' => $t['transfers_in'],
                'transfers_out' => $t['transfers_out'],
                'turnover' => $t['turnover'],
                'reconciles' => $t['reconciles'],
                'early_leavers' => $report['early_leavers'],
                'pending' => array_sum($report['pending_counts']),
                'awaiting_termination' => $report['awaiting_termination'],
            ],
            'pending_counts' => $report['pending_counts'],
            'pending' => array_slice($report['pending'], 0, 8),
            'series' => [
                'labels' => array_column($series, 'label'),
                'caption' => $ctx->trendCaption(),
                'headcount' => array_column($series, 'closing'),
                'hires' => array_map(fn ($r) => $r['hires'] + $r['reinstated'], $series),
                'terminated' => array_column($series, 'terminated'),
            ],
            'offices' => $report['offices'],
            'by_reason' => self::reasons($year['by_reason']),
            'tenure' => $year['tenure'],
            'year' => [
                'label' => $yearFrom->format('M Y') . ' – ' . $ctx->to->format('M Y'),
                'hires' => $year['totals']['hires'] + $year['totals']['reinstated'],
                'terminated' => $year['totals']['terminated'],
                'turnover' => $year['totals']['turnover'],
                'early_leavers' => $year['early_leavers'],
            ],
            'by_status' => $report['by_status'],
            'payroll_check' => $report['payroll'],
            'issues' => count($report['undated']) + count($report['mismatches']) + count($report['conflicts']),
            'workforce' => self::workforce($ctx),
            'payroll' => self::payroll($ctx),
        ];
    }

    /** Termination reasons (label => count) as [label, value], largest first. */
    private static function reasons(array $byReason): array
    {
        $out = [];
        foreach ($byReason as $key => $count) {
            $label = (string) $key !== '' ? (string) $key : 'Not recorded';   // the report already gives labels
            $count = (int) $count;
            if ($count > 0) {
                $out[] = ['label' => $label, 'value' => $count];
            }
        }
        usort($out, fn ($a, $b) => $b['value'] <=> $a['value']);

        return $out;
    }

    /** Today's active employees: guards vs staff, gender, department, and records missing details. */
    private static function workforce(DashboardContext $ctx): array
    {
        $active = fn () => DB::table('employees')->whereIn('employees.status', Roster::ACTIVE_STATUSES)
            ->when($ctx->fieldIds !== null, fn ($q) => $q->whereIn('employees.field_id', $ctx->fieldIds ?: [0]));

        $total = $active()->count();
        $guards = $active()->where('role_id', DashboardContext::GUARD_ROLE_ID)->count();

        $gender = $active()->selectRaw("LOWER(COALESCE(NULLIF(TRIM(gender), ''), 'not recorded')) as gender_key, COUNT(*) as cnt")
            ->groupBy('gender_key')->pluck('cnt', 'gender_key')
            ->mapWithKeys(fn ($n, $g) => [ucfirst($g) => (int) $n])->sortDesc()->all();

        $departments = $active()->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->selectRaw("COALESCE(departments.name, 'No department') as dept_name, COUNT(*) as cnt")
            ->groupBy('departments.name')->orderByDesc('cnt')->limit(8)->get()
            ->map(fn ($r) => ['label' => $r->dept_name, 'value' => (int) $r->cnt])->all();

        $missing = [];
        foreach (self::REQUIRED_DETAILS as $col => $label) {
            $n = $active()->where(fn ($q) => $q->whereNull($col)->orWhere($col, ''))->count();
            if ($n > 0) {
                $missing[] = ['label' => $label, 'value' => $n, 'pct' => $total ? round($n / $total * 100, 1) : 0];
            }
        }
        usort($missing, fn ($a, $b) => $b['value'] <=> $a['value']);

        // Guards not posted to any client: on the books but not on a roster.
        $unposted = $active()->where('role_id', DashboardContext::GUARD_ROLE_ID)->whereNull('client_id')->count();

        return [
            'active' => $total,
            'guards' => $guards,
            'staff' => $total - $guards,
            'unposted_guards' => $unposted,
            'gender' => $gender,
            'departments' => $departments,
            'missing' => $missing,
            'complete_pct' => $total ? round(($total - $active()->where(function ($q) {
                foreach (array_keys(self::REQUIRED_DETAILS) as $col) {
                    $q->orWhereNull($col)->orWhere($col, '');
                }
            })->count()) / $total * 100, 1) : null,
        ];
    }

    /** Payroll for the months the period touches: people paid, cost, and the attendance deductions taken. */
    private static function payroll(DashboardContext $ctx): array
    {
        $row = DB::table('salaries')->whereBetween('salary_month', $ctx->monthSpan())->whereIn('payment_status', ['pending', 'approved'])
            ->when($ctx->fieldIds !== null, fn ($q) => $q->whereIn('field_id', $ctx->fieldIds ?: [0]))
            ->selectRaw('COUNT(DISTINCT employee_id) as people, COUNT(*) as payslips, COALESCE(SUM(gross_salary),0) as gross_pay,
                COALESCE(SUM(net_salary),0) as net_pay, COALESCE(SUM(cost_to_company),0) as ctc_amount,
                COALESCE(SUM(absent),0) as absent_amount, COALESCE(SUM(overtime),0) as overtime_amount')
            ->first();

        return [
            'visible' => $ctx->user->hasRole(['Invoice', 'Manager', 'Internal Auditor', 'Officer', 'Finance Manager', 'Director']),
            'months' => $ctx->monthsLabel(),
            'heads' => (int) $row->people,
            'gross' => round((float) $row->gross_pay, 2),
            'net' => round((float) $row->net_pay, 2),
            'ctc' => round((float) $row->ctc_amount, 2),
            'absent' => round((float) $row->absent_amount, 2),
            'overtime' => round((float) $row->overtime_amount, 2),
            // Per payslip, so a quarter is not three months' pay per person.
            'avg_net' => $row->payslips ? round($row->net_pay / $row->payslips, 2) : 0,
        ];
    }
}
