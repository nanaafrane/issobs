<?php

namespace App\Support\Dashboards;

use App\Models\Bank;
use App\Services\Receipts\ReceivablesAging;
use App\Support\ReceiptReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Accounts dashboard: billing, collections, receivables, spend and payroll for one month.
 *
 * Definitions (each one reuses the rule the matching report already uses, so the
 * dashboard and the reports always agree):
 *  - Invoiced:   invoices.total for invoices whose invoice_month is the month.
 *  - Collected:  money received on head-office APPROVED receipts dated in the month
 *                (ReceiptReport: cash + MoMo + cheque + transfer + other; receipt date
 *                = receipt_month, falling back to the entry date).
 *  - Receivable: ReceivablesAging (invoice total − everything settled against it,
 *                aged from the due date), less unused client credit. It is "as of today":
 *                it does not change with the month filter.
 *  - Expenses:   expenses approved at head office (stage 3), by the date they were entered.
 *  - Payroll:    salaries of the month in a live status (pending / approved), as on the
 *                salaries report; paid = approved.
 */
class AccountsDashboard
{
    public const MODES = [
        'cash' => 'Cash',
        'momo' => 'MoMo',
        'transfer' => 'Bank transfer',
        'cheque' => 'Cheque',
        'other' => 'Other',
    ];

    private const RECEIPT_MONTH_SQL = 'SUBSTR(' . ReceiptReport::DATE_SQL . ', 1, 7)';

    public static function build(DashboardContext $ctx): array
    {
        [$from, $to] = $ctx->span();
        $prev = $ctx->month->copy()->subMonthNoOverflow();

        $receipts = new ReceiptReport($ctx->fieldIds, 'custom', Carbon::parse($from), Carbon::parse($to), $ctx->month->copy());
        $collected = $receipts->totals();
        $prevCollected = (new ReceiptReport($ctx->fieldIds, 'custom', $prev->copy(), $prev->copy()->endOfMonth(), $prev->copy()))->totals();

        $invoiced = self::invoiced($ctx, $ctx->month);
        $prevInvoiced = self::invoiced($ctx, $prev);

        $aging = (new ReceivablesAging())->build($ctx->fieldIds);
        $trend = self::trend($ctx);

        // Debtor days: what is owed now, in days of recent billing (last 3 months' average).
        $recent = array_slice($trend['invoiced'], -3);
        $avgDaily = array_sum($recent) / (count($recent) ?: 1) / 30;
        $debtorDays = $avgDaily > 0 ? (int) round(max(0, $aging['totals']['net']) / $avgDaily) : null;

        $expenses = self::expenses($ctx);
        $payroll = self::payroll($ctx);

        return [
            'kpi' => [
                'invoiced' => $invoiced['total'],
                'invoiced_count' => $invoiced['count'],
                'invoiced_prev' => $prevInvoiced['total'],
                'collected' => $collected->received,
                'collected_count' => (int) $collected->cnt,
                'collected_prev' => $prevCollected->received,
                'collection_rate' => $invoiced['total'] > 0 ? round($collected->received / $invoiced['total'] * 100, 1) : null,
                'receivable' => $aging['totals']['net'],
                'receivable_owed' => $aging['totals']['owed'],
                'credit' => $aging['totals']['credit'],
                'overdue_90' => $aging['totals']['d90plus'],
                'debtor_days' => $debtorDays,
                'awaiting' => $receipts->awaitingApproval(),
                'expenses' => $expenses['approved'],
                'expenses_pending' => $expenses['pending'],
                'expenses_pending_count' => $expenses['pending_count'],
                'wht' => $collected->wht,
                'vat_withheld' => $collected->vat,
                'unpaid_invoices' => $invoiced['unpaid_count'],
            ],
            'modes' => collect(self::MODES)->map(fn ($label, $k) => ['label' => $label, 'value' => round((float) $collected->{$k}, 2)])->values()->all(),
            'trend' => $trend,
            'aging' => [
                'buckets' => collect(ReceivablesAging::BUCKETS)->map(fn ($label, $k) => ['label' => $label, 'value' => $aging['totals'][$k]])->values()->all(),
                'top' => $aging['rows']->filter(fn ($r) => $r['net'] > 0)->take(8)->values()->all(),
                'clients_owing' => $aging['rows']->filter(fn ($r) => $r['owed'] > 0)->count(),
            ],
            'offices' => self::byOffice($ctx, $aging['rows']),
            'expense_types' => $expenses['by_type'],
            'payroll' => $payroll,
            'banks' => $ctx->user->hasRole(['Finance Manager', 'Director'])
                ? Bank::orderBy('name')->get(['id', 'name', 'branch', 'acc_number', 'total'])
                : collect(),
        ];
    }

