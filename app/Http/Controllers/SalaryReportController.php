<?php

namespace App\Http\Controllers;

use App\Models\Field;
use App\Support\PayrollMonth;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Salaries report: payroll cost, trend, composition and category economics.
 *
 * Definitions (used consistently across every figure on the page):
 *   payroll     = payment_status IN (pending, approved)   - what the business owes
 *   paid        = approved
 *   outstanding = pending
 *   held        = hold + rejected
 *
 * Every figure comes from a handful of grouped SQL queries (no model hydration),
 * so the page costs roughly the same for 300 or 30,000 salary rows.
 * salary_month / category_month / invoice_month are assumed to be the 1st of the month,
 * which is how the app writes them.
 */
class SalaryReportController extends Controller
{
    private const PERIODS = ['monthly' => 1, 'quarterly' => 3, 'semiannual' => 6, 'yearly' => 12];

    private const TREND_BUCKETS = ['monthly' => 12, 'quarterly' => 8, 'semiannual' => 6, 'yearly' => 5];

    private const LIVE = "('pending','approved')";

    private const HELD = "('hold','rejected')";

    /**
     * Deduction columns shown in the composition chart: column => label.
     * Exactly the components SalaryController sums into total_deductions (uploadSalaries/update),
     * so the slices always add up to the "Total deductions" figure. ssnit_tier1_0_5 is NOT one of them.
     */
    private const DEDUCTIONS = [
        'tax' => 'PAYE tax', 'ssnit_tier2_5' => 'SSNIT (employee)',
        'welfare' => 'Welfare', 'maintenance' => 'Maintenance', 'absent' => 'Absent', 'boot' => 'Boots',
        'iou' => 'IOU', 'hostel' => 'Hostel', 'insurance' => 'Insurance', 'reprimand' => 'Reprimand',
        'scouter' => 'Scouter', 'raincoat' => 'Raincoat', 'meal' => 'Meal', 'loan' => 'Loan', 'walkin' => 'Walk-in',
        'amnt_ded_cof_start_date' => 'Start-date deduction', 'other_deductions' => 'Other',
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        // Same roles that see the payroll summary cards on the salaries transaction page.
        abort_unless(Auth::user()?->hasRole(['Invoice', 'Manager', 'Internal Auditor', 'Officer', 'Finance Manager']), 403);

        $period = array_key_exists($request->input('period'), self::PERIODS) ? $request->input('period') : 'monthly';
        $anchor = PayrollMonth::parse($request->input('month') ?: $this->latestPayrollMonth());
        [$from, $to] = $this->window($period, $anchor);
        [$prevFrom, $prevTo] = $this->window($period, $from->copy()->subMonth());

        $kpi = $this->kpis($from, $to);
        $prev = $this->kpis($prevFrom, $prevTo);
        $trend = $this->trend($period, $anchor);
        $movement = $this->movement($to);
        $byField = $this->byField($from, $to);
        $deductions = $this->deductions($from, $to);
        $categories = $this->categoryEconomics($from, $to);
        $topClients = $this->topClients($from, $to);
        $topUps = DB::table('salary_top_ups')->whereBetween('salary_month', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('status, COUNT(*) as cnt, COALESCE(SUM(top_up_amount),0) as amount')->groupBy('status')->get();
        $quality = $this->dataQuality($from, $to);

        return view('salaries.report', compact(
            'period', 'anchor', 'from', 'to', 'prevFrom', 'prevTo', 'kpi', 'prev', 'trend', 'movement',
            'byField', 'deductions', 'categories', 'topClients', 'topUps', 'quality'
        ));
    }

    /** Default the report to the most recent month that has payroll, not an empty "today". */
    private function latestPayrollMonth(): ?string
    {
        return DB::table('salaries')->where('salary_month', '<=', now()->endOfMonth()->toDateString())->max('salary_month');
    }

    /** Calendar-aligned [from, to] for the period containing $anchor (both are 1st-of-month Carbons). */
    private function window(string $period, Carbon $anchor): array
    {
        $size = self::PERIODS[$period];
        $startMonth = intdiv($anchor->month - 1, $size) * $size + 1;
        $from = Carbon::create($anchor->year, $startMonth, 1)->startOfDay();

        return [$from, $from->copy()->addMonths($size - 1)];
    }

    private function between($query, string $column, Carbon $from, Carbon $to)
    {
        return $query->whereBetween($column, [$from->toDateString(), $to->copy()->endOfMonth()->toDateString()]);
    }

    private function kpis(Carbon $from, Carbon $to): object
    {
        $live = self::LIVE;
        $held = self::HELD;

        $row = $this->between(DB::table('salaries'), 'salary_month', $from, $to)->selectRaw("
            COUNT(DISTINCT CASE WHEN payment_status IN {$live} THEN employee_id END) as headcount,
            COUNT(DISTINCT CASE WHEN payment_status IN {$live} THEN CONCAT(employee_id,'-',salary_month) END) as payslips,
            COUNT(DISTINCT salary_month) as months,
            COALESCE(SUM(CASE WHEN payment_status IN {$live} THEN gross_salary END),0) as gross,
            COALESCE(SUM(CASE WHEN payment_status IN {$live} THEN total_deductions END),0) as deductions,
            COALESCE(SUM(CASE WHEN payment_status IN {$live} THEN net_salary END),0) as net,
            COALESCE(SUM(CASE WHEN payment_status IN {$live} THEN cost_to_company END),0) as ctc,
            COALESCE(SUM(CASE WHEN payment_status IN {$live} THEN tax END),0) as tax,
            COALESCE(SUM(CASE WHEN payment_status IN {$live} THEN ssnit_tobe_paid13_5 END),0) as ssnit_remit,
            COALESCE(SUM(CASE WHEN payment_status IN {$live} THEN overtime END),0) as overtime,
            COALESCE(SUM(CASE WHEN payment_status = 'approved' THEN net_salary END),0) as paid,
            COUNT(CASE WHEN payment_status = 'approved' THEN 1 END) as paid_count,
            COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN net_salary END),0) as outstanding,
            COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as outstanding_count,
            COALESCE(SUM(CASE WHEN payment_status IN {$held} THEN net_salary END),0) as held,
            COUNT(CASE WHEN payment_status IN {$held} THEN 1 END) as held_count
        ")->first();

        $row->avg_net = $row->payslips > 0 ? $row->net / $row->payslips : 0;
        $row->avg_monthly_headcount = $row->months > 0 ? $row->payslips / $row->months : 0;
        $row->paid_pct = $row->net > 0 ? $row->paid / $row->net * 100 : 0;

        return $row;
    }

