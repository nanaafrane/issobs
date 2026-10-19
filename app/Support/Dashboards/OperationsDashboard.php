<?php

namespace App\Support\Dashboards;

use App\Models\Attendance;
use App\Support\Attendance\Roster;
use App\Support\Attendance\ShiftPlans;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Operations dashboard: are the posts covered, over any period?
 *
 * The same rules as the guard-attendance module (MonthOverview), applied to the period
 * instead of one calendar month, so a full month gives exactly MonthOverview's figures:
 *  - Required: each client's shift plan in force in that month (day + night guards), per day.
 *  - Coverage: 1 − guard-shifts not covered ÷ guard-shifts required, from approved (present)
 *    sign-ins per day and shift, counted up to today. Clients with no plan are left out.
 *  - Posted / shortage: today's roster against the plan in force at the end of the period
 *    (Roster::shortage: per shift, rotating guards fill whichever is short). The roster has no
 *    history, so these are "now" figures whatever the period.
 *  - Absence: absent ÷ (present + absent) guard-days in the period.
 *
 * Column aliases avoid words reserved in MySQL / MariaDB ("terminated" broke MariaDB).
 */
class OperationsDashboard
{
    public static function build(DashboardContext $ctx): array
    {
        $clients = self::clients($ctx);
        $rows = self::clientRows($ctx, $clients);

        $planned = $rows->filter(fn ($r) => $r['has_plan']);
        $requiredDays = $planned->sum('required_guard_days');
        $uncovered = $planned->sum('uncovered_guard_days');
        $worked = $rows->sum('worked');
        $absent = $rows->sum('absent');

        $guards = self::guards($ctx);
        $overtime = self::overtime($ctx);
        $prevOvertime = self::overtime($ctx->shifted(1));

        return [
            'kpi' => [
                'clients' => $rows->count(),
                'active_guards' => $guards['active'],
                'unposted' => $guards['unposted'],
                'required' => $rows->sum('required'),
                'posted' => $rows->sum('posted'),
                'shortage' => $rows->sum('shortage'),
                'clients_short' => $rows->filter(fn ($r) => $r['shortage'] > 0)->count(),
                'coverage' => $requiredDays > 0 ? round(($requiredDays - $uncovered) / $requiredDays * 100, 1) : null,
                'uncovered_days' => (int) $uncovered,
                'worked' => $worked,
                'absent' => $absent,
                'absence_rate' => ($worked + $absent) > 0 ? round($absent / ($worked + $absent) * 100, 1) : null,
                'pending_scans' => self::pendingScans($clients),
                'overtime_count' => $overtime['count'],
                'overtime_prev' => $prevOvertime['count'],
                'overtime_amount' => $overtime['amount'],
                'no_plan' => $rows->count() - $planned->count(),
                'no_qr' => $rows->filter(fn ($r) => ! $r['has_qr'])->count(),
                'no_site' => $rows->filter(fn ($r) => ! $r['has_site'])->count(),
                'counted_days' => self::countedDays($ctx),
            ],
            'offices' => self::byOffice($ctx, $rows, $guards['by_office'], $overtime['by_office']),
            'daily' => self::onDuty($ctx, $clients),
            'attention' => $rows
                ->filter(fn ($r) => $r['shortage'] > 0 || ($r['uncovered_guard_days'] ?? 0) > 0 || ! $r['has_plan'])
                ->sortBy([
                    fn ($a, $b) => $b['shortage'] <=> $a['shortage'],
                    fn ($a, $b) => ($b['uncovered_guard_days'] ?? -1) <=> ($a['uncovered_guard_days'] ?? -1),
                ])
                ->take(10)
                ->map(fn ($r) => [
                    'id' => $r['client']->id,
                    'name' => $r['client']->business_name ?: $r['client']->name,
                    'office' => $r['client']->field?->name,
                    'required' => $r['required'],
                    'posted' => $r['posted'],
                    'shortage' => $r['shortage'],
                    'uncovered' => $r['uncovered_guard_days'],
                    'coverage' => $r['coverage'],
                    'no_plan' => ! $r['has_plan'],
                ])->values()->all(),
            'overtime_reasons' => $overtime['reasons'],
            'guard_status' => $guards['status_by_office'],
        ];
    }

