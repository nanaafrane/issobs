<?php

namespace App\Support;

use App\Models\Field;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * HR movements report: headcount that reconciles.
 *
 *   headcount at start + new recruits + re-instated + transfers in
 *     - terminated - transfers out = headcount at end
 *
 * Built only from APPROVED movements with a known date, replayed in date order per
 * employee. Each movement is counted against the office the employee was in at that
 * moment (a termination against the office they left from), so the sum always holds,
 * for one office or for all.
 *
 * A movement takes effect at the end of its effective date: someone terminated on
 * 15 Oct (last day employed) is counted as terminated in October and is not in the
 * headcount at the end of October.
 */
class EmployeeMovementsReport
{
    public const NO_OFFICE = 0;

    /**
     * @param int[]|null $fieldIds  offices in scope, null = every office
     */
    public static function build(Carbon $from, Carbon $to, ?array $fieldIds = null): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();
        $inScope = fn ($f) => $fieldIds === null || in_array((int) $f, $fieldIds, true);

        // An approved hire with no known date (old records) counts as employed from before any
        // period, so their later termination is never lost. Other undated movements wait for a date.
        $rows = DB::table('employee_movements')->where('status', 'approved')
            ->where(fn ($q) => $q->whereNotNull('effective_date')->orWhere('type', 'hire'))
            ->orderByRaw('effective_date IS NOT NULL')->orderBy('effective_date')->orderBy('id')
            ->get(['id', 'employee_id', 'type', 'effective_date', 'reason', 'note', 'field_id', 'from_field_id', 'client_id', 'role_id',
                'date_estimated', 'requested_by', 'decided_by', 'decided_at', 'source']);

        $state = [];      // employee => ['active' => bool, 'field' => int, 'since' => date]
        $opening = null;
        $closing = null;
        $events = [];     // movements in the period, attributed to an office
        $conflicts = [];  // movements that make no sense in order (e.g. terminated before joining)
        $fromStr = $from->toDateString();
        $toStr = $to->toDateString();

        foreach ($rows as $m) {
            $date = $m->effective_date ? substr((string) $m->effective_date, 0, 10) : '0000-00-00';
            if ($opening === null && $date >= $fromStr) {
                $opening = $state;
            }
            if ($closing === null && $date > $toStr) {
                $closing = $state;
            }
            $e = (int) $m->employee_id;
            $before = $state[$e] ?? ['active' => false, 'field' => self::NO_OFFICE, 'since' => null, 'since_est' => false];
            $after = $before;
            $office = (int) ($m->field_id ?: self::NO_OFFICE);

            switch ($m->type) {
                case EmployeeMovements::HIRE:
                case EmployeeMovements::REINSTATEMENT:
                    if ($before['active']) {
                        if ($m->type === EmployeeMovements::REINSTATEMENT) {
                            $conflicts[] = $m;   // re-instated while still employed
                        }
                        continue 2;   // already employed: a duplicate hire, ignore
                    }
                    // No start date known (old record): "worked for" is unknown, never counted from year 0.
                    $after = ['active' => true, 'field' => $office, 'since' => $m->effective_date ? $date : null, 'since_est' => (bool) $m->date_estimated];
                    $m->office = $office;
                    break;
                case EmployeeMovements::TERMINATION:
                    if (! $before['active']) {
                        $conflicts[] = $m;   // e.g. termination dated before the joining date
                        continue 2;
                    }
                    $after = ['active' => false, 'field' => $before['field'], 'since' => null, 'since_est' => false];
                    $m->office = $before['field'];
                    $m->tenure_days = $before['since'] ? (int) Carbon::parse($before['since'])->diffInDays(Carbon::parse($date)) : null;
                    $m->tenure_from = $before['since'];
                    $m->tenure_estimated = $before['since_est'];
                    break;
                case EmployeeMovements::TRANSFER:
                    $after = ['active' => $before['active'], 'field' => $office, 'since' => $before['since'], 'since_est' => $before['since_est']];
                    $m->office = $office;
                    $m->from_office = $before['field'];
                    if (! $before['active'] || $before['field'] === $office) {
                        $state[$e] = $after;
                        continue 2;   // not employed, or no real move: office updated, nothing to count
                    }
                    break;
            }
            $state[$e] = $after;
            if ($date >= $fromStr && $date <= $toStr) {
                $events[] = $m;
            }
        }
        $opening ??= $state;
        $closing ??= $state;

