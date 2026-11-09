<?php

namespace App\Support;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Shifts on the salary pages (salariesCreate, edit, Cash / MoMo, Bank, Client and their
 * hold pages), so Finance can see each salary against the guard's shift and what he
 * actually worked that month.
 *
 *   shift    salaries.shift: the shift at his client when the salaries were generated.
 *            Older salaries have none: the current roster shift is shown, marked "now".
 *   worked   from that month's attendance: day / night shifts present, double duties
 *            (present day and night on one date), off, absent, sick, pass, pending review.
 */
class SalaryShifts
{
    public const LOOK = [
        'day' => ['Day', 'bx-sun', '#fff4d6', '#8a5a00'],
        'night' => ['Night', 'bx-moon', '#e3e8f7', '#1e2a5a'],
        'rotate' => ['Rotate', 'bx-refresh', '#f0e6fb', '#5b2a86'],
    ];

    /** [employee_id => shift] at each guard's client, from the roster (latest row wins). */
    public static function current(array $employeeClient): array
    {
        $employeeClient = array_filter($employeeClient);
        if (! $employeeClient) {
            return [];
        }
        $out = [];
        $rows = DB::table('client_employee')->whereIn('employee_id', array_keys($employeeClient))
            ->whereIn('client_id', array_values(array_unique($employeeClient)))->whereNotNull('shift')
            ->orderBy('id')->get(['employee_id', 'client_id', 'shift']);
        foreach ($rows as $r) {
            if ((int) ($employeeClient[(int) $r->employee_id] ?? 0) === (int) $r->client_id) {
                $out[(int) $r->employee_id] = $r->shift;
            }
        }

        return $out;
    }

    /**
     * Shift and attendance for a page of salaries.
     *
     * @return array<int, array{shift: ?string, snapshot: bool, now: ?string, worked: array}> by salary id
     */
    public static function forSalaries(iterable $salaries): array
    {
        $salaries = collect($salaries)->filter();
        if ($salaries->isEmpty()) {
            return [];
        }
        $now = self::current($salaries->mapWithKeys(fn ($s) => [(int) $s->employee_id => (int) $s->client_id])->all());

        $worked = [];
        foreach ($salaries->groupBy(fn ($s) => Carbon::parse($s->salary_month)->format('Y-m')) as $ym => $group) {
            [$start, $end] = PayrollMonth::span(Carbon::parse($ym . '-01'));
            $rows = Attendance::whereIn('employee_id', $group->pluck('employee_id')->unique()->all())
                ->whereBetween('work_date', [Carbon::parse($start)->toDateString(), Carbon::parse($end)->toDateString()])
                ->get(['employee_id', 'work_date', 'shift', 'status']);
            foreach ($rows->groupBy('employee_id') as $empId => $marks) {
                $w = ['day' => 0, 'night' => 0, 'double' => 0, 'off' => 0, 'absent' => 0, 'sick' => 0, 'pass' => 0, 'pending' => 0, 'marks' => $marks->count()];
                foreach ($marks as $m) {
                    if ($m->status === Attendance::PRESENT) {
                        $w[$m->shift === 'night' ? 'night' : 'day']++;
                    } elseif (isset($w[$m->status])) {
                        $w[$m->status]++;
                    }
                }
                $w['double'] = $marks->where('status', Attendance::PRESENT)
                    ->groupBy(fn ($m) => Carbon::parse($m->work_date)->toDateString())
                    ->filter(fn ($d) => $d->pluck('shift')->unique()->count() > 1)->count();
                $worked[$ym][(int) $empId] = $w;
            }
        }

        $out = [];
        foreach ($salaries as $s) {
            $ym = Carbon::parse($s->salary_month)->format('Y-m');
            $current = $now[(int) $s->employee_id] ?? null;
            $out[$s->id] = [
                'shift' => $s->shift ?: $current,
                'snapshot' => (bool) $s->shift,
                'now' => $current,
                'worked' => $worked[$ym][(int) $s->employee_id] ?? null,
            ];
        }

        return $out;
    }

