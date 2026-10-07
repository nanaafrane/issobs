<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Receipts report: the reporting window, and every figure on the report page and
 * its drill-downs. One class so the page, the drill-downs and the comparison all
 * use the same window, the same money definition and the same field-office scope.
 *
 * Money received = cash + MoMo + cheque + transfer + other, as entered on the
 * receipt. NOT receipts.amount_received: ReceiptController only fills that column
 * when withholding tax is ticked (NULL on create, 0.00 on update otherwise), so
 * summing it silently dropped every receipt without WHT.
 *
 * Receipt date = receipts.receipt_month (the payment date typed on the receipt),
 * falling back to the date the receipt was entered when it is empty, so undated
 * receipts are not invisible.
 *
 * Only head-office approved receipts count; receipts still awaiting approval are
 * reported separately.
 */
class ReceiptReport
{
    public const PERIODS = [
        'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly',
        'semiannual' => 'Half-year', 'yearly' => 'Yearly', 'custom' => 'Date range',
    ];

    /** Longest custom range accepted (keeps the trend and queries bounded). */
    public const MAX_RANGE_DAYS = 1830;

    /** Effective receipt date (SQL). */
    public const DATE_SQL = 'COALESCE(receipts.receipt_month, DATE(receipts.created_at))';

    /** Money actually received (SQL). */
    public const RECEIVED_SQL = '(COALESCE(receipts.cash_amount,0) + COALESCE(receipts.momo_amount,0) + COALESCE(receipts.cheque_amount,0) + COALESCE(receipts.transfer_amount,0) + COALESCE(receipts.other_payment_amnt,0))';

    public string $period;
    public Carbon $from;   // start of first day
    public Carbon $to;     // end of last day
    public Carbon $anchor; // the date the named period was built around
    public int $days;

    /** @param int[]|null $fieldIds null = every field office (see ReceiptController::reportAllowedFieldIds) */
    public function __construct(public ?array $fieldIds, string $period, Carbon $from, Carbon $to, Carbon $anchor)
    {
        $this->period = $period;
        $this->from = $from->copy()->startOfDay();
        $this->to = $to->copy()->endOfDay();
        $this->anchor = $anchor;
        $this->days = (int) $this->from->copy()->startOfDay()->diffInDays($this->to->copy()->startOfDay()) + 1;
    }

    /* ------------------------------------------------------------------
     | Window
     * ------------------------------------------------------------------ */

    /**
     * Read the window from the request: ?period=<named>&date=Y-m-d, or
     * ?period=custom&from=Y-m-d&to=Y-m-d. Bad or missing input falls back
     * to this month instead of a server error.
     */
    public static function fromRequest(Request $request, ?array $fieldIds): self
    {
        $period = array_key_exists((string) $request->input('period'), self::PERIODS) ? (string) $request->input('period') : 'monthly';
        $today = Carbon::today();

        if ($period === 'custom') {
            $from = self::date($request->input('from'));
            $to = self::date($request->input('to'));
            if ($from && $to) {
                if ($from->gt($to)) {
                    [$from, $to] = [$to, $from]; // entered the wrong way round
                }
                if ($from->diffInDays($to) >= self::MAX_RANGE_DAYS) {
                    $from = $to->copy()->subDays(self::MAX_RANGE_DAYS - 1);
                }

                return new self($fieldIds, 'custom', $from, $to, $to);
            }
            $period = 'monthly'; // incomplete range: show this month
        }

        $anchor = self::date($request->input('date')) ?? $today;
        [$from, $to] = self::namedWindow($period, $anchor);

        return new self($fieldIds, $period, $from, $to, $anchor);
    }

    private static function date($value): ?Carbon
    {
        $value = trim((string) $value);
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        try {
            $d = Carbon::createFromFormat('!Y-m-d', $value);

            return $d && $d->format('Y-m-d') === $value ? $d : null; // rejects 2026-02-31
        } catch (\Throwable) {
            return null;
        }
    }

    /** Calendar window for a named period containing $anchor. */
    public static function namedWindow(string $period, Carbon $anchor): array
    {
        $a = $anchor->copy();

        return match ($period) {
            'daily' => [$a->copy()->startOfDay(), $a->copy()->endOfDay()],
            'weekly' => [$a->copy()->startOfWeek(), $a->copy()->endOfWeek()],
            'quarterly' => [$a->copy()->startOfQuarter(), $a->copy()->endOfQuarter()],
            'semiannual' => $a->month <= 6
                ? [$a->copy()->startOfYear(), $a->copy()->startOfYear()->addMonths(5)->endOfMonth()]
                : [$a->copy()->startOfYear()->addMonths(6), $a->copy()->endOfYear()],
            'yearly' => [$a->copy()->startOfYear(), $a->copy()->endOfYear()],
            default => [$a->copy()->startOfMonth(), $a->copy()->endOfMonth()],
        };
    }

