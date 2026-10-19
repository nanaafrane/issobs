<?php

namespace App\Support\Dashboards;

use App\Models\Attendance;
use App\Support\Attendance\MonthOverview;
use App\Support\Attendance\Roster;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Operations dashboard: are the posts covered?
 *
 * Built on the guard-attendance module (MonthOverview), so every figure matches the
 * Attendance pages: required posts come from each client's shift plan for the month,
 * posted guards from the roster, the shortage per shift (Roster::shortage), and the
 * coverage from approved sign-ins. Coverage only counts clients that have a shift plan,
 * up to today for the current month.
 */
class OperationsDashboard
{
    public static function build(DashboardContext $ctx): array
    {
        $clients = self::clients($ctx);
        $rows = MonthOverview::rows($clients, $ctx->month);

        $planned = $rows->filter(fn ($r) => $r['plan'] !== null);
        $requiredDays = $planned->sum('required_guard_days');
        $uncovered = $planned->sum('uncovered_guard_days');
        $worked = $rows->sum('worked');
        $absent = $rows->sum('absent');

        $guards = self::guards($ctx);
        $overtime = self::overtime($ctx);

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
                'overtime_amount' => $overtime['amount'],
                'no_plan' => $rows->count() - $planned->count(),
                'no_qr' => $rows->filter(fn ($r) => ! $r['has_qr'])->count(),
                'no_site' => $rows->filter(fn ($r) => ! $r['has_site'])->count(),
            ],
            'offices' => self::byOffice($ctx, $rows, $guards['by_office'], $overtime['by_office']),
            'daily' => self::daily($ctx, $clients, $planned),
            'attention' => $rows
                ->filter(fn ($r) => $r['shortage'] > 0 || ($r['uncovered_guard_days'] ?? 0) > 0 || $r['plan'] === null)
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
                    'coverage' => $r['plan'] && $r['required_guard_days'] > 0
                        ? round(($r['required_guard_days'] - $r['uncovered_guard_days']) / $r['required_guard_days'] * 100, 1) : null,
                    'no_plan' => $r['plan'] === null,
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