    /** Active, approved clients the user may see (the Attendance list), narrowed by the office filter. */
    private static function clients(DashboardContext $ctx)
    {
        $q = Roster::clientsFor($ctx->user);
        if ($ctx->fieldIds !== null) {
            $q->whereIn('field_id', $ctx->fieldIds ?: [0]);
        }

        return $q->with('field:id,name')->get();
    }

    /** Days of the period counted so far (none when the period starts after today). */
    private static function countedDays(DashboardContext $ctx): int
    {
        return $ctx->from->gt(Carbon::today()) ? 0 : (int) $ctx->from->diffInDays($ctx->asOf()) + 1;
    }

    /**
     * Shift plans in force on each day of [$from, $to]: [client_id => ['Y-m' => [day, night]]].
     * One query per month touched.
     */
    private static function plansByMonth(array $clientIds, Carbon $from, Carbon $to): array
    {
        $out = [];
        if (! $clientIds) {
            return $out;
        }
        for ($m = $from->copy()->startOfMonth(); $m->lte($to); $m->addMonthNoOverflow()) {
            foreach (ShiftPlans::forMonthMany($clientIds, $m) as $clientId => $plan) {
                $out[(int) $clientId][$m->format('Y-m')] = [(int) $plan->day_guards, (int) $plan->night_guards];
            }
        }

        return $out;
    }

    /** Present sign-ins per client, day and shift in [$from, $to]: [client][Y-m-d][shift] => n. */
    private static function presence(array $clientIds, string $from, string $to): array
    {
        $out = [];
        if (! $clientIds || $from > $to) {
            return $out;
        }
        $rows = DB::table('attendances')->whereIn('client_id', $clientIds)->where('status', Attendance::PRESENT)
            ->whereBetween('work_date', [$from, $to])
            ->selectRaw('client_id, work_date, shift, COUNT(*) as cnt')->groupBy('client_id', 'work_date', 'shift')->get();
        foreach ($rows as $r) {
            $out[(int) $r->client_id][substr((string) $r->work_date, 0, 10)][$r->shift] = (int) $r->cnt;
        }

        return $out;
    }

    /** Per client: plan, posting, shortage, coverage and attendance totals for the period. */
    private static function clientRows(DashboardContext $ctx, $clients)
    {
        $ids = $clients->pluck('id')->map(fn ($id) => (int) $id)->all();
        [$from, $to] = $ctx->span();
        $lastCounted = $ctx->asOf()->toDateString();
        $counting = $ctx->from->lte(Carbon::today());

        $plans = self::plansByMonth($ids, $ctx->from, $ctx->to);
        $endMonth = $ctx->asOf()->format('Y-m');
        $posted = Roster::shiftCounts($ids);
        $present = $counting ? self::presence($ids, $from, $lastCounted) : [];

        $counts = [];
        if ($ids) {
            foreach (DB::table('attendances')->whereIn('client_id', $ids)->whereBetween('work_date', [$from, $to])
                ->selectRaw('client_id, status, COUNT(*) as cnt')->groupBy('client_id', 'status')->get() as $r) {
                $counts[(int) $r->client_id][$r->status] = (int) $r->cnt;
            }
        }

        $days = $counting ? iterator_to_array(CarbonPeriod::create($from, $lastCounted)) : [];

        return $clients->map(function ($client) use ($plans, $endMonth, $posted, $present, $counts, $days) {
            $id = (int) $client->id;
            $clientPlans = $plans[$id] ?? [];
            [$dayReq, $nightReq] = $clientPlans[$endMonth] ?? (count($clientPlans) ? end($clientPlans) : [0, 0]);
            $p = $posted[$id] ?? ['day' => 0, 'night' => 0, 'rotate' => 0, 'unset' => 0];
            $c = $counts[$id] ?? [];

            $required = 0;
            $uncovered = 0;
            foreach ($days as $d) {
                [$dr, $nr] = $clientPlans[$d->format('Y-m')] ?? [0, 0];
                $k = $d->toDateString();
                $required += $dr + $nr;
                $uncovered += max(0, $dr - ($present[$id][$k]['day'] ?? 0)) + max(0, $nr - ($present[$id][$k]['night'] ?? 0));
            }
            $hasPlan = (bool) $clientPlans;

            return [
                'client' => $client,
                'has_plan' => $hasPlan,
                'required' => $dayReq + $nightReq,
                'posted' => array_sum($p),
                'shortage' => Roster::shortage($dayReq, $nightReq, $p),
                'worked' => $c[Attendance::PRESENT] ?? 0,
                'absent' => $c[Attendance::ABSENT] ?? 0,
                'required_guard_days' => $required,
                'uncovered_guard_days' => $hasPlan ? $uncovered : null,
                'coverage' => $hasPlan && $required > 0 ? round(($required - $uncovered) / $required * 100, 1) : null,
                'has_qr' => (bool) $client->attendance_token,
                'has_site' => $client->latitude !== null && $client->longitude !== null,
            ];
        })->values();
    }