    /** The window $n steps back: previous calendar periods, or equal-length windows for a custom range. */
    public function shifted(int $n): self
    {
        if ($this->period === 'custom') {
            $from = $this->from->copy()->subDays($this->days * $n);

            return new self($this->fieldIds, 'custom', $from, $from->copy()->addDays($this->days - 1), $from);
        }

        $anchor = match ($this->period) {
            'daily' => $this->from->copy()->subDays($n),
            'weekly' => $this->from->copy()->subWeeks($n),
            'quarterly' => $this->from->copy()->subMonthsNoOverflow(3 * $n),
            'semiannual' => $this->from->copy()->subMonthsNoOverflow(6 * $n),
            'yearly' => $this->from->copy()->subYears($n),
            default => $this->from->copy()->subMonthsNoOverflow($n),
        };
        [$from, $to] = self::namedWindow($this->period, $anchor);

        return new self($this->fieldIds, $this->period, $from, $to, $anchor);
    }

    public function label(): string
    {
        return $this->days === 1 ? $this->from->format('d M Y') : $this->from->format('d M Y') . ' – ' . $this->to->format('d M Y');
    }

    /** Query string that reproduces this window (drill-downs, links). */
    public function query(): array
    {
        return $this->period === 'custom'
            ? ['period' => 'custom', 'from' => $this->from->toDateString(), 'to' => $this->to->toDateString()]
            : ['period' => $this->period, 'date' => $this->anchor->toDateString()];
    }

    /* ------------------------------------------------------------------
     | Queries
     * ------------------------------------------------------------------ */

    /** Receipts in the window, joined to their client, limited to the user's field offices. */
    public function base(bool $approvedOnly = true)
    {
        $q = DB::table('receipts')
            ->leftJoin('clients', 'clients.id', '=', 'receipts.client_id')
            ->whereRaw(self::DATE_SQL . ' BETWEEN ? AND ?', [$this->from->toDateString(), $this->to->toDateString()]);

        if ($approvedOnly) {
            $q->where('receipts.ho_status', 'approved');
        }
        if ($this->fieldIds !== null) {
            $q->whereIn('clients.field_id', $this->fieldIds ?: [0]);
        }

        return $q;
    }

    public function totals(): object
    {
        $r = self::RECEIVED_SQL;
        $row = $this->base()->selectRaw("
            COUNT(*) as cnt,
            COALESCE(SUM({$r}),0) as received,
            COALESCE(SUM(receipts.cash_amount),0) as cash,
            COALESCE(SUM(receipts.momo_amount),0) as momo,
            COALESCE(SUM(receipts.cheque_amount),0) as cheque,
            COALESCE(SUM(receipts.transfer_amount),0) as transfer,
            COALESCE(SUM(receipts.other_payment_amnt),0) as other,
            COALESCE(SUM(receipts.wht_amount),0) as wht,
            COALESCE(SUM(receipts.vat7_value),0) as vat,
            COALESCE(SUM(receipts.dAmount),0) as deductions,
            COUNT(DISTINCT receipts.client_id) as clients,
            SUM(CASE WHEN receipts.receipt_month IS NULL THEN 1 ELSE 0 END) as undated,
            COALESCE(SUM(CASE WHEN receipts.status = 'completed' THEN {$r} END),0) as full_amount,
            SUM(CASE WHEN receipts.status = 'completed' THEN 1 ELSE 0 END) as full_cnt,
            COALESCE(SUM(CASE WHEN receipts.status = 'uncompleted' THEN {$r} END),0) as part_amount,
            SUM(CASE WHEN receipts.status = 'uncompleted' THEN 1 ELSE 0 END) as part_cnt
        ")->first();

        foreach ($row as $k => $v) {
            $row->{$k} = (float) $v;
        }
        $row->avg = $row->cnt > 0 ? $row->received / $row->cnt : 0.0;
        // Amount cleared off invoices = money + tax withheld by the client + agreed deductions.
        $row->settled = $row->received + $row->wht + $row->vat + $row->deductions;

        return $row;
    }

    /** Receipts in the window that head office has not approved yet (not in the totals). */
    public function awaitingApproval(): object
    {
        $row = $this->base(false)
            ->where(fn ($q) => $q->whereNull('receipts.ho_status')->orWhere('receipts.ho_status', '!=', 'approved'))
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(' . self::RECEIVED_SQL . '),0) as received')->first();

        return (object) ['cnt' => (int) $row->cnt, 'received' => (float) $row->received];
    }

