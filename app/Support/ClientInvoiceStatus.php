<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Has the client paid its invoice for a month, and when?
 *
 * The ONE place that answers this. The category page, the bank / cash payroll
 * screens and the bank Excel export all read it, so they always agree.
 *
 * Source of truth (what the invoice screens already show):
 *   invoices.status   unpaid -> uncompleted (part paid) -> completed (fully paid),
 *                     set by ReceiptController as soon as a receipt is entered.
 *   invoices.due_date the due date typed on the invoice.
 *   receipts.receipt_month  the payment date typed on the receipt
 *                     (falls back to the receipt's created_at when empty).
 *
 * A client can have more than one invoice in a month; they are combined:
 * paid only when every invoice is completed, paid on = the last payment,
 * due = the earliest due date.
 *
 * Invoices are matched on invoice_month = the payroll month (the same rule
 * SalaryController::store() uses to reject guards whose client has no invoice).
 */
class ClientInvoiceStatus
{
    public const PAID = 'paid';          // fully paid by the due date (+ grace)
    public const PAID_LATE = 'paid_late'; // fully paid, after the due date (+ grace)
    public const PART = 'part';          // something received, balance still open
    public const UNPAID = 'unpaid';      // nothing received
    public const NONE = 'none';          // no invoice for the month

    /** Days after the due date that still count as "on time" (bank transfers clear slowly). */
    public const GRACE_DAYS = 7;

    /** Months of history used for a client's payment record. */
    public const HISTORY_MONTHS = 6;

    public const LABELS = [
        self::PAID => 'Paid',
        self::PAID_LATE => 'Paid late',
        self::PART => 'Part paid',
        self::UNPAID => 'Unpaid',
        self::NONE => 'No invoice',
    ];

    /** Payment record over the last months: what kind of payer is this client? */
    public const RECORD_ON_TIME = 'on_time';
    public const RECORD_LATE = 'late';
    public const RECORD_OWING = 'owing';
    public const RECORD_NONE = 'none';

    public const RECORD_LABELS = [
        self::RECORD_ON_TIME => 'Pays on time',
        self::RECORD_LATE => 'Pays late',
        self::RECORD_OWING => 'Owing',
        self::RECORD_NONE => 'No invoices',
    ];

    /**
     * Month-by-month status for the HISTORY_MONTHS months ending at $month, plus a record.
     *
     * @param  int[]|null  $clientIds  null = every client invoiced in the window
     * @return array<int, array{months: array<string, array>, record: array}>
     */
    public static function history(?array $clientIds, CarbonInterface $month, int $months = self::HISTORY_MONTHS, ?CarbonInterface $today = null): array
    {
        $to = Carbon::instance($month)->startOfMonth();
        $from = $to->copy()->subMonths($months - 1);
        $loaded = self::load($clientIds, $from, $to, $today);

        $ids = $clientIds === null ? array_keys($loaded) : array_map('intval', $clientIds);
        $out = [];
        foreach ($ids as $clientId) {
            $series = [];
            for ($m = $from->copy(); $m <= $to; $m->addMonth()) {
                $series[$m->format('Y-m')] = $loaded[$clientId][$m->format('Y-m')] ?? self::none($m);
            }
            $out[$clientId] = ['months' => $series, 'record' => self::record($series)];
        }

        return $out;
    }

    /** Every invoice of one client for the last $months months (newest first), for the client panel. */
    public static function invoices(int $clientId, CarbonInterface $month, int $months = 12, ?CarbonInterface $today = null): array
    {
        $to = Carbon::instance($month)->startOfMonth();
        $from = $to->copy()->subMonths($months - 1);

        return self::invoiceRows([$clientId], $from, $to)
            ->map(fn ($r) => self::single($r, $today))
            ->sortByDesc(fn ($r) => $r['month'] . sprintf('%012d', $r['invoice_id']))
            ->values()->all();
    }

    /* ------------------------------------------------------------------
     | Loading
     * ------------------------------------------------------------------ */

    /** One query: invoices in the window with their receipts summed per invoice. */
    private static function invoiceRows(?array $clientIds, Carbon $from, Carbon $to)
    {
        // Linked through receipt_allocations so a receipt that pays several invoices
        // counts for each of them (receipts.invoice_id only holds the first one).
        // Credit applied later counts as paid on the day it was applied.
        $paidOn = "CASE WHEN receipt_allocations.source = 'credit' THEN DATE(receipt_allocations.created_at)
                ELSE COALESCE(receipts.receipt_month, DATE(receipts.created_at)) END";
        $receipts = DB::table('receipt_allocations')
            ->join('receipts', 'receipts.id', '=', 'receipt_allocations.receipt_id')
            ->selectRaw("receipt_allocations.invoice_id, COUNT(DISTINCT receipt_allocations.receipt_id) as receipt_count,
                MIN($paidOn) as first_paid_on,
                MAX($paidOn) as last_paid_on")
            ->groupBy('receipt_allocations.invoice_id');

        return DB::table('invoices')
            ->leftJoinSub($receipts, 'r', 'r.invoice_id', '=', 'invoices.id')
            ->whereBetween('invoices.invoice_month', [$from->toDateString(), $to->copy()->endOfMonth()->toDateString()])
            ->when($clientIds !== null, fn ($q) => $q->whereIn('invoices.client_id', array_map('intval', $clientIds) ?: [0]))
            ->whereNotNull('invoices.client_id')
            ->get([
                'invoices.id', 'invoices.client_id', 'invoices.invoice_month', 'invoices.due_date',
                'invoices.status', 'invoices.total', 'invoices.balance', 'invoices.amount_received', 'invoices.wht_amount',
                'r.receipt_count', 'r.first_paid_on', 'r.last_paid_on',
            ]);
    }

    /** @return array<int, array<string, array>> client_id => 'Y-m' => combined status */
    private static function load(?array $clientIds, Carbon $from, Carbon $to, ?CarbonInterface $today): array
    {
        $grouped = [];
        foreach (self::invoiceRows($clientIds, $from, $to) as $row) {
            $ym = substr((string) $row->invoice_month, 0, 7);
            $grouped[(int) $row->client_id][$ym][] = self::single($row, $today);
        }

        $out = [];
        foreach ($grouped as $clientId => $months) {
            foreach ($months as $ym => $invoices) {
                $out[$clientId][$ym] = self::combine($invoices, $today);
            }
        }

        return $out;
    }

    /* ------------------------------------------------------------------
     | Status rules
     * ------------------------------------------------------------------ */

    /** Status of one invoice row. */
    private static function single(object $row, ?CarbonInterface $today): array
    {
        $today = Carbon::instance($today ?? now())->startOfDay();
        $month = Carbon::parse($row->invoice_month)->startOfMonth();
        $total = (float) $row->total;
        $state = strtolower(trim((string) $row->status));

        // Old invoices without a due date: treat as due at the end of the following month.
        $due = $row->due_date ? Carbon::parse($row->due_date)->startOfDay() : $month->copy()->addMonth()->endOfMonth()->startOfDay();
        $paidOn = $row->last_paid_on ? Carbon::parse($row->last_paid_on)->startOfDay() : null;

        if ($state === 'completed') {
            $outstanding = 0.0;
            $paidOn ??= $due; // completed without a receipt date: nothing better to go on
            $daysLate = (int) $due->diffInDays($paidOn, false);
            $status = $daysLate > self::GRACE_DAYS ? self::PAID_LATE : self::PAID;
        } elseif ($state === 'uncompleted' || (int) $row->receipt_count > 0) {
            // Part payment: ReceiptController keeps the remaining amount in invoices.balance.
            $outstanding = (float) $row->balance > 0 ? (float) $row->balance : $total;
            $daysLate = $today->gt($due) ? (int) $due->diffInDays($today) : 0;
            $status = self::PART;
        } else {
            // 'unpaid' (new invoice): invoices.balance is 0 here, so the whole total is owed.
            $outstanding = $total;
            $paidOn = null;
            $daysLate = $today->gt($due) ? (int) $due->diffInDays($today) : 0;
            $status = self::UNPAID;
        }

        return [
            'status' => $status,
            'invoice_id' => (int) $row->id,
            'invoice_ids' => [(int) $row->id],
            'month' => $month->format('Y-m'),
            'invoiced' => $total,
            'outstanding' => round($outstanding, 2),
            'due' => $due->toDateString(),
            'paid_on' => $paidOn?->toDateString(),
            'first_paid_on' => $row->first_paid_on ? Carbon::parse($row->first_paid_on)->toDateString() : null,
            // paid: days after due (negative = early). open: days overdue (0 = not due yet).
            'days_late' => $daysLate,
            'overdue' => in_array($status, [self::PART, self::UNPAID], true) && $daysLate > 0,
            'count' => 1,
        ];
    }

    /** Several invoices of one client in one month -> one status. */
    private static function combine(array $invoices, ?CarbonInterface $today): array
    {
        if (count($invoices) === 1) {
            return $invoices[0];
        }

        $open = array_filter($invoices, fn ($i) => in_array($i['status'], [self::PART, self::UNPAID], true));
        $received = array_filter($invoices, fn ($i) => $i['status'] !== self::UNPAID);
        $dues = array_column($invoices, 'due');
        $paidDates = array_filter(array_column($invoices, 'paid_on'));
        $firstDates = array_filter(array_column($invoices, 'first_paid_on'));

        $due = min($dues);
        $paidOn = $paidDates ? max($paidDates) : null;
        $todayStr = Carbon::instance($today ?? now())->toDateString();

        if (! $open) {
            $daysLate = max(array_column($invoices, 'days_late'));
            $status = $daysLate > self::GRACE_DAYS ? self::PAID_LATE : self::PAID;
        } else {
            $status = $received ? self::PART : self::UNPAID;
            $daysLate = max(array_column($open, 'days_late'));
            $paidOn = $status === self::PART ? $paidOn : null;
        }

        return [
            'status' => $status,
            'invoice_id' => $invoices[0]['invoice_id'],
            'invoice_ids' => array_merge(...array_column($invoices, 'invoice_ids')),
            'month' => $invoices[0]['month'],
            'invoiced' => array_sum(array_column($invoices, 'invoiced')),
            'outstanding' => round(array_sum(array_column($invoices, 'outstanding')), 2),
            'due' => $due,
            'paid_on' => $paidOn,
            'first_paid_on' => $firstDates ? min($firstDates) : null,
            'days_late' => $daysLate,
            'overdue' => (bool) $open && $todayStr > $due,
            'count' => count($invoices),
        ];
    }

    private static function none(CarbonInterface $month): array
    {
        return [
            'status' => self::NONE, 'invoice_id' => null, 'invoice_ids' => [], 'month' => $month->format('Y-m'),
            'invoiced' => 0.0, 'outstanding' => 0.0, 'due' => null, 'paid_on' => null, 'first_paid_on' => null,
            'days_late' => 0, 'overdue' => false, 'count' => 0,
        ];
    }

    /**
     * What kind of payer: owing (an overdue invoice is still open), late (everything due is
     * paid but on average after due + grace), on time, or no invoices in the window.
     * Invoices that are open but not yet due do not count against the client.
     */
    public static function record(array $series): array
    {
        $invoiced = array_filter($series, fn ($s) => $s['status'] !== self::NONE);
        $owing = array_filter($invoiced, fn ($s) => $s['overdue']);
        $paid = array_filter($invoiced, fn ($s) => in_array($s['status'], [self::PAID, self::PAID_LATE], true));
        $late = array_filter($paid, fn ($s) => $s['status'] === self::PAID_LATE);
        $avgDays = $paid ? (int) round(array_sum(array_column($paid, 'days_late')) / count($paid)) : null;

        $grade = match (true) {
            ! $invoiced => self::RECORD_NONE,
            (bool) $owing => self::RECORD_OWING,
            ! $paid => self::RECORD_NONE, // only invoices that are not due yet
            $avgDays > self::GRACE_DAYS || count($late) * 2 > count($paid) => self::RECORD_LATE,
            default => self::RECORD_ON_TIME,
        };

        return [
            'grade' => $grade,
            'label' => self::RECORD_LABELS[$grade],
            'invoiced' => count($invoiced),
            'paid' => count($paid),
            'paid_late' => count($late),
            'owing' => count($owing),
            'owing_amount' => round(array_sum(array_column($owing, 'outstanding')), 2),
            'avg_days' => $avgDays,
            'summary' => self::recordSummary($grade, count($invoiced), count($paid), count($owing), $avgDays, array_sum(array_column($owing, 'outstanding'))),
        ];
    }

    private static function recordSummary(string $grade, int $invoiced, int $paid, int $owing, ?int $avg, float $owed): string
    {
        if ($grade === self::RECORD_NONE) {
            return $invoiced ? 'Invoiced, not due yet' : 'No invoices in the last ' . self::HISTORY_MONTHS . ' months';
        }
        $text = "Paid {$paid} of {$invoiced} invoices";
        if ($avg !== null) {
            $text .= ', ' . self::daysText($avg, 'on average');
        }
        if ($owing) {
            $text .= "; {$owing} overdue (GH₵ " . number_format($owed, 2) . ')';
        }

        return $text;
    }

    private static function daysText(int $days, string $suffix = ''): string
    {
        $suffix = $suffix !== '' ? ' ' . $suffix : '';
        if ($days === 0) {
            return 'on the due date' . $suffix;
        }

        return abs($days) . ' day' . (abs($days) === 1 ? '' : 's') . ($days > 0 ? ' after' : ' before') . ' due' . $suffix;
    }

    /* ------------------------------------------------------------------
     | Presentation (shared by Blade, JSON endpoints and the Excel export)
     * ------------------------------------------------------------------ */

    /** Short status word for a cell / filter: Paid, Paid late, Part paid, Unpaid, Overdue, No invoice. */
    public static function label(array $s): string
    {
        if ($s['status'] === self::UNPAID && $s['overdue']) {
            return 'Overdue';
        }

        return self::LABELS[$s['status']];
    }

    /** One plain-text line saying when: "Paid 14 Sep 2026, 5 days after due". */
    public static function detail(array $s): string
    {
        $date = fn (?string $d) => $d ? Carbon::parse($d)->format('d M Y') : '';
        $money = fn ($v) => 'GH₵ ' . number_format((float) $v, 2);

        return match ($s['status']) {
            self::NONE => 'No invoice for ' . Carbon::parse($s['month'] . '-01')->format('M Y'),
            self::PAID, self::PAID_LATE => 'Paid ' . $date($s['paid_on']) . ', ' . self::daysText($s['days_late']),
            self::PART => 'Part paid (last ' . $date($s['paid_on']) . '), ' . $money($s['outstanding']) . ' owed, '
                . ($s['overdue'] ? $s['days_late'] . ' days overdue' : 'due ' . $date($s['due'])),
            default => $money($s['outstanding']) . ' unpaid, '
                . ($s['overdue'] ? $s['days_late'] . ' days overdue (due ' . $date($s['due']) . ')' : 'due ' . $date($s['due'])),
        };
    }

    /** Value for DataTables data-search, so chips can filter a column exactly. */
    public static function searchKey(array $s): string
    {
        return 'inv-' . ($s['status'] === self::UNPAID && $s['overdue'] ? 'overdue' : $s['status']);
    }

    /** Badge colour per status. */
    public static function color(array $s): string
    {
        return match (true) {
            $s['status'] === self::PAID => 'success',
            $s['status'] === self::PAID_LATE => 'info',
            $s['status'] === self::PART => 'warning',
            $s['status'] === self::UNPAID && $s['overdue'] => 'danger',
            $s['status'] === self::UNPAID => 'secondary',
            default => 'dark',
        };
    }

    /** Badge HTML with the detail as a tooltip and an optional second line. */
    public static function badge(array $s, bool $withDetail = true): string
    {
        $detail = e(self::detail($s));
        $html = '<span class="badge bg-label-' . self::color($s) . ' inv-flag" title="' . $detail . '" data-bs-toggle="tooltip">'
            . e(self::label($s)) . '</span>';

        if ($withDetail && $s['status'] !== self::NONE) {
            $html .= '<div class="small text-muted text-wrap" style="min-width:11rem">' . $detail . '</div>';
        }

        return $html;
    }

    /** Badge for a payment record (category page). */
    public static function recordBadge(array $record): string
    {
        $color = [self::RECORD_ON_TIME => 'success', self::RECORD_LATE => 'warning', self::RECORD_OWING => 'danger', self::RECORD_NONE => 'secondary'][$record['grade']];

        return '<span class="badge bg-' . $color . ($color === 'warning' ? ' text-dark' : '') . '" title="' . e($record['summary']) . '" data-bs-toggle="tooltip">'
            . e($record['label']) . '</span>';
    }

    /** Six small squares, oldest -> newest, each with its own tooltip. */
    public static function strip(array $months): string
    {
        $html = '<span class="inv-strip" aria-label="Invoice payments by month">';
        foreach ($months as $ym => $s) {
            $title = Carbon::parse($ym . '-01')->format('M Y') . ': ' . self::label($s)
                . ($s['status'] === self::NONE ? '' : ' - ' . self::detail($s));
            $html .= '<span class="inv-sq inv-sq-' . self::color($s) . ($s['status'] === self::NONE ? ' inv-sq-none' : '')
                . '" title="' . e($title) . '" data-bs-toggle="tooltip">' . e(Carbon::parse($ym . '-01')->format('M')[0]) . '</span>';
        }

        return $html . '</span>';
    }
}