    private static function guards(DashboardContext $ctx): array
    {
        $base = fn () => DB::table('employees')->where('role_id', DashboardContext::GUARD_ROLE_ID)
            ->when($ctx->fieldIds !== null, fn ($q) => $q->whereIn('field_id', $ctx->fieldIds ?: [0]));

        $byOffice = $base()->selectRaw("COALESCE(field_id, 0) as f,
                SUM(CASE WHEN status IN ('Active','Re-Instate') THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status IN ('Active','Re-Instate') AND client_id IS NULL THEN 1 ELSE 0 END) as unposted,
                SUM(CASE WHEN status = 'Terminated' THEN 1 ELSE 0 END) as terminated,
                SUM(CASE WHEN status NOT IN ('Active','Re-Instate','Terminated') OR status IS NULL THEN 1 ELSE 0 END) as other")
            ->groupBy('f')->get()->keyBy('f');

        $names = DB::table('fields')->pluck('name', 'id');

        return [
            'active' => (int) $byOffice->sum('active'),
            'unposted' => (int) $byOffice->sum('unposted'),
            'by_office' => $byOffice,
            'status_by_office' => $byOffice->map(fn ($r, $f) => [
                'office' => $f ? ($names[$f] ?? 'Office #' . $f) : 'No office',
                'active' => (int) $r->active, 'terminated' => (int) $r->terminated, 'other' => (int) $r->other,
            ])->sortBy('office')->values()->all(),
        ];
    }

    private static function overtime(DashboardContext $ctx): array
    {
        [$from, $to] = $ctx->span();
        $base = fn () => DB::table('overtimes')->whereNull('deleted_at')->whereBetween('entry_date', [$from, $to])
            ->when($ctx->fieldIds !== null, fn ($q) => $q->whereIn('field_id', $ctx->fieldIds ?: [0]));

        $totals = $base()->selectRaw('COUNT(*) as cnt, COALESCE(SUM(amount),0) as amount')->first();

        return [
            'count' => (int) $totals->cnt,
            'amount' => round((float) $totals->amount, 2),
            'by_office' => $base()->selectRaw('field_id as f, COUNT(*) as cnt, SUM(amount) as amount')->groupBy('field_id')->get()->keyBy('f'),
            'reasons' => $base()->selectRaw("COALESCE(NULLIF(TRIM(reason), ''), 'No reason given') as r, COUNT(*) as cnt, SUM(amount) as amount")
                ->groupBy('r')->orderByDesc('cnt')->limit(6)->get()
                ->map(fn ($r) => ['label' => ucfirst((string) $r->r), 'value' => (int) $r->cnt, 'amount' => round((float) $r->amount, 2)])->all(),
        ];
    }

    /** Sign-ins held for review (any date): until approved they are not counted as present. */
    private static function pendingScans($clients): int
    {
        $ids = $clients->pluck('id')->all();

        return $ids ? DB::table('attendances')->whereIn('client_id', $ids)->where('status', Attendance::PENDING)->count() : 0;
    }

    /** Guards present each day of the month against the guards the plans require. */
    private static function daily(DashboardContext $ctx, $clients, $planned): array
    {
        [$from, $to] = $ctx->span();
        $last = $ctx->asOf()->toDateString();
        $ids = $clients->pluck('id')->all();

        $present = $ids ? DB::table('attendances')->whereIn('client_id', $ids)->where('status', Attendance::PRESENT)
            ->whereBetween('work_date', [$from, $to])
            ->selectRaw('work_date as d, COUNT(*) as n')->groupBy('work_date')->get()
            ->mapWithKeys(fn ($r) => [substr((string) $r->d, 0, 10) => (int) $r->n]) : collect();

        $required = $planned->sum('required');
        $out = ['labels' => [], 'present' => [], 'required' => []];
        foreach (CarbonPeriod::create($from, $to) as $d) {
            $k = $d->toDateString();
            $out['labels'][] = $d->format('j');
            $out['present'][] = $k <= $last ? ($present[$k] ?? 0) : null;
            $out['required'][] = $required;
        }

        return $out;
    }

    private static function byOffice(DashboardContext $ctx, $rows, $guardsByOffice, $overtimeByOffice): array
    {
        $grouped = $rows->groupBy(fn ($r) => (int) $r['client']->field_id);

        return $ctx->offices()->map(function ($o) use ($grouped, $guardsByOffice, $overtimeByOffice) {
            $r = $grouped->get((int) $o->id, collect());
            $planned = $r->filter(fn ($x) => $x['plan'] !== null);
            $req = $planned->sum('required_guard_days');
            $worked = $r->sum('worked');
            $absent = $r->sum('absent');

            return [
                'id' => (int) $o->id,
                'name' => $o->name,
                'clients' => $r->count(),
                'guards' => (int) ($guardsByOffice[$o->id]->active ?? 0),
                'unposted' => (int) ($guardsByOffice[$o->id]->unposted ?? 0),
                'required' => $r->sum('required'),
                'posted' => $r->sum('posted'),
                'shortage' => $r->sum('shortage'),
                'coverage' => $req > 0 ? round(($req - $planned->sum('uncovered_guard_days')) / $req * 100, 1) : null,
                'absence_rate' => ($worked + $absent) > 0 ? round($absent / ($worked + $absent) * 100, 1) : null,
                'overtime' => (int) ($overtimeByOffice[$o->id]->cnt ?? 0),
                'overtime_amount' => round((float) ($overtimeByOffice[$o->id]->amount ?? 0), 2),
            ];
        })->filter(fn ($o) => $o['clients'] || $o['guards'] || $o['overtime'])->values()->all();
    }
}
