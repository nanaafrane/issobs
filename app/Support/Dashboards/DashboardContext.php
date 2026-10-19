<?php

namespace App\Support\Dashboards;

use App\Models\Field;
use App\Models\User;
use App\Support\Attendance\Roster;
use App\Support\EmployeeMovementsReport;
use App\Support\ReceiptReport;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * What a dashboard is looking at: which period, which field offices, and for whom.
 *
 * Every figure on the Accounts, HR and Operations dashboards is built from one of
 * these, so the period and the office scope are applied the same way everywhere
 * (and the same way the receipts report applies them):
 *
 *  - period: ?period=daily|weekly|monthly|quarterly|semiannual|yearly&date=Y-m-d
 *            (the calendar day / week / month / quarter / half-year / year containing
 *            the date), or ?period=custom&from=Y-m-d&to=Y-m-d. Default: this month.
 *            Old links with ?month=YYYY-MM still open that month. A date in the
 *            future falls back to today; a range longer than five years is cut.
 *  - office: ?field=<id> narrows to that office and its sub-offices (Tema includes
 *            Shai Hills), only within the offices the user may see; anything else
 *            is refused with 403 rather than silently widened.
 *  - scope:  head-office users see every office (fieldIds = null); a field-office
 *            user sees their own office and its sub-offices.
 *
 * Monthly records (invoices, salaries, shift plans) are matched to a period by the
 * months it touches: a week in October shows October's invoices and payroll.
 */
class DashboardContext
{
    public const ACCOUNTS = 'accounts';
    public const HR = 'hr';
    public const OPERATIONS = 'operations';

    public const LABELS = [
        self::ACCOUNTS => 'Accounts',
        self::HR => 'HR',
        self::OPERATIONS => 'Operations',
    ];

    /** Same periods, names and calendar rules as the receipts report. */
    public const PERIODS = ReceiptReport::PERIODS;

    /** The role every guard has (the id the rest of the system already uses for guards). */
    public const GUARD_ROLE_ID = 7;

    /** Roles that see the company-wide figures on every dashboard they can open. */
    public const HEAD_OFFICE_ROLES = ['Invoice', 'Finance Manager', 'Director', 'Internal Auditor'];

    public function __construct(
        public readonly User $user,
        public readonly string $board,
        public readonly string $period,       // a key of PERIODS
        public readonly Carbon $from,         // start of the first day
        public readonly Carbon $to,           // start of the last day
        public readonly Carbon $anchor,       // the date the named period was built around
        public readonly ?array $fieldIds,     // null = every office
        public readonly ?array $allowedIds,   // null = every office (before the ?field filter)
        public readonly ?int $fieldId,        // the ?field filter, if any
    ) {
    }

    public static function fromRequest(Request $request, User $user, string $board): self
    {
        [$period, $from, $to, $anchor] = self::window($request);
        $allowed = self::allowedFieldIds($user, $board);

        $fieldId = $request->integer('field') ?: null;
        $fieldIds = $allowed;
        if ($fieldId) {
            $wanted = Field::managedIds($fieldId);
            if ($allowed !== null && ! in_array($fieldId, $allowed, true)) {
                abort(403, 'You cannot view that field office.');
            }
            $fieldIds = $allowed === null ? $wanted : array_values(array_intersect($wanted, $allowed));
        }

        return new self($user, $board, $period, $from, $to, $anchor, $fieldIds, $allowed, $fieldId);
    }

    /** A copy of this context for another window (same user, board and offices). */
    public function withWindow(string $period, Carbon $from, Carbon $to, ?Carbon $anchor = null): self
    {
        return new self($this->user, $this->board, $period, $from->copy()->startOfDay(), $to->copy()->startOfDay(),
            ($anchor ?? $from)->copy(), $this->fieldIds, $this->allowedIds, $this->fieldId);
    }