        // ---- per office
        $offices = [];
        $row = function ($f) use (&$offices) {
            return $offices[$f] ??= ['field_id' => $f, 'opening' => 0, 'hires' => 0, 'reinstated' => 0, 'transfers_in' => 0,
                'terminated' => 0, 'transfers_out' => 0, 'closing' => 0];
        };
        foreach ($opening as $s) {
            if ($s['active'] && $inScope($s['field'])) {
                $row($s['field']);
                $offices[$s['field']]['opening']++;
            }
        }
        foreach ($closing as $s) {
            if ($s['active'] && $inScope($s['field'])) {
                $row($s['field']);
                $offices[$s['field']]['closing']++;
            }
        }
        $listed = [];
        foreach ($events as $m) {
            if ($m->type === EmployeeMovements::TRANSFER) {
                if ($inScope($m->from_office)) {
                    $row($m->from_office);
                    $offices[$m->from_office]['transfers_out']++;
                }
                if ($inScope($m->office)) {
                    $row($m->office);
                    $offices[$m->office]['transfers_in']++;
                }
                if ($inScope($m->from_office) || $inScope($m->office)) {
                    $listed[] = $m;
                }
                continue;
            }
            if (! $inScope($m->office)) {
                continue;
            }
            $row($m->office);
            $key = ['hire' => 'hires', 'reinstatement' => 'reinstated', 'termination' => 'terminated'][$m->type];
            $offices[$m->office][$key]++;
            $listed[] = $m;
        }

        $names = Field::pluck('name', 'id')->mapWithKeys(fn ($n, $id) => [(int) $id => $n])->all();
        $totals = ['opening' => 0, 'hires' => 0, 'reinstated' => 0, 'transfers_in' => 0, 'terminated' => 0, 'transfers_out' => 0, 'closing' => 0];
        foreach ($offices as $f => &$o) {
            $o['name'] = $f === self::NO_OFFICE ? 'No office' : ($names[$f] ?? 'Office #' . $f);
            $o['net'] = $o['hires'] + $o['reinstated'] + $o['transfers_in'] - $o['terminated'] - $o['transfers_out'];
            $o['turnover'] = self::turnover($o['terminated'], $o['opening'], $o['closing']);
            foreach ($totals as $k => $_) {
                $totals[$k] += $o[$k];
            }
        }
        unset($o);
        uasort($offices, fn ($a, $b) => strcasecmp($a['name'], $b['name']));
        // Transfers between two offices in scope cancel out in the total.
        $totals['net'] = $totals['hires'] + $totals['reinstated'] + $totals['transfers_in'] - $totals['terminated'] - $totals['transfers_out'];
        $totals['turnover'] = self::turnover($totals['terminated'], $totals['opening'], $totals['closing']);
        $totals['reconciles'] = $totals['opening'] + $totals['net'] === $totals['closing'];

        // ---- people, for the lists
        $empIds = collect($listed)->pluck('employee_id')->unique()->all();
        $people = DB::table('employees')->whereIn('id', $empIds ?: [0])->get(['id', 'name', 'phone_number', 'gender'])->keyBy('id');
        $clients = DB::table('clients')->whereIn('id', collect($listed)->pluck('client_id')->filter()->unique()->all() ?: [0])
            ->get(['id', 'name', 'business_name'])->keyBy('id');
        $roles = DB::table('roles')->pluck('name', 'id');
        $users = DB::table('users')->whereIn('id', collect($listed)->flatMap(fn ($m) => [$m->requested_by, $m->decided_by])->filter()->unique()->all() ?: [0])->pluck('name', 'id');
        $officeName = fn ($f) => $f === self::NO_OFFICE || $f === null ? 'No office' : ($names[$f] ?? 'Office #' . $f);
        $clientName = fn ($id) => $id && isset($clients[$id]) ? trim($clients[$id]->name . ' ' . $clients[$id]->business_name) : '';