    private static function guards(DashboardContext $ctx): array
    {
        $byOffice = DB::table('employees')->where('role_id', DashboardContext::GUARD_ROLE_ID)
            ->when($ctx->fieldIds !== null, fn ($q) => $q->whereIn('field_id', $ctx->fieldIds ?: [0]))
            ->selectRaw("COALESCE(field_id, 0) as office_id,
                SUM(CASE WHEN status IN ('Active','Re-Instate') THEN 1 ELSE 0 END) as active_cnt,
                SUM(CASE WHEN status IN ('Active','Re-Instate') AND client_id IS NULL THEN 1 ELSE 0 END) as unposted_cnt,
                SUM(CASE WHEN status = 'Terminated' THEN 1 ELSE 0 END) as left_cnt,
                SUM(CASE WHEN status IS NULL OR status NOT IN ('Active','Re-Instate','Terminated') THEN 1 ELSE 0 END) as pending_cnt")
            ->groupBy(DB::raw('COALESCE(field_id, 0)'))->get()->keyBy('office_id');

        $names = DB::table('fields')->pluck('name', 'id');

        return [
            'active' => (int) $byOffice->sum('active_cnt'),
            'unposted' => (int) $byOffice->sum('unposted_cnt'),
            'by_office' => $byOffice,
            'status_by_office' => $byOffice->map(fn ($r, $f) => [
                'office' => $f ? ($names[$f] ?? 'Office #' . $f) : 'No office',
                'active' => (int) $r->active_cnt, 'terminated' => (int) $r->left_cnt, 'other' => (int) $r->pending_cnt,
            ])->sortBy('office')->values()->all(),
        ];
    }

    private static function overtime(DashboardContext $ctx): array
    {
        $base = fn () => DB::table('overtimes')->whereNull('deleted_at')->whereBetween('entry_date', $ctx->span())
            ->when($ctx->fieldIds !== null, fn ($q) => $q->whereIn('field_id', $ctx->fieldIds ?: [0]));

        $totals = $base()->selectRaw('COUNT(*) as cnt, COALESCE(SUM(amount),0) as amount_sum')->first();

        return [
            'count' => (int) $totals->cnt,
            'amount' => round((float) $totals->amount_sum, 2),
            'by_office' => $base()->selectRaw('field_id as office_id, COUNT(*) as cnt, SUM(amount) as amount_sum')->groupBy('field_id')->get()->keyBy('office_id'),
            'reasons' => $base()->selectRaw("COALESCE(NULLIF(TRIM(reason), ''), 'No reason given') as reason_label, COUNT(*) as cnt, SUM(amount) as amount_sum")
                ->groupBy('reason_label')->orderByDesc('cnt')->limit(6)->get()
                ->map(fn ($r) => ['label' => ucfirst((string) $r->reason_label), 'value' => (int) $r->cnt, 'amount' => round((float) $r->amount_sum, 2)])->all(),
        ];
    }

    /** Sign-ins held for review (any date): until approved they are not counted as present. */
    private static function pendingScans($clients): int
    {
        $ids = $clients->pluck('id')->all();

        return $ids ? DB::table('attendances')->whereIn('client_id', $ids)->where('status', Attendance::PENDING)->count() : 0;
    }

    /**
     * Guards on duty: average present per day in each bucket against the average the plans require.
     * Buckets: each day for periods up to two months (a single day shows the 14 days up to it),
     * weeks for a quarter, months for longer. Days after today are left empty, not zero.
     */
    private static function onDuty(DashboardContext $ctx, $clients): array
    {
        if ($ctx->period === 'daily') {
            [$from, $to] = [$ctx->from->copy()->subDays(13), $ctx->to->copy()];
        } else {
            [$from, $to] = [$ctx->from->copy(), $ctx->to->copy()];
        }
        $days = (int) $from->diffInDays($to) + 1;
        $unit = $days <= 62 ? 'day' : ($days <= 120 ? 'week' : 'month');

        $ids = $clients->pluck('id')->map(fn ($id) => (int) $id)->all();
        $today = Carbon::today();
        $lastCounted = $to->min($today)->toDateString();
        $plans = self::plansByMonth($ids, $from, $to);
        $present = [];
        if ($ids && $from->lte($today)) {
            foreach (DB::table('attendances')->whereIn('client_id', $ids)->where('status', Attendance::PRESENT)
                ->whereBetween('work_date', [$from->toDateString(), $lastCounted])
                ->selectRaw('work_date, COUNT(*) as cnt')->groupBy('work_date')->get() as $r) {
                $present[substr((string) $r->work_date, 0, 10)] = (int) $r->cnt;
            }
        }
        $requiredFor = function (Carbon $d) use ($plans) {
            $m = $d->format('Y-m');
            $n = 0;
            foreach ($plans as $byMonth) {
                $n += array_sum($byMonth[$m] ?? [0, 0]);
            }

            return $n;
        };

        $out = ['labels' => [], 'present' => [], 'required' => [], 'unit' => $unit];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $end = match ($unit) {
                'day' => $cursor->copy(),
                'week' => $cursor->copy()->endOfWeek()->startOfDay()->min($to),
                default => $cursor->copy()->endOfMonth()->startOfDay()->min($to),
            };
            $sumPresent = 0;
            $sumRequired = 0;
            $counted = 0;
            $allDays = 0;
            foreach (CarbonPeriod::create($cursor, $end) as $d) {
                $allDays++;
                $sumRequired += $requiredFor($d);
                if ($d->lte($today)) {
                    $counted++;
                    $sumPresent += $present[$d->toDateString()] ?? 0;
                }
            }
            $out['labels'][] = match ($unit) {
                'day' => $days <= 31 && $ctx->period !== 'daily' ? $cursor->format('j') : $cursor->format('j M'),
                'week' => $cursor->format('j M'),
                default => $cursor->format('M y'),
            };
            $out['present'][] = $counted ? round($sumPresent / $counted, 1) : null;
            $out['required'][] = $allDays ? round($sumRequired / $allDays, 1) : 0;
            $cursor = $end->copy()->addDay();
        }

        return $out;
    }