    /** [period, from, to, anchor] read from the request; bad or missing input gives this month. */
    public static function window(Request $request): array
    {
        $today = Carbon::today();
        $period = array_key_exists((string) $request->input('period'), self::PERIODS) ? (string) $request->input('period') : 'monthly';

        if ($period === 'custom') {
            $from = self::date($request->input('from'));
            $to = self::date($request->input('to'));
            if ($from && $to) {
                if ($from->gt($to)) {
                    [$from, $to] = [$to, $from];
                }
                $to = $to->min($today);
                $from = $from->min($to);
                if ($from->diffInDays($to) >= ReceiptReport::MAX_RANGE_DAYS) {
                    $from = $to->copy()->subDays(ReceiptReport::MAX_RANGE_DAYS - 1);
                }

                return ['custom', $from, $to, $to->copy()];
            }
            $period = 'monthly';
        }

        // ?month=YYYY-MM (links from the first version of the dashboards)
        $anchor = self::date($request->input('date'));
        if (! $anchor && preg_match('/^\d{4}-\d{2}$/', (string) $request->input('month'))) {
            $anchor = self::date($request->input('month') . '-01');
            if ($anchor && ! $request->filled('period')) {
                $period = 'monthly';
            }
        }
        $anchor = ($anchor ?? $today)->min($today);
        [$from, $to] = ReceiptReport::namedWindow($period, $anchor);

        return [$period, $from->copy()->startOfDay(), $to->copy()->startOfDay(), $anchor];
    }