        $lists = ['hire' => [], 'reinstatement' => [], 'termination' => [], 'transfer' => []];
        foreach ($listed as $m) {
            $p = $people[$m->employee_id] ?? null;
            $lists[$m->type][] = [
                'id' => (int) $m->id,
                'employee_id' => (int) $m->employee_id,
                'name' => $p->name ?? 'Employee #' . $m->employee_id,
                'phone' => $p->phone_number ?? '',
                'date' => substr((string) $m->effective_date, 0, 10),
                'estimated' => (bool) $m->date_estimated,
                'office' => $officeName($m->office),
                'from_office' => isset($m->from_office) ? $officeName($m->from_office) : null,
                'client' => $clientName($m->client_id),
                'role' => $m->role_id ? ($roles[$m->role_id] ?? '') : '',
                'reason' => $m->type === EmployeeMovements::TERMINATION ? EmployeeMovements::reasonLabel($m->reason) : null,
                'reason_key' => $m->reason,
                'note' => $m->note,
                'tenure_days' => $m->tenure_days ?? null,
                'tenure_from' => $m->tenure_from ?? null,
                'tenure_estimated' => (bool) ($m->tenure_estimated ?? false),
                'requested_by' => $users[$m->requested_by] ?? '',
                'approved_by' => $users[$m->decided_by] ?? '',
                'source' => $m->source,
            ];
        }
        foreach ($lists as &$l) {
            usort($l, fn ($a, $b) => [$a['date'], $a['name']] <=> [$b['date'], $b['name']]);
        }
        unset($l);

        // ---- terminations: why, and after how long
        $terms = collect($lists['termination']);
        $byReason = $terms->groupBy(fn ($t) => $t['reason'])->map->count()->sortDesc()->all();
        $byClient = $terms->groupBy(fn ($t) => $t['client'] ?: 'No client')->map->count()->sortDesc()->take(10)->all();
        $tenure = ['Under 30 days' => 0, '30 to 89 days' => 0, '3 to 12 months' => 0, '1 to 2 years' => 0, 'Over 2 years' => 0, 'Unknown' => 0];
        foreach ($terms as $t) {
            $d = $t['tenure_days'];
            $tenure[match (true) {
                $d === null => 'Unknown',
                $d < 30 => 'Under 30 days',
                $d < 90 => '30 to 89 days',
                $d < 365 => '3 to 12 months',
                $d < 730 => '1 to 2 years',
                default => 'Over 2 years',
            }]++;
        }
        $hiresByRole = collect($lists['hire'])->groupBy(fn ($h) => $h['role'] ?: 'No role')->map->count()->sortDesc()->all();

