<?php

namespace App\Support\Dashboards;

use App\Models\Bank;
use App\Services\Receipts\ReceivablesAging;
use App\Support\ReceiptReport;
use Illuminate\Support\Facades\DB;

/**
 * Accounts dashboard: billing, collections, receivables, spend and payroll for a period.
 *
 * Definitions (each one reuses the rule the matching report already uses, so the
 * dashboard and the reports always agree):
 *  - Invoiced:   invoices.total for invoices whose invoice_month is one of the months the
 *                period touches (invoices are monthly: a week shows its month's invoices).
 *  - Collected:  money received on head-office APPROVED receipts dated in the period
 *                (ReceiptReport: cash + MoMo + cheque + transfer + other; receipt date
 *                = receipt_month, falling back to the entry date).
 *  - Collection rate: collected ÷ invoiced, only when the period is whole months
 *                (a day's receipts against a month's invoices means nothing).
 *  - Receivable: ReceivablesAging (invoice total − everything settled against it,
 *                aged from the due date), less unused client credit. It is "as of today":
 *                it does not change with the period.
 *  - Expenses:   expenses approved at head office (stage 3), by the date they were entered.
 *  - Payroll:    salaries of the period's months in a live status (pending / approved), as on
 *                the salaries report; paid = approved.
 *
 * Column aliases are plain words that are not reserved in MySQL / MariaDB / SQLite
 * (a reserved alias such as "terminated" breaks MariaDB).
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

    public static function build(DashboardContext $ctx): array
    {
        $prev = $ctx->shifted(1);

        $receipts = self::receiptReport($ctx);
        $collected = $receipts->totals();
        $prevCollected = self::receiptReport($prev)->totals();

        $invoiced = self::invoiced($ctx);
        $prevInvoiced = self::invoiced($prev);

        $aging = (new ReceivablesAging())->build($ctx->fieldIds);
        $trend = self::trend($ctx);

        // Debtor days: what is owed now, in days of billing over the last 3 months.
        [$billFrom, $billTo] = [now()->startOfMonth()->subMonthsNoOverflow(3)->toDateString(), now()->startOfMonth()->subDay()->toDateString()];
        $recentBilling = (float) self::invoices($ctx)->whereBetween('invoices.invoice_month', [$billFrom, $billTo])->sum('invoices.total');
        $avgDaily = $recentBilling / 91;
        $debtorDays = $avgDaily > 0 ? (int) round(max(0, $aging['totals']['net']) / $avgDaily) : null;

        $expenses = self::expenses($ctx);
        $prevExpenses = self::expenseTotal($prev);
        $comparable = $ctx->isWholeMonths();

        return [
            'kpi' => [
                'invoiced' => $invoiced['total'],
                'invoiced_count' => $invoiced['count'],
                'invoiced_prev' => $prevInvoiced['total'],
                'invoiced_months' => $ctx->monthsLabel(),
                'collected' => $collected->received,
                'collected_count' => (int) $collected->cnt,
                'collected_prev' => $prevCollected->received,
                'collection_rate' => $comparable && $invoiced['total'] > 0 ? round($collected->received / $invoiced['total'] * 100, 1) : null,
                'receivable' => $aging['totals']['net'],
                'receivable_owed' => $aging['totals']['owed'],
                'credit' => $aging['totals']['credit'],
                'overdue_90' => $aging['totals']['d90plus'],
                'debtor_days' => $debtorDays,
                'awaiting' => $receipts->awaitingApproval(),
                'expenses' => $expenses['approved'],
                'expenses_prev' => $prevExpenses,
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
            'payroll' => self::payroll($ctx),
            'banks' => $ctx->user->hasRole(['Finance Manager', 'Director'])
                ? Bank::orderBy('name')->get(['id', 'name', 'kind', 'branch', 'acc_number', 'total'])
                : collect(),
        ];
    }

    private static function receiptReport(DashboardContext $ctx): ReceiptReport
    {
        return new ReceiptReport($ctx->fieldIds, 'custom', $ctx->from->copy(), $ctx->to->copy(), $ctx->from->copy());
    }

    /** Invoices for the months the period touches: total, count, and how many have nothing paid yet. */
    private static function invoiced(DashboardContext $ctx): array
    {
        $row = self::invoices($ctx)
            ->whereBetween('invoices.invoice_month', $ctx->monthSpan())
            ->selectRaw("COUNT(*) as cnt, COALESCE(SUM(invoices.total),0) as total_amount,
                SUM(CASE WHEN invoices.status = 'unpaid' THEN 1 ELSE 0 END) as unpaid_cnt")
            ->first();

        return ['total' => round((float) $row->total_amount, 2), 'count' => (int) $row->cnt, 'unpaid_count' => (int) $row->unpaid_cnt];
    }

    private static function invoices(DashboardContext $ctx)
    {
        return DB::table('invoices')->join('clients', 'clients.id', '=', 'invoices.client_id')
            ->when($ctx->fieldIds !== null, fn ($q) => $q->whereIn('clients.field_id', $ctx->fieldIds ?: [0]));
    }

    private static function expenseBase(DashboardContext $ctx)
    {
        return DB::table('expenses')->whereNull('expenses.deleted_at')
            ->when($ctx->fieldIds !== null, fn ($q) => $q->whereIn('expenses.field_id', $ctx->fieldIds ?: [0]));
    }

    private static function expenseTotal(DashboardContext $ctx): float
    {
        return round((float) self::expenseBase($ctx)->where('expenses.status_3', 'approved')
            ->whereBetween('expenses.created_at', $ctx->dateTimeSpan())->sum('expenses.amount'), 2);
    }

    /**
     * Trend over the period's buckets (see DashboardContext::trendBuckets): collected and approved
     * expenses per bucket; invoiced too when the buckets are whole months (invoices are monthly).
     */
    private static function trend(DashboardContext $ctx): array
    {
        $buckets = $ctx->trendBuckets();
        $first = $buckets[0][0]->toDateString();
        $last = end($buckets)[1]->toDateString();

        $rec = DB::table('receipts')->leftJoin('clients', 'clients.id', '=', 'receipts.client_id')
            ->where('receipts.ho_status', 'approved')
            ->whereRaw(ReceiptReport::DATE_SQL . ' BETWEEN ? AND ?', [$first, $last])
            ->when($ctx->fieldIds !== null, fn ($q) => $q->whereIn('clients.field_id', $ctx->fieldIds ?: [0]))
            ->selectRaw('DATE(' . ReceiptReport::DATE_SQL . ') as day_key, SUM(' . ReceiptReport::RECEIVED_SQL . ') as amount')
            ->groupBy('day_key')->pluck('amount', 'day_key')->all();

        $exp = self::expenseBase($ctx)->where('expenses.status_3', 'approved')
            ->whereBetween('expenses.created_at', [$first . ' 00:00:00', $last . ' 23:59:59'])
            ->selectRaw('DATE(expenses.created_at) as day_key, SUM(expenses.amount) as amount')
            ->groupBy('day_key')->pluck('amount', 'day_key')->all();

        $out = [
            'labels' => array_column($buckets, 2),
            'caption' => $ctx->trendCaption(),
            'collected' => DashboardContext::foldIntoBuckets($rec, $buckets),
            'expenses' => DashboardContext::foldIntoBuckets($exp, $buckets),
            'invoiced' => null,
        ];

        if ($ctx->trendIsMonthly()) {
            $inv = self::invoices($ctx)->whereBetween('invoices.invoice_month', [$first, $last])
                ->selectRaw('invoices.invoice_month as day_key, SUM(invoices.total) as amount')
                ->groupBy('invoices.invoice_month')->pluck('amount', 'day_key')->all();
            $out['invoiced'] = DashboardContext::foldIntoBuckets($inv, $buckets);
        }

        return $out;
    }

    private static function expenses(DashboardContext $ctx): array
    {
        // Waiting for a decision (any date): nothing rejected yet and head office has not decided.
        $pending = self::expenseBase($ctx)->whereNull('expenses.status_3')
            ->where(fn ($q) => $q->whereNull('expenses.status_2')->orWhere('expenses.status_2', '!=', 'rejected'))
            ->where(fn ($q) => $q->whereNull('expenses.status_1')->orWhere('expenses.status_1', '!=', 'rejected'))
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(expenses.amount),0) as amount')->first();

        $byType = self::expenseBase($ctx)->where('expenses.status_3', 'approved')
            ->whereBetween('expenses.created_at', $ctx->dateTimeSpan())
            ->leftJoin('expense_types', 'expense_types.id', '=', 'expenses.expense_type_id')
            ->selectRaw("COALESCE(expense_types.name, 'Uncategorised') as type_name, COUNT(*) as cnt, SUM(expenses.amount) as amount")
            ->groupBy('expense_types.name')->orderByDesc('amount')->limit(8)->get()
            ->map(fn ($r) => ['label' => $r->type_name, 'value' => round((float) $r->amount, 2), 'count' => (int) $r->cnt])->all();

        return [
            'approved' => self::expenseTotal($ctx),
            'pending' => round((float) $pending->amount, 2),
            'pending_count' => (int) $pending->cnt,
            'by_type' => $byType,
        ];
    }

    /** Payroll for the months the period touches, as the salaries report counts it. */
    private static function payroll(DashboardContext $ctx): array
    {
        $row = DB::table('salaries')->whereBetween('salary_month', $ctx->monthSpan())
            ->when($ctx->fieldIds !== null, fn ($q) => $q->whereIn('field_id', $ctx->fieldIds ?: [0]))
            ->selectRaw("
                COUNT(DISTINCT CASE WHEN payment_status IN ('pending','approved') THEN employee_id END) as people,
                COALESCE(SUM(CASE WHEN payment_status IN ('pending','approved') THEN net_salary END),0) as net_pay,
                COALESCE(SUM(CASE WHEN payment_status IN ('pending','approved') THEN gross_salary END),0) as gross_pay,
                COALESCE(SUM(CASE WHEN payment_status IN ('pending','approved') THEN cost_to_company END),0) as ctc_amount,
                COALESCE(SUM(CASE WHEN payment_status = 'approved' THEN net_salary END),0) as paid_amount,
                COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN net_salary END),0) as due_amount,
                COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as due_cnt,
                COALESCE(SUM(CASE WHEN payment_status IN ('hold','rejected') THEN net_salary END),0) as held_amount,
                COUNT(CASE WHEN payment_status IN ('hold','rejected') THEN 1 END) as held_cnt
            ")->first();

        $out = [
            'headcount' => (int) $row->people,
            'net' => round((float) $row->net_pay, 2),
            'gross' => round((float) $row->gross_pay, 2),
            'ctc' => round((float) $row->ctc_amount, 2),
            'paid' => round((float) $row->paid_amount, 2),
            'outstanding' => round((float) $row->due_amount, 2),
            'outstanding_count' => (int) $row->due_cnt,
            'held' => round((float) $row->held_amount, 2),
            'held_count' => (int) $row->held_cnt,
            'months' => $ctx->monthsLabel(),
        ];
        $out['paid_pct'] = $out['net'] > 0 ? round($out['paid'] / $out['net'] * 100, 1) : null;
        $out['visible'] = $ctx->user->hasRole(['Invoice', 'Manager', 'Internal Auditor', 'Officer', 'Finance Manager', 'Director']);

        return $out;
    }

    /**
     * One row per office: invoiced, collected, receivable, expenses and payroll cost, and the
     * office's contribution: service revenue (invoices.sub_amount, before levies and VAT) less
     * payroll cost to company and approved expenses. A management indicator, not profit:
     * head-office overheads and statutory items are not allocated.
     *
     * Invoices, payroll and the expenses used for the contribution all cover the months the
     * period touches, so they compare like with like; "collected" is the period itself.
     */
    private static function byOffice(DashboardContext $ctx, $agingRows): array
    {
        $offices = $ctx->offices();
        if ($offices->isEmpty()) {
            return [];
        }
        [$mFrom, $mTo] = $ctx->monthSpan();

        $inv = self::invoices($ctx)->whereBetween('invoices.invoice_month', [$mFrom, $mTo])
            ->selectRaw('clients.field_id as office_id, SUM(invoices.total) as amount, SUM(COALESCE(invoices.sub_amount, 0)) as net_amount')
            ->groupBy('clients.field_id')->get()->keyBy('office_id');

        $rec = DB::table('receipts')->leftJoin('clients', 'clients.id', '=', 'receipts.client_id')
            ->where('receipts.ho_status', 'approved')
            ->whereRaw(ReceiptReport::DATE_SQL . ' BETWEEN ? AND ?', $ctx->span())
            ->when($ctx->fieldIds !== null, fn ($q) => $q->whereIn('clients.field_id', $ctx->fieldIds ?: [0]))
            ->selectRaw('clients.field_id as office_id, SUM(' . ReceiptReport::RECEIVED_SQL . ') as amount')
            ->groupBy('clients.field_id')->pluck('amount', 'office_id');

        $exp = self::expenseBase($ctx)->where('expenses.status_3', 'approved')
            ->whereBetween('expenses.created_at', [$mFrom . ' 00:00:00', $mTo . ' 23:59:59'])
            ->selectRaw('expenses.field_id as office_id, SUM(expenses.amount) as amount')
            ->groupBy('expenses.field_id')->pluck('amount', 'office_id');

        $pay = DB::table('salaries')->whereBetween('salary_month', [$mFrom, $mTo])->whereIn('payment_status', ['pending', 'approved'])
            ->when($ctx->fieldIds !== null, fn ($q) => $q->whereIn('field_id', $ctx->fieldIds ?: [0]))
            ->selectRaw('field_id as office_id, SUM(cost_to_company) as amount')->groupBy('field_id')->pluck('amount', 'office_id');

        $clientField = DB::table('clients')->whereIn('id', collect($agingRows)->pluck('client_id')->all() ?: [0])->pluck('field_id', 'id');
        $owed = [];
        foreach ($agingRows as $r) {
            $f = (int) ($clientField[$r['client_id']] ?? 0);
            $owed[$f] = ($owed[$f] ?? 0) + $r['net'];
        }

        $comparable = $ctx->isWholeMonths();

        return $offices->map(function ($o) use ($inv, $rec, $exp, $pay, $owed, $comparable) {
            $row = [
                'id' => (int) $o->id,
                'name' => $o->name,
                'invoiced' => round((float) ($inv[$o->id]->amount ?? 0), 2),
                'revenue' => round((float) ($inv[$o->id]->net_amount ?? 0), 2),
                'collected' => round((float) ($rec[$o->id] ?? 0), 2),
                'receivable' => round((float) ($owed[$o->id] ?? 0), 2),
                'expenses' => round((float) ($exp[$o->id] ?? 0), 2),
                'payroll' => round((float) ($pay[$o->id] ?? 0), 2),
            ];
            $row['rate'] = $comparable && $row['invoiced'] > 0 ? round($row['collected'] / $row['invoiced'] * 100, 1) : null;
            $row['contribution'] = round($row['revenue'] - $row['payroll'] - $row['expenses'], 2);
            $row['margin'] = $row['revenue'] > 0 ? round($row['contribution'] / $row['revenue'] * 100, 1) : null;

            return $row;
        })->sortByDesc('invoiced')->values()->all();
    }
}