    /**
     * Trailing trend: last N buckets ending with the anchor's bucket, from ONE monthly-grouped query.
     * Headcount for multi-month buckets is the average monthly headcount (so quarters compare to months).
     */
    private function trend(string $period, Carbon $anchor): array
    {
        $size = self::PERIODS[$period];
        [$lastFrom] = $this->window($period, $anchor);
        $firstFrom = $lastFrom->copy()->subMonths($size * (self::TREND_BUCKETS[$period] - 1));
        $live = self::LIVE;

        $monthly = $this->between(DB::table('salaries'), 'salary_month', $firstFrom, $lastFrom->copy()->addMonths($size - 1))
            ->selectRaw("DATE_FORMAT(salary_month, '%Y-%m') as ym,
                COUNT(DISTINCT CASE WHEN payment_status IN {$live} THEN employee_id END) as headcount,
                COALESCE(SUM(CASE WHEN payment_status IN {$live} THEN gross_salary END),0) as gross,
                COALESCE(SUM(CASE WHEN payment_status IN {$live} THEN net_salary END),0) as net,
                COALESCE(SUM(CASE WHEN payment_status IN {$live} THEN cost_to_company END),0) as ctc,
                COALESCE(SUM(CASE WHEN payment_status IN {$live} THEN total_deductions END),0) as deductions")
            ->groupBy('ym')->get()->keyBy('ym');

        $out = ['labels' => [], 'gross' => [], 'net' => [], 'ctc' => [], 'deductions' => [], 'headcount' => [], 'avg_net' => []];
        for ($b = $firstFrom->copy(); $b <= $lastFrom; $b->addMonths($size)) {
            $sum = ['gross' => 0, 'net' => 0, 'ctc' => 0, 'deductions' => 0, 'heads' => 0, 'months' => 0];
            for ($i = 0; $i < $size; $i++) {
                $m = $monthly->get($b->copy()->addMonths($i)->format('Y-m'));
                if ($m) {
                    foreach (['gross', 'net', 'ctc', 'deductions'] as $k) {
                        $sum[$k] += (float) $m->{$k};
                    }
                    $sum['heads'] += (int) $m->headcount;
                    $sum['months']++;
                }
            }
            $out['labels'][] = $this->bucketLabel($period, $b);
            foreach (['gross', 'net', 'ctc', 'deductions'] as $k) {
                $out[$k][] = round($sum[$k], 2);
            }
            $avgHeads = $sum['months'] ? $sum['heads'] / $sum['months'] : 0;
            $out['headcount'][] = round($avgHeads, 1);
            $out['avg_net'][] = $sum['heads'] ? round($sum['net'] / $sum['heads'], 2) : 0;
        }

        return $out;
    }

    private function bucketLabel(string $period, Carbon $b): string
    {
        return match ($period) {
            'quarterly' => 'Q'.$b->quarter.' '.$b->year,
            'semiannual' => ($b->month <= 6 ? 'H1 ' : 'H2 ').$b->year,
            'yearly' => (string) $b->year,
            default => $b->format('M Y'),
        };
    }

    /**
     * Payroll movement for the 12 months ending at $to: employees who appear on a month's payroll
     * but not the previous month's (joiners) and the reverse (leavers). Uses the salaries table itself,
     * so it reflects who was actually paid, independent of HR status fields.
     */
    private function movement(Carbon $to): array
    {
        $end = $to->copy()->startOfMonth();
        $start = $end->copy()->subMonths(11);
        $range = [$start->toDateString(), $end->toDateString()];

        $joiners = DB::table('salaries as s')
            ->leftJoin('salaries as p', function ($j) {
                $j->on('p.employee_id', '=', 's.employee_id')
                    ->whereRaw('p.salary_month = DATE_SUB(s.salary_month, INTERVAL 1 MONTH)');
            })
            ->whereBetween('s.salary_month', $range)->whereNull('p.id')
            ->selectRaw("DATE_FORMAT(s.salary_month, '%Y-%m') as ym, COUNT(DISTINCT s.employee_id) as cnt")
            ->groupBy('ym')->pluck('cnt', 'ym');

        $leavers = DB::table('salaries as p')
            ->leftJoin('salaries as s', function ($j) {
                $j->on('s.employee_id', '=', 'p.employee_id')
                    ->whereRaw('s.salary_month = DATE_ADD(p.salary_month, INTERVAL 1 MONTH)');
            })
            ->whereBetween('p.salary_month', [$start->copy()->subMonth()->toDateString(), $end->copy()->subMonth()->toDateString()])
            ->whereNull('s.id')
            ->selectRaw("DATE_FORMAT(DATE_ADD(p.salary_month, INTERVAL 1 MONTH), '%Y-%m') as ym, COUNT(DISTINCT p.employee_id) as cnt")
            ->groupBy('ym')->pluck('cnt', 'ym');

        // The first month of the whole dataset has no "previous month", so everyone looks like a joiner.
        $firstEver = DB::table('salaries')->min('salary_month');
        $firstEverYm = $firstEver ? substr($firstEver, 0, 7) : null;

        $out = ['labels' => [], 'joiners' => [], 'leavers' => []];
        for ($m = $start->copy(); $m <= $end; $m->addMonth()) {
            $ym = $m->format('Y-m');
            $out['labels'][] = $m->format('M Y');
            $out['joiners'][] = $ym === $firstEverYm ? 0 : (int) ($joiners[$ym] ?? 0);
            $out['leavers'][] = (int) ($leavers[$ym] ?? 0);
        }
        $out['last_joiners'] = end($out['joiners']);
        $out['last_leavers'] = end($out['leavers']);

        return $out;
    }

    private function byField(Carbon $from, Carbon $to)
    {
        $live = self::LIVE;
        $held = self::HELD;

        $rows = $this->between(DB::table('salaries'), 'salary_month', $from, $to)
            ->selectRaw("field_id,
                COUNT(DISTINCT CASE WHEN payment_status IN {$live} THEN employee_id END) as headcount,
                COALESCE(SUM(CASE WHEN payment_status IN {$live} THEN net_salary END),0) as net,
                COALESCE(SUM(CASE WHEN payment_status IN {$live} THEN cost_to_company END),0) as ctc,
                COALESCE(SUM(CASE WHEN payment_status IN {$live} AND LOWER(TRIM(payment_type)) = 'bank' THEN net_salary END),0) as bank,
                COALESCE(SUM(CASE WHEN payment_status IN {$live} AND LOWER(TRIM(payment_type)) = 'cash' THEN net_salary END),0) as momo,
                COALESCE(SUM(CASE WHEN payment_status = 'approved' THEN net_salary END),0) as paid,
                COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN net_salary END),0) as outstanding,
                COALESCE(SUM(CASE WHEN payment_status IN {$held} THEN net_salary END),0) as held,
                COUNT(CASE WHEN payment_status IN {$held} THEN 1 END) as held_count")
            ->groupBy('field_id')->get()->keyBy('field_id');

        $names = Field::pluck('name', 'id');

        return $rows->map(function ($r, $id) use ($names) {
            $r->name = $names[$id] ?? 'Unassigned';

            return $r;
        })->sortByDesc('ctc')->values();
    }