        return [
            'from' => $from,
            'to' => $to,
            'offices' => array_values($offices),
            'totals' => $totals,
            'lists' => $lists,
            'by_reason' => $byReason,
            'by_client' => $byClient,
            'tenure' => $tenure,
            'early_leavers' => $tenure['Under 30 days'] + $tenure['30 to 89 days'],
            'hires_by_role' => $hiresByRole,
            'pending' => self::pending($fieldIds, $names),
            'undated' => self::undated($fieldIds, $names),
            'mismatches' => self::mismatches($state, $fieldIds, $names),
            'conflicts' => self::conflicts($conflicts, $fieldIds, $names),
            'pending_counts' => self::pendingCounts($fieldIds),
            'payroll' => self::payrollCheck($from, $to, $opening, $closing, $events, $fieldIds),
        ];
    }

    /**
     * Movements that could not be counted because of their order: a termination dated
     * before the employee joined (or after an earlier termination), a re-instatement of
     * someone still employed. Shown so HR can correct the dates.
     */
    private static function conflicts(array $list, ?array $fieldIds, array $names): array
    {
        if (! $list) {
            return [];
        }
        $people = DB::table('employees')->whereIn('id', collect($list)->pluck('employee_id')->all())->get(['id', 'name', 'field_id'])->keyBy('id');
        $hires = DB::table('employee_movements')->where('type', 'hire')->where('status', 'approved')
            ->whereIn('employee_id', collect($list)->pluck('employee_id')->all())->pluck('effective_date', 'employee_id');
        $out = [];
        foreach ($list as $m) {
            $p = $people[$m->employee_id] ?? null;
            $office = (int) ($m->field_id ?: ($p->field_id ?? 0));
            if ($fieldIds !== null && ! in_array($office, $fieldIds, true)) {
                continue;
            }
            $out[] = [
                'id' => (int) $m->id, 'employee_id' => (int) $m->employee_id, 'name' => $p->name ?? 'Employee #' . $m->employee_id,
                'type' => EmployeeMovements::LABELS[$m->type] ?? $m->type, 'type_key' => $m->type, 'reason_key' => $m->reason,
                'date' => substr((string) $m->effective_date, 0, 10), 'office' => $names[$office] ?? 'No office',
                'problem' => $m->type === EmployeeMovements::TERMINATION
                    ? 'Not counted: not employed on that date (joined ' . (($hires[$m->employee_id] ?? null) ? Carbon::parse($hires[$m->employee_id])->format('d M Y') : 'on an unknown date') . ', or already terminated).'
                    : 'Not counted: still employed on that date.',
            ];
        }

        return $out;
    }

    /** Requests waiting for head office, by type (shown beside the totals). */
    private static function pendingCounts(?array $fieldIds): array
    {
        return DB::table('employee_movements as m')->join('employees as e', 'e.id', '=', 'm.employee_id')->where('m.status', 'pending')
            ->when($fieldIds !== null, fn ($q) => $q->whereIn(DB::raw('COALESCE(m.field_id, e.field_id)'), $fieldIds ?: [0]))
            ->selectRaw('m.type, COUNT(*) as n')->groupBy('m.type')->pluck('n', 'type')->map(fn ($n) => (int) $n)->all();
    }

    /**
     * Why each of this employee's approved movements is or is not counted in the report
     * (the same replay as build(), for one person). movement id => null (counted) or the
     * reason it is left out. Shown in the employee's history so nothing is skipped silently.
     *
     * @return array<int, ?string>
     */
    public static function explain(int $employeeId): array
    {
        $rows = DB::table('employee_movements')->where('employee_id', $employeeId)->where('status', 'approved')
            ->orderByRaw('effective_date IS NOT NULL')->orderBy('effective_date')->orderBy('id')
            ->get(['id', 'type', 'effective_date', 'field_id']);
        $active = false;
        $lastEnd = null;
        $out = [];
        foreach ($rows as $m) {
            $date = $m->effective_date ? Carbon::parse($m->effective_date)->format('d M Y') : null;
            if (! $m->effective_date && $m->type !== EmployeeMovements::HIRE) {
                $out[$m->id] = 'Not counted: it has no date. Set the date in the HR movements report (Needs attention).';
                continue;
            }
            switch ($m->type) {
                case EmployeeMovements::HIRE:
                case EmployeeMovements::REINSTATEMENT:
                    if ($active) {
                        $out[$m->id] = $m->type === EmployeeMovements::HIRE ? 'Not counted: already employed (a duplicate hire).'
                            : 'Not counted: still employed on ' . $date . ' (no termination before it).';
                        continue 2;
                    }
                    $active = true;
                    $out[$m->id] = $m->effective_date ? null : 'Counted as employed from before any period (joining date unknown).';
                    break;
                case EmployeeMovements::TERMINATION:
                    if (! $active) {
                        $out[$m->id] = 'Not counted: not employed on ' . $date . ($lastEnd ? ' (already terminated on ' . $lastEnd . ')' : ' (the joining date is later)')
                            . '. Correct the dates in the HR movements report (Needs attention).';
                        continue 2;
                    }
                    $active = false;
                    $lastEnd = $date;
                    $out[$m->id] = null;
                    break;
                default:
                    $out[$m->id] = null;
            }
        }

        return $out;
    }

    /** Terminations per 100 staff on average over the period. */
    private static function turnover(int $terminated, int $opening, int $closing): ?float
    {
        $avg = ($opening + $closing) / 2;

        return $avg > 0 ? round($terminated / $avg * 100, 1) : null;
    }

    /** Requests waiting for head office (any date). */
    private static function pending(?array $fieldIds, array $names): array
    {
        return DB::table('employee_movements as m')->join('employees as e', 'e.id', '=', 'm.employee_id')
            ->where('m.status', 'pending')
            ->when($fieldIds !== null, fn ($q) => $q->whereIn(DB::raw('COALESCE(m.field_id, e.field_id)'), $fieldIds ?: [0]))
            ->orderBy('m.requested_at')->get(['m.employee_id', 'e.name', 'm.type', 'm.effective_date', 'm.reason', 'm.field_id', 'm.requested_at'])
            ->map(fn ($r) => [
                'employee_id' => (int) $r->employee_id, 'name' => $r->name, 'type' => EmployeeMovements::LABELS[$r->type] ?? $r->type,
                'date' => $r->effective_date ? substr($r->effective_date, 0, 10) : '', 'office' => $names[(int) $r->field_id] ?? 'No office',
                'reason' => $r->type === 'termination' ? EmployeeMovements::reasonLabel($r->reason) : '',
                'waiting_days' => $r->requested_at ? (int) Carbon::parse($r->requested_at)->diffInDays(now()) : null,
            ])->all();
    }

    /** Approved movements with no date. A hire counts as employed from before any period; others wait for a date. */
    private static function undated(?array $fieldIds, array $names): array
    {
        return DB::table('employee_movements as m')->join('employees as e', 'e.id', '=', 'm.employee_id')
            ->where('m.status', 'approved')->whereNull('m.effective_date')
            ->when($fieldIds !== null, fn ($q) => $q->whereIn(DB::raw('COALESCE(m.field_id, e.field_id)'), $fieldIds ?: [0]))
            ->orderBy('e.name')->get(['m.id', 'm.employee_id', 'e.name', 'm.type', 'm.field_id', 'm.reason'])
            ->map(fn ($r) => ['id' => (int) $r->id, 'type_key' => $r->type, 'reason_key' => $r->reason, 'employee_id' => (int) $r->employee_id, 'name' => $r->name, 'type' => EmployeeMovements::LABELS[$r->type] ?? $r->type,
                'office' => $names[(int) $r->field_id] ?? 'No office'])->all();
    }

    /**
     * Employees whose status says something different from their movements (a status
     * changed outside the terminate / re-instate steps). Waiting requests are not mismatches.
     */
    private static function mismatches(array $state, ?array $fieldIds, array $names): array
    {
        $pending = DB::table('employee_movements')->where('status', 'pending')->pluck('employee_id')->flip();
        $out = [];
        DB::table('employees')->whereIn('status', ['Active', 'Terminated'])
            ->when($fieldIds !== null, fn ($q) => $q->whereIn('field_id', $fieldIds ?: [0]))
            ->orderBy('name')->get(['id', 'name', 'status', 'field_id'])
            ->each(function ($e) use ($state, $pending, $names, &$out) {
                if (isset($pending[$e->id])) {
                    return;
                }
                $active = $state[$e->id]['active'] ?? false;
                if (($e->status === 'Active') !== $active) {
                    $out[] = ['employee_id' => (int) $e->id, 'name' => $e->name, 'status' => $e->status,
                        'movements_say' => $active ? 'Employed' : 'Not employed', 'office' => $names[(int) $e->field_id] ?? 'No office'];
                }
            });

        return $out;
    }

    /**
     * Only for a single calendar month: who was employed during the month but has no salary
     * for it, and who has a salary but was not employed at any point in the month.
     */
    private static function payrollCheck(Carbon $from, Carbon $to, array $opening, array $closing, array $events, ?array $fieldIds): ?array
    {
        if (! ($from->day === 1 && $to->isSameDay($from->copy()->endOfMonth()))) {
            return null;
        }
        $inScope = fn ($f) => $fieldIds === null || in_array((int) $f, $fieldIds, true);

        // Employed at some point in the month: at the start, at the end, or joined / left during it.
        $employed = [];
        foreach ([$opening, $closing] as $snap) {
            foreach ($snap as $e => $s) {
                if ($s['active'] && $inScope($s['field'])) {
                    $employed[$e] = true;
                }
            }
        }
        foreach ($events as $m) {
            if ($m->type !== EmployeeMovements::TRANSFER && $inScope($m->office)) {
                $employed[(int) $m->employee_id] = true;
            }
        }

        $salaries = DB::table('salaries')->whereBetween('salary_month', [$from->toDateString(), $to->toDateString()])
            ->when($fieldIds !== null, fn ($q) => $q->whereIn('field_id', $fieldIds ?: [0]))
            ->pluck('employee_id')->map(fn ($id) => (int) $id)->flip();

        $noSalary = array_values(array_diff(array_keys($employed), array_keys($salaries->all())));
        $notEmployed = array_values(array_diff(array_keys($salaries->all()), array_keys($employed)));
        // Someone paid from another office's payroll is not "not employed": check across all offices.
        if ($fieldIds !== null && $notEmployed) {
            $notEmployed = array_values(array_filter($notEmployed, fn ($e) => ! (($opening[$e]['active'] ?? false) || ($closing[$e]['active'] ?? false))));
        }
        $names = DB::table('employees')->whereIn('id', array_merge($noSalary, $notEmployed) ?: [0])->pluck('name', 'id');
        $list = fn ($ids) => collect($ids)->map(fn ($id) => ['employee_id' => $id, 'name' => $names[$id] ?? 'Employee #' . $id])->sortBy('name')->values()->all();

        return [
            'employed' => count($employed),
            'salaries' => $salaries->count(),
            'no_salary' => $list($noSalary),
            'not_employed' => $list($notEmployed),
        ];
    }
}