    private static function byOffice(DashboardContext $ctx, $rows, $guardsByOffice, $overtimeByOffice): array
    {
        $grouped = $rows->groupBy(fn ($r) => (int) $r['client']->field_id);

        return $ctx->offices()->map(function ($o) use ($grouped, $guardsByOffice, $overtimeByOffice) {
            $r = $grouped->get((int) $o->id, collect());
            $planned = $r->filter(fn ($x) => $x['has_plan']);
            $req = $planned->sum('required_guard_days');
            $worked = $r->sum('worked');
            $absent = $r->sum('absent');

            return [
                'id' => (int) $o->id,
                'name' => $o->name,
                'clients' => $r->count(),
                'guards' => (int) ($guardsByOffice[$o->id]->active_cnt ?? 0),
                'unposted' => (int) ($guardsByOffice[$o->id]->unposted_cnt ?? 0),
                'required' => $r->sum('required'),
                'posted' => $r->sum('posted'),
                'shortage' => $r->sum('shortage'),
                'coverage' => $req > 0 ? round(($req - $planned->sum('uncovered_guard_days')) / $req * 100, 1) : null,
                'absence_rate' => ($worked + $absent) > 0 ? round($absent / ($worked + $absent) * 100, 1) : null,
                'overtime' => (int) ($overtimeByOffice[$o->id]->cnt ?? 0),
                'overtime_amount' => round((float) ($overtimeByOffice[$o->id]->amount_sum ?? 0), 2),
            ];
        })->filter(fn ($o) => $o['clients'] || $o['guards'] || $o['overtime'])->values()->all();
    }
}