    public function byField()
    {
        return $this->base()->leftJoin('fields', 'fields.id', '=', 'clients.field_id')
            ->selectRaw("fields.id as field_id, COALESCE(fields.name, 'No field office') as field_name,
                COUNT(*) as cnt, SUM(" . self::RECEIVED_SQL . ') as total')
            ->groupBy('fields.id', 'fields.name')->orderByDesc('total')->get();
    }

    public function topClients(int $limit = 10)
    {
        return $this->base()->whereNotNull('receipts.client_id')
            ->selectRaw('clients.id, clients.business_name, clients.name, COUNT(*) as cnt, SUM(' . self::RECEIVED_SQL . ') as total')
            ->groupBy('clients.id', 'clients.business_name', 'clients.name')->orderByDesc('total')->limit($limit)->get();
    }

    public function topCollectors(int $limit = 10)
    {
        return $this->base()->leftJoin('users', 'users.id', '=', 'receipts.user_id')
            ->selectRaw("users.id, COALESCE(users.name, 'Unknown') as name, COUNT(*) as cnt, SUM(" . self::RECEIVED_SQL . ') as total')
            ->groupBy('users.id', 'users.name')->orderByDesc('total')->limit($limit)->get();
    }

    /** Bucket size for the trend: days up to ~2 months, weeks up to ~6 months, months beyond. */
    public function bucket(): string
    {
        return $this->days <= 62 ? 'day' : ($this->days <= 186 ? 'week' : 'month');
    }

    /** Trend with every bucket present (zero when nothing was received), labelled server-side. */
    public function trend(): array
    {
        $bucket = $this->bucket();
        $keySql = match ($bucket) {
            'day' => 'DATE(' . self::DATE_SQL . ')',
            'week' => 'DATE_SUB(DATE(' . self::DATE_SQL . '), INTERVAL WEEKDAY(' . self::DATE_SQL . ') DAY)', // Monday
            default => "DATE_FORMAT(" . self::DATE_SQL . ", '%Y-%m-01')",
        };
        $rows = $this->base()->selectRaw("{$keySql} as k, SUM(" . self::RECEIVED_SQL . ') as total, COUNT(*) as cnt')
            ->groupBy('k')->get()->keyBy(fn ($r) => substr((string) $r->k, 0, 10));

        $start = match ($bucket) {
            'day' => $this->from->copy()->startOfDay(),
            'week' => $this->from->copy()->startOfWeek(),
            default => $this->from->copy()->startOfMonth(),
        };
        $out = ['bucket' => $bucket, 'labels' => [], 'values' => [], 'counts' => []];
        foreach (CarbonPeriod::create($start, '1 ' . $bucket, $this->to) as $d) {
            $k = $d->toDateString();
            $out['labels'][] = match ($bucket) {
                'day' => $d->format('d M'),
                'week' => 'Wk of ' . $d->format('d M'),
                default => $d->format('M Y'),
            };
            $out['values'][] = round((float) ($rows[$k]->total ?? 0), 2);
            $out['counts'][] = (int) ($rows[$k]->cnt ?? 0);
        }

        return $out;
    }

    /** Average received over the previous 4 equivalent windows. */
    public function projection(): array
    {
        $samples = [];
        for ($i = 1; $i <= 4; $i++) {
            $samples[] = (float) $this->shifted($i)->base()->selectRaw('COALESCE(SUM(' . self::RECEIVED_SQL . '),0) as t')->value('t');
        }

        return ['value' => round(array_sum($samples) / 4, 2), 'samples' => $samples];
    }

    /* ------------------------------------------------------------------
     | Drill-downs (same window, same scope, same money definition)
     * ------------------------------------------------------------------ */

    public function fieldClients(int $fieldId)
    {
        return $this->base()->where('clients.field_id', $fieldId)
            ->selectRaw('clients.id, clients.business_name, clients.name, COUNT(*) as cnt, SUM(' . self::RECEIVED_SQL . ') as total')
            ->groupBy('clients.id', 'clients.business_name', 'clients.name')->orderByDesc('total')->get();
    }

    public function clientCollectors(int $clientId)
    {
        return $this->base()->where('receipts.client_id', $clientId)->leftJoin('users', 'users.id', '=', 'receipts.user_id')
            ->selectRaw("users.id, COALESCE(users.name, 'Unknown') as name, COUNT(*) as cnt, SUM(" . self::RECEIVED_SQL . ') as total')
            ->groupBy('users.id', 'users.name')->orderByDesc('total')->get();
    }

    public function collectorClients(int $userId)
    {
        return $this->base()->where('receipts.user_id', $userId)
            ->selectRaw('clients.id, clients.business_name, clients.name, COUNT(*) as cnt, SUM(' . self::RECEIVED_SQL . ') as total')
            ->groupBy('clients.id', 'clients.business_name', 'clients.name')->orderByDesc('total')->get();
    }
}