    /** Counts for the bar above a table: day / night / rotate / not set. */
    public static function counts(array $info): array
    {
        $c = ['day' => 0, 'night' => 0, 'rotate' => 0, 'none' => 0];
        foreach ($info as $i) {
            $c[$i['shift'] && isset($c[$i['shift']]) ? $i['shift'] : 'none']++;
        }

        return $c;
    }

    /** The shift badge: icon + word, same look on every page. */
    public static function badge(?string $shift, bool $snapshot = true, ?string $now = null): string
    {
        if (! $shift || ! isset(self::LOOK[$shift])) {
            return '<span class="shift-badge" title="No shift set on the attendance sheet" style="display:inline-flex;align-items:center;gap:.2rem;padding:.1rem .45rem;border-radius:1rem;'
                . 'border:1px dashed #b8bec6;color:#6b7280;font-size:.75rem;white-space:nowrap">No shift</span>';
        }
        [$label, $icon, $bg, $fg] = self::LOOK[$shift];
        $title = $snapshot
            ? 'Shift when the salaries were generated' . ($now && $now !== $shift ? ' (now ' . self::LOOK[$now][0] . ')' : '')
            : 'Current shift on the attendance sheet (this salary was generated before shifts were kept on salaries)';
        $extra = $snapshot && $now && $now !== $shift ? ' <span style="opacity:.75">→ ' . e(self::LOOK[$now][0]) . ' now</span>' : '';
        $mark = $snapshot ? '' : ' <span style="opacity:.7;font-weight:400">now</span>';

        return '<span class="shift-badge" title="' . e($title) . '" style="display:inline-flex;align-items:center;gap:.2rem;padding:.1rem .5rem;border-radius:1rem;'
            . 'background:' . $bg . ';color:' . $fg . ';font-size:.75rem;font-weight:600;white-space:nowrap"><i class="bx ' . $icon . '"></i>' . e($label) . $mark . $extra . '</span>';
    }

    /** "26 shifts · 24 N · 2 D" with chips for doubles, absences, off days, pending review. */
    public static function worked(?array $w, ?string $shift): string
    {
        if (! $w || ! $w['marks']) {
            return '<span class="text-muted small">No attendance</span>';
        }
        $total = $w['day'] + $w['night'];
        $chip = fn ($text, $bg, $fg, $title) => ' <span title="' . e($title) . '" style="display:inline-block;padding:0 .4rem;border-radius:.6rem;background:' . $bg . ';color:' . $fg . ';font-size:.72rem;white-space:nowrap">' . e($text) . '</span>';
        $html = '<span style="white-space:nowrap"><strong>' . $total . '</strong> <span class="small">shift' . ($total === 1 ? '' : 's') . '</span>'
            . ' <span class="text-muted small">' . $w['night'] . ' N · ' . $w['day'] . ' D</span></span>';
        if (in_array($shift, ['day', 'night'], true) && $total >= 4) {
            $other = $shift === 'day' ? $w['night'] : $w['day'];
            if ($other > $total / 2) {
                $html .= $chip('mostly ' . ($shift === 'day' ? 'night' : 'day'), '#fff1d6', '#9a5b00', 'Most shifts worked are not on his rostered ' . $shift . ' shift');
            }
        }
        if ($w['double']) {
            $html .= $chip($w['double'] . ' double', '#e0ecff', '#1d4ed8', 'Dates present on both day and night (double duty)');
        }
        if ($w['absent']) {
            $html .= $chip($w['absent'] . ' absent', '#fde2e1', '#b42318', 'Shifts marked Absent');
        }
        if ($w['sick'] || $w['pass']) {
            $html .= $chip(trim(($w['sick'] ? $w['sick'] . ' sick ' : '') . ($w['pass'] ? $w['pass'] . ' pass' : '')), '#eef2f6', '#475467', 'Sick / on pass');
        }
        if ($w['off']) {
            $html .= $chip($w['off'] . ' off', '#f2f4f7', '#475467', 'Off days');
        }
        if ($w['pending']) {
            $html .= $chip($w['pending'] . ' to review', '#fff7e6', '#a15c00', 'Scans waiting for operations review: not counted yet');
        }

        return $html;
    }
}