    /** Invoices whose invoice month is $month: total, count, and how many have nothing paid yet. */
    private static function invoiced(DashboardContext $ctx, Carbon $month): array
    {
        $row = self::invoices($ctx)
            ->whereBetween('invoices.invoice_month', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->selectRaw("COUNT(*) as cnt, COALESCE(SUM(invoices.total),0) as total,
                SUM(CASE WHEN invoices.status = 'unpaid' THEN 1 ELSE 0 END) as unpaid")
            ->first();

        return ['total' => round((float) $row->total, 2), 'count' => (int) $row->cnt, 'unpaid_count' => (int) $row->unpaid];
    }

    private static function invoices(DashboardContext $ctx)
    {
        return DB::table('invoices')->join('clients', 'clients.id', '=', 'invoices.client_id')
            ->when($ctx->fieldIds !== null, fn ($q) => $q->whereIn('clients.field_id', $ctx->fieldIds ?: [0]));
    }

    private static function approvedExpenses(DashboardContext $ctx)
    {
        return DB::table('expenses')->whereNull('expenses.deleted_at')
            ->when($ctx->fieldIds !== null, fn ($q) => $q->whereIn('expenses.field_id', $ctx->fieldIds ?: [0]));
    }

    /** 12 months of invoiced, collected and approved expenses, ending with the selected month. */
    private static function trend(DashboardContext $ctx): array
    {
        $months = $ctx->months(12);
        $first = $months[0][0]->toDateString();
        $last = $ctx->month->copy()->endOfMonth()->toDateString();

        $inv = self::invoices($ctx)->whereBetween('invoices.invoice_month', [$first, $last])
            ->selectRaw('SUBSTR(invoices.invoice_month, 1, 7) as ym, SUM(invoices.total) as t')->groupBy('ym')->pluck('t', 'ym');

        $rec = DB::table('receipts')->leftJoin('clients', 'clients.id', '=', 'receipts.client_id')
            ->where('receipts.ho_status', 'approved')
            ->whereRaw(ReceiptReport::DATE_SQL . ' BETWEEN ? AND ?', [$first, $last])
            ->when($ctx->fieldIds !== null, fn ($q) => $q->whereIn('clients.field_id', $ctx->fieldIds ?: [0]))
            ->selectRaw(self::RECEIPT_MONTH_SQL . ' as ym, SUM(' . ReceiptReport::RECEIVED_SQL . ') as t')->groupBy('ym')->pluck('t', 'ym');

        $exp = self::approvedExpenses($ctx)->where('expenses.status_3', 'approved')
            ->whereBetween('expenses.created_at', [$first . ' 00:00:00', $last . ' 23:59:59'])
            ->selectRaw('SUBSTR(expenses.created_at, 1, 7) as ym, SUM(expenses.amount) as t')->groupBy('ym')->pluck('t', 'ym');

        $out = ['labels' => [], 'invoiced' => [], 'collected' => [], 'expenses' => []];
        foreach ($months as [, $ym, $label]) {
            $out['labels'][] = $label;
            $out['invoiced'][] = round((float) ($inv[$ym] ?? 0), 2);
            $out['collected'][] = round((float) ($rec[$ym] ?? 0), 2);
            $out['expenses'][] = round((float) ($exp[$ym] ?? 0), 2);
        }

        return $out;
    }

    private static function expenses(DashboardContext $ctx): array
    {
        [$from, $to] = $ctx->span();
        $window = [$from . ' 00:00:00', $to . ' 23:59:59'];

        $approved = (float) self::approvedExpenses($ctx)->where('expenses.status_3', 'approved')
            ->whereBetween('expenses.created_at', $window)->sum('expenses.amount');

        // Waiting for a decision (any date): nothing rejected yet and head office has not decided.
        $pending = self::approvedExpenses($ctx)->whereNull('expenses.status_3')
            ->where(fn ($q) => $q->whereNull('expenses.status_2')->orWhere('expenses.status_2', '!=', 'rejected'))
            ->where(fn ($q) => $q->whereNull('expenses.status_1')->orWhere('expenses.status_1', '!=', 'rejected'))
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(expenses.amount),0) as total')->first();

        $byType = self::approvedExpenses($ctx)->where('expenses.status_3', 'approved')
            ->whereBetween('expenses.created_at', $window)
            ->leftJoin('expense_types', 'expense_types.id', '=', 'expenses.expense_type_id')
            ->selectRaw("COALESCE(expense_types.name, 'Uncategorised') as name, COUNT(*) as cnt, SUM(expenses.amount) as total")
            ->groupBy('expense_types.name')->orderByDesc('total')->limit(8)->get()
            ->map(fn ($r) => ['label' => $r->name, 'value' => round((float) $r->total, 2), 'count' => (int) $r->cnt])->all();

        return [
            'approved' => round($approved, 2),
            'pending' => round((float) $pending->total, 2),
            'pending_count' => (int) $pending->cnt,
            'by_type' => $byType,
        ];
    }

    /** The month's payroll, as the salaries report counts it. */
    private static function payroll(DashboardContext $ctx): array
    {
        [$from, $to] = $ctx->span();
        $row = DB::table('salaries')->whereBetween('salary_month', [$from, $to])
            ->when($ctx->fieldIds !== null, fn ($q) => $q->whereIn('field_id', $ctx->fieldIds ?: [0]))
            ->selectRaw("
                COUNT(DISTINCT CASE WHEN payment_status IN ('pending','approved') THEN employee_id END) as headcount,
                COALESCE(SUM(CASE WHEN payment_status IN ('pending','approved') THEN net_salary END),0) as net,
                COALESCE(SUM(CASE WHEN payment_status IN ('pending','approved') THEN gross_salary END),0) as gross,
                COALESCE(SUM(CASE WHEN payment_status IN ('pending','approved') THEN cost_to_company END),0) as ctc,
                COALESCE(SUM(CASE WHEN payment_status = 'approved' THEN net_salary END),0) as paid,
                COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN net_salary END),0) as outstanding,
                COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as outstanding_count,
                COALESCE(SUM(CASE WHEN payment_status IN ('hold','rejected') THEN net_salary END),0) as held,
                COUNT(CASE WHEN payment_status IN ('hold','rejected') THEN 1 END) as held_count
            ")->first();

        $out = [];
        foreach ($row as $k => $v) {
            $out[$k] = str_ends_with($k, 'count') || $k === 'headcount' ? (int) $v : round((float) $v, 2);
        }
        $out['paid_pct'] = $out['net'] > 0 ? round($out['paid'] / $out['net'] * 100, 1) : null;
        $out['visible'] = $ctx->user->hasRole(['Invoice', 'Manager', 'Internal Auditor', 'Officer', 'Finance Manager', 'Director']);

        return $out;
    }