    private static function date($value): ?Carbon
    {
        $value = trim((string) $value);
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        try {
            $d = Carbon::createFromFormat('!Y-m-d', $value);

            return $d && $d->format('Y-m-d') === $value ? $d : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Offices the user may see on a dashboard: null = all.
     *
     * Accounts: the roles that already see every office's money (old dashboard + receipts report).
     * HR: anyone in HR (the old HR dashboard was company-wide) or the HR report's head-office roles.
     * Operations: whoever may take attendance for every client.
     */
    public static function allowedFieldIds(User $user, string $board): ?array
    {
        $all = match ($board) {
            self::ACCOUNTS => $user->hasRole(self::HEAD_OFFICE_ROLES),
            self::HR => $user->hasPermission('HR') || EmployeeMovementsReport::scopeFor($user)[0],
            self::OPERATIONS => Roster::seesAllClients($user) || $user->hasRole(['Internal Auditor']),
            default => false,
        };

        return $all ? null : Field::managedIds($user->field_id ? (int) $user->field_id : null);
    }

    /** Dashboards this user may open (the tabs at the top of each one). */
    public static function boardsFor(User $user): array
    {
        $dept = $user->department?->name;
        $director = $user->hasRole(['Director', 'Internal Auditor'])
            || ($user->hasRole(['Manager']) && $dept === 'Administration');

        $boards = [];
        if ($director || in_array($dept, ['Accounts', 'Administration'], true) || $user->hasRole(['Finance Manager', 'Invoice'])) {
            $boards[] = self::ACCOUNTS;
        }
        if ($director || $dept === 'HR') {
            $boards[] = self::HR;
        }
        if ($director || $dept === 'Operations') {
            $boards[] = self::OPERATIONS;
        }

        return $boards;
    }

    /** The dashboard /home opens for this user's department (null = keep the old behaviour). */
    public static function homeBoardFor(User $user): ?string
    {
        return match ($user->department?->name) {
            'Accounts', 'Administration' => self::ACCOUNTS,
            'HR' => self::HR,
            'Operations' => self::OPERATIONS,
            default => null,
        };
    }

    /* ------------------------------------------------------------------
     | The window
     * ------------------------------------------------------------------ */

    /** First and last day of the period, as Y-m-d strings. */
    public function span(): array
    {
        return [$this->from->toDateString(), $this->to->toDateString()];
    }

    /** The same window as datetimes, for created_at-style columns. */
    public function dateTimeSpan(): array
    {
        return [$this->from->toDateString() . ' 00:00:00', $this->to->toDateString() . ' 23:59:59'];
    }

    /** Every month the period touches: first day of the first, last day of the last (Y-m-d). */
    public function monthSpan(): array
    {
        return [$this->from->copy()->startOfMonth()->toDateString(), $this->to->copy()->endOfMonth()->toDateString()];
    }

    /** "Oct 2026" or "Jan – Jun 2026": the months monthly records are taken from. */
    public function monthsLabel(): string
    {
        $a = $this->from->copy()->startOfMonth();
        $b = $this->to->copy()->startOfMonth();
        if ($a->equalTo($b)) {
            return $a->format('M Y');
        }

        return $a->year === $b->year ? $a->format('M') . ' – ' . $b->format('M Y') : $a->format('M Y') . ' – ' . $b->format('M Y');
    }

    /** True when the period is exactly whole calendar months (monthly, quarterly, ... or such a range). */
    public function isWholeMonths(): bool
    {
        return $this->from->day === 1 && $this->to->isSameDay($this->to->copy()->endOfMonth());
    }

    /** Last day counted: the end of the period, or today while it is still running. */
    public function asOf(): Carbon
    {
        return $this->to->lt(Carbon::today()) ? $this->to->copy() : Carbon::today();
    }

    /** The period includes today, so its figures are "to date". */
    public function isRunning(): bool
    {
        return Carbon::today()->betweenIncluded($this->from, $this->to);
    }

    public function days(): int
    {
        return (int) $this->from->diffInDays($this->to) + 1;
    }

    /** "October 2026", "Q4 2026", "Week of 12 Oct 2026", "12 Oct – 19 Oct 2026" ... */
    public function label(): string
    {
        $f = $this->from;

        return match ($this->period) {
            'daily' => $f->format('l j F Y'),
            'weekly' => 'Week of ' . $f->format('j M Y'),
            'monthly' => $f->format('F Y'),
            'quarterly' => 'Q' . $f->quarter . ' ' . $f->year,
            'semiannual' => ($f->month <= 6 ? 'H1 ' : 'H2 ') . $f->year,
            'yearly' => (string) $f->year,
            default => $this->days() === 1 ? $f->format('j M Y') : $f->format('j M Y') . ' – ' . $this->to->format('j M Y'),
        };
    }

    /** A short name for the period, for "vs last week"-type comparisons. */
    public function previousLabel(): string
    {
        return match ($this->period) {
            'daily' => 'previous day',
            'weekly' => 'last week',
            'monthly' => $this->from->copy()->subMonthNoOverflow()->format('M'),
            'quarterly' => 'last quarter',
            'semiannual' => 'last half-year',
            'yearly' => (string) ($this->from->year - 1),
            default => 'previous ' . $this->days() . ' days',
        };
    }

    /** The window $n steps back: the previous calendar period, or an equal-length window before a range. */
    public function shifted(int $n = 1): self
    {
        if ($this->period === 'custom') {
            $from = $this->from->copy()->subDays($this->days() * $n);

            return $this->withWindow('custom', $from, $from->copy()->addDays($this->days() - 1));
        }
        $anchor = match ($this->period) {
            'daily' => $this->from->copy()->subDays($n),
            'weekly' => $this->from->copy()->subWeeks($n),
            'quarterly' => $this->from->copy()->subMonthsNoOverflow(3 * $n),
            'semiannual' => $this->from->copy()->subMonthsNoOverflow(6 * $n),
            'yearly' => $this->from->copy()->subYears($n),
            default => $this->from->copy()->subMonthsNoOverflow($n),
        };
        [$from, $to] = ReceiptReport::namedWindow($this->period, $anchor);

        return $this->withWindow($this->period, $from, $to, $anchor);
    }

    /** The next period, or null when it would start after today. */
    public function next(): ?self
    {
        $next = $this->shifted(-1);

        return $next->from->gt(Carbon::today()) ? null : $next;
    }

    /**
     * Buckets for the trend charts, oldest first: [from, to, label].
     *
     * Named periods: this period and the ones before it (14 days, 12 weeks, 12 months, 8 quarters,
     * 6 half-years, 5 years). A custom range: the range itself, by day (up to 2 months), week
     * (up to 6 months) or month.
     */
    public function trendBuckets(): array
    {
        if ($this->period === 'custom') {
            $unit = $this->days() <= 62 ? 'day' : ($this->days() <= 186 ? 'week' : 'month');
            $out = [];
            $cursor = match ($unit) {
                'day' => $this->from->copy(),
                'week' => $this->from->copy()->startOfWeek(),
                default => $this->from->copy()->startOfMonth(),
            };
            while ($cursor->lte($this->to)) {
                $end = match ($unit) {
                    'day' => $cursor->copy(),
                    'week' => $cursor->copy()->endOfWeek()->startOfDay(),
                    default => $cursor->copy()->endOfMonth()->startOfDay(),
                };
                $b = [$cursor->copy()->max($this->from), $end->min($this->to)];
                $out[] = [$b[0], $b[1], match ($unit) {
                    'day' => $cursor->format('j M'),
                    'week' => $cursor->format('j M'),
                    default => $cursor->format('M y'),
                }];
                $cursor = $end->copy()->addDay();
            }

            return $out;
        }

        $n = ['daily' => 14, 'weekly' => 12, 'monthly' => 12, 'quarterly' => 8, 'semiannual' => 6, 'yearly' => 5][$this->period];
        $out = [];
        for ($i = $n - 1; $i >= 0; $i--) {
            $c = $this->shifted($i);
            $out[] = [$c->from, $c->to, match ($this->period) {
                'daily' => $c->from->format('j M'),
                'weekly' => $c->from->format('j M'),
                'monthly' => $c->from->format('M y'),
                'quarterly' => 'Q' . $c->from->quarter . ' ' . $c->from->format('y'),
                'semiannual' => ($c->from->month <= 6 ? 'H1 ' : 'H2 ') . $c->from->format('y'),
                'yearly' => (string) $c->from->year,
            }];
        }

        return $out;
    }

    /** What one trend bucket is called, for chart captions. */
    public function trendCaption(): string
    {
        $b = $this->trendBuckets();
        $first = $b[0];
        $len = (int) $first[0]->diffInDays($first[1]) + 1;
        $unit = $len === 1 ? 'days' : ($len <= 7 ? 'weeks' : ($len <= 31 ? 'months' : ($len <= 92 ? 'quarters' : ($len <= 184 ? 'half-years' : 'years'))));

        return ($this->period === 'custom' ? 'By ' . rtrim($unit, 's') : 'Last ' . count($b) . ' ' . $unit);
    }

    /** True when the trend buckets are whole months or longer (monthly records can be plotted). */
    public function trendIsMonthly(): bool
    {
        $b = $this->trendBuckets()[0];

        return $b[0]->day === 1 && $b[1]->isSameDay($b[1]->copy()->endOfMonth());
    }

    /* ------------------------------------------------------------------
     | Offices
     * ------------------------------------------------------------------ */

    /** Offices offered in the office filter: the ones this user may see, top-level first. */
    public function officeOptions()
    {
        return Field::query()
            ->when($this->allowedIds !== null, fn ($q) => $q->whereIn('id', $this->allowedIds ?: [0]))
            ->orderByRaw('parent_field_id IS NOT NULL')->orderBy('name')
            ->get(['id', 'name', 'parent_field_id']);
    }

    /** Offices shown in the per-office tables. */
    public function offices()
    {
        return Field::query()
            ->when($this->fieldIds !== null, fn ($q) => $q->whereIn('id', $this->fieldIds ?: [0]))
            ->orderBy('name')->get(['id', 'name', 'parent_field_id']);
    }

    public function scopeLabel(): string
    {
        if ($this->fieldId) {
            return Field::whereKey($this->fieldId)->value('name') ?? 'Office';
        }

        return $this->fieldIds === null ? 'All offices' : 'Your offices';
    }

    /** Query string that reopens this view (period, dates, office); $extra overrides. */
    public function query(array $extra = []): array
    {
        $q = $this->period === 'custom'
            ? ['period' => 'custom', 'from' => $this->from->toDateString(), 'to' => $this->to->toDateString()]
            : ['period' => $this->period, 'date' => $this->anchor->toDateString()];

        return array_filter($q + ['field' => $this->fieldId], fn ($v) => $v !== null);
    }

    /**
     * Period parameters for linking to another report that knows fewer periods: the same period
     * when it supports it, otherwise the month of the period's end. $monthKey sends ?month=Y-m
     * (salaries report, attendance) instead of ?date=Y-m-d.
     */
    public function reportQuery(array $supported, ?string $monthKey = null, array $extra = []): array
    {
        $period = in_array($this->period, $supported, true) ? $this->period : 'monthly';
        $anchor = $this->period === 'custom' ? $this->to : $this->anchor;
        $q = $monthKey ? [$monthKey => $anchor->format('Y-m')] : ['date' => $anchor->toDateString()];
        if ($supported) {
            $q = ['period' => $period] + $q;
        }

        return $q + array_filter(['field' => $this->fieldId]) + $extra;
    }

    /**
     * Sum a value per day over the window and fold it into the trend buckets.
     *
     * @param  array<string, float>  $byDay  'Y-m-d' => value
     * @return float[] one value per trend bucket
     */
    public static function foldIntoBuckets(array $byDay, array $buckets): array
    {
        $out = array_fill(0, count($buckets), 0.0);
        foreach ($byDay as $day => $value) {
            $day = substr((string) $day, 0, 10);
            foreach ($buckets as $i => [$from, $to]) {
                if ($day >= $from->toDateString() && $day <= $to->toDateString()) {
                    $out[$i] += (float) $value;
                    break;
                }
            }
        }

        return array_map(fn ($v) => round($v, 2), $out);
    }
}