    private function deductions(Carbon $from, Carbon $to)
    {
        $live = self::LIVE;
        $select = collect(self::DEDUCTIONS)->keys()
            ->map(fn ($c) => "COALESCE(SUM(CASE WHEN payment_status IN {$live} THEN {$c} END),0) as {$c}")->implode(', ');
        $row = $this->between(DB::table('salaries'), 'salary_month', $from, $to)->selectRaw($select)->first();

        return collect(self::DEDUCTIONS)
            ->map(fn ($label, $col) => (object) ['label' => $label, 'amount' => (float) $row->{$col}])
            ->filter(fn ($d) => $d->amount > 0)->sortByDesc('amount')->values();
    }

    /**
     * Category A-D economics: what each category COSTS on payroll vs what it was INVOICED and RECEIPTED,
     * plus guards invoiced vs guard payslips (a leakage indicator: paying more guards than billed).
     * Category membership is per month, so salaries/invoices are matched to the category of THEIR month.
     */
    private function categoryEconomics(Carbon $from, Carbon $to)
    {
        $live = self::LIVE;
        $range = [$from->toDateString(), $to->copy()->endOfMonth()->toDateString()];

        // One (client, month) -> category mapping; if a client is listed twice in a month, take one name.
        $catMap = DB::table('categories')->whereBetween('category_month', $range)
            ->selectRaw('client_id, category_month, MIN(name) as name')->groupBy('client_id', 'category_month');

        $payroll = DB::table('salaries')
            ->joinSub($catMap, 'c', fn ($j) => $j->on('c.client_id', '=', 'salaries.client_id')->on('c.category_month', '=', 'salaries.salary_month'))
            ->whereBetween('salaries.salary_month', $range)
            ->selectRaw("c.name,
                COUNT(DISTINCT CASE WHEN salaries.payment_status IN {$live} THEN salaries.client_id END) as clients,
                COUNT(CASE WHEN salaries.payment_status IN {$live} THEN 1 END) as payslips,
                COUNT(CASE WHEN salaries.payment_status IN {$live} AND salaries.role_id = 7 THEN 1 END) as guard_payslips,
                COALESCE(SUM(CASE WHEN salaries.payment_status IN {$live} THEN salaries.net_salary END),0) as net,
                COALESCE(SUM(CASE WHEN salaries.payment_status IN {$live} THEN salaries.net_salary END),0) as ctc")
            ->groupBy('c.name')->get()->keyBy('name');

        $invoiced = DB::table('invoices')
            ->joinSub($catMap, 'c', fn ($j) => $j->on('c.client_id', '=', 'invoices.client_id')->on('c.category_month', '=', 'invoices.invoice_month'))
            ->whereBetween('invoices.invoice_month', $range)
            ->leftJoinSub(DB::table('invoice_data')->selectRaw('invoice_id, SUM(quantity) as guards')->groupBy('invoice_id'), 'q', 'q.invoice_id', '=', 'invoices.id')
            ->leftJoinSub(DB::table('receipts')->selectRaw('invoice_id, SUM(COALESCE(cash_amount,0)+COALESCE(momo_amount,0)+COALESCE(transfer_amount,0)+COALESCE(cheque_amount,0)) as received')->groupBy('invoice_id'), 'r', 'r.invoice_id', '=', 'invoices.id')
            ->selectRaw('c.name, COALESCE(SUM(invoices.total),0) as invoiced, COALESCE(SUM(q.guards),0) as guards_invoiced, COALESCE(SUM(r.received),0) as received')
            ->groupBy('c.name')->get()->keyBy('name');

        return collect(['Category A', 'Category B', 'Category C', 'Category D'])->map(function ($name) use ($payroll, $invoiced) {
            $p = $payroll->get($name);
            $i = $invoiced->get($name);
            $ctc = (float) ($p->ctc ?? 0);
            $inv = (float) ($i->invoiced ?? 0);

            return (object) [
                'name' => $name,
                'clients' => (int) ($p->clients ?? 0),
                'payslips' => (int) ($p->payslips ?? 0),
                'guard_payslips' => (int) ($p->guard_payslips ?? 0),
                'guards_invoiced' => (float) ($i->guards_invoiced ?? 0),
                'net' => (float) ($p->net ?? 0),
                'ctc' => $ctc,
                'invoiced' => $inv,
                'received' => (float) ($i->received ?? 0),
                'margin' => (float) $i->received - $ctc,
                'margin_pct' => (float) $i->received > 0 ? ($i->received - $ctc) / (float) $i->received * 100 : null,
                'collection_pct' => $inv > 0 ? ((float) ($i->received ?? 0)) / $inv * 100 : null,
            ];
        });
    }

    private function topClients(Carbon $from, Carbon $to)
    {
        $live = self::LIVE;
        $range = [$from->toDateString(), $to->copy()->endOfMonth()->toDateString()];

        $invoiced = DB::table('invoices')->whereBetween('invoice_month', $range)
            ->selectRaw('client_id, SUM(total) as invoiced')->groupBy('client_id');

        return DB::table('salaries')
            ->join('clients', 'clients.id', '=', 'salaries.client_id')
            ->leftJoinSub($invoiced, 'i', 'i.client_id', '=', 'salaries.client_id')
            ->whereBetween('salaries.salary_month', $range)
            ->whereRaw("salaries.payment_status IN {$live}")
            ->selectRaw('salaries.client_id, clients.name, clients.business_name,
                COUNT(DISTINCT salaries.employee_id) as headcount,
                SUM(salaries.cost_to_company) as ctc, SUM(salaries.net_salary) as net,
                MAX(COALESCE(i.invoiced,0)) as invoiced')
            ->groupBy('salaries.client_id', 'clients.name', 'clients.business_name')
            ->orderByDesc('net')->limit(10)->get()
            ->map(function ($c) {
                $c->margin = $c->invoiced - $c->net;
                $c->margin_pct = $c->invoiced > 0 ? $c->margin / $c->invoiced * 100 : null;

                return $c;
            });
    }

    /** Record-level problems that make payroll figures unreliable. Each is one cheap COUNT. */
    private function dataQuality(Carbon $from, Carbon $to): array
    {
        $q = fn () => $this->between(DB::table('salaries'), 'salary_month', $from, $to);

        return [
            ['label' => "Payment type not spelled exactly 'Bank' or 'Cash'", 'count' => $q()->whereNotIn(DB::raw('BINARY payment_type'), ['Bank', 'Cash'])->count()],
            ['label' => 'Bank payments missing bank or account number', 'count' => $q()->whereRaw("LOWER(TRIM(payment_type)) = 'bank'")->where(fn ($w) => $w->whereNull('bank_id')->orWhereNull('account_number')->orWhere('account_number', ''))->count()],
            ['label' => 'Payroll rows with no client', 'count' => $q()->whereNull('client_id')->count()],
            ['label' => 'Payroll rows with net salary not yet computed', 'count' => $q()->whereRaw('payment_status IN '.self::LIVE)->whereNull('net_salary')->count()],
            ['label' => 'Guards rejected (client not invoiced)', 'count' => $q()->where('payment_status', 'rejected')->where('role_id', 7)->count()],
            ['label' => 'Duplicate payslips (same employee, same month)', 'count' => DB::query()->fromSub(
                $q()->selectRaw('employee_id, salary_month')->groupBy('employee_id', 'salary_month')->havingRaw('COUNT(*) > 1'), 'd'
            )->count()],
        ];
    }
}