    /**
     * One row per office: invoiced, collected, receivable, expenses and payroll cost for the month,
     * and the office's contribution: service revenue (invoices.sub_amount, before levies and VAT)
     * less payroll cost to company and approved expenses. A management indicator, not profit:
     * head-office overheads and statutory items are not allocated.
     */
    private static function byOffice(DashboardContext $ctx, $agingRows): array
    {
        [$from, $to] = $ctx->span();
        $offices = $ctx->offices();
        if ($offices->isEmpty()) {
            return [];
        }

        $inv = self::invoices($ctx)->whereBetween('invoices.invoice_month', [$from, $to])
            ->selectRaw('clients.field_id as f, SUM(invoices.total) as t, SUM(COALESCE(invoices.sub_amount, 0)) as net')
            ->groupBy('clients.field_id')->get()->keyBy('f');

        $rec = DB::table('receipts')->leftJoin('clients', 'clients.id', '=', 'receipts.client_id')
            ->where('receipts.ho_status', 'approved')
            ->whereRaw(ReceiptReport::DATE_SQL . ' BETWEEN ? AND ?', [$from, $to])
            ->when($ctx->fieldIds !== null, fn ($q) => $q->whereIn('clients.field_id', $ctx->fieldIds ?: [0]))
            ->selectRaw('clients.field_id as f, SUM(' . ReceiptReport::RECEIVED_SQL . ') as t')->groupBy('clients.field_id')->pluck('t', 'f');

        $exp = self::approvedExpenses($ctx)->where('expenses.status_3', 'approved')
            ->whereBetween('expenses.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->selectRaw('expenses.field_id as f, SUM(expenses.amount) as t')->groupBy('expenses.field_id')->pluck('t', 'f');

        $pay = DB::table('salaries')->whereBetween('salary_month', [$from, $to])->whereIn('payment_status', ['pending', 'approved'])
            ->when($ctx->fieldIds !== null, fn ($q) => $q->whereIn('field_id', $ctx->fieldIds ?: [0]))
            ->selectRaw('field_id as f, SUM(cost_to_company) as t')->groupBy('field_id')->pluck('t', 'f');

        $clientField = DB::table('clients')->whereIn('id', collect($agingRows)->pluck('client_id')->all() ?: [0])->pluck('field_id', 'id');
        $owed = [];
        foreach ($agingRows as $r) {
            $f = (int) ($clientField[$r['client_id']] ?? 0);
            $owed[$f] = ($owed[$f] ?? 0) + $r['net'];
        }

        $rows = $offices->map(function ($o) use ($inv, $rec, $exp, $pay, $owed) {
            $row = [
                'id' => (int) $o->id,
                'name' => $o->name,
                'invoiced' => round((float) ($inv[$o->id]->t ?? 0), 2),
                'revenue' => round((float) ($inv[$o->id]->net ?? 0), 2),
                'collected' => round((float) ($rec[$o->id] ?? 0), 2),
                'receivable' => round((float) ($owed[$o->id] ?? 0), 2),
                'expenses' => round((float) ($exp[$o->id] ?? 0), 2),
                'payroll' => round((float) ($pay[$o->id] ?? 0), 2),
            ];
            $row['rate'] = $row['invoiced'] > 0 ? round($row['collected'] / $row['invoiced'] * 100, 1) : null;
            // Service revenue before NHIL / GETFund / COVID levy and VAT, less the month's payroll and expenses.
            $row['contribution'] = round($row['revenue'] - $row['payroll'] - $row['expenses'], 2);
            $row['margin'] = $row['revenue'] > 0 ? round($row['contribution'] / $row['revenue'] * 100, 1) : null;

            return $row;
        })->sortByDesc('invoiced')->values()->all();

        return $rows;
    }
}
