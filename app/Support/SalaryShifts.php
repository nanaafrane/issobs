<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Overtime;
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
            $from = Carbon::parse($start)->toDateString();
            $to = Carbon::parse($end)->toDateString();
            $ids = $group->pluck('employee_id')->unique()->map(fn ($id) => (int) $id)->all();
            $rows = Attendance::whereIn('employee_id', $ids)->whereBetween('work_date', [$from, $to])
                ->orderBy('work_date')->get(['employee_id', 'client_id', 'work_date', 'shift', 'status']);
            $overtime = Overtime::with('absentEmployee:id,name')->whereIn('ot_employee_id', $ids)
                ->whereDate('entry_date', '>=', $from)->whereDate('entry_date', '<=', $to)
                ->orderBy('entry_date')->get();
            $names = self::clientNames([...$rows->pluck('client_id')->all(), ...$overtime->pluck('client_id')->all()]);

            foreach ($ids as $empId) {
                $marks = $rows->where('employee_id', $empId);
                $ots = $overtime->where('ot_employee_id', $empId);
                if ($marks->isEmpty() && $ots->isEmpty()) {
                    continue;
                }
                $w = ['day' => 0, 'night' => 0, 'double' => 0, 'off' => 0, 'absent' => 0, 'sick' => 0, 'pass' => 0, 'pending' => 0, 'marks' => $marks->count()];
                $clients = [];
                foreach ($marks as $m) {
                    if ($m->status === Attendance::PRESENT) {
                        $w[$m->shift === 'night' ? 'night' : 'day']++;
                        $c = (int) $m->client_id;
                        $clients[$c] ??= ['name' => $names[$c] ?? 'Client #' . $c, 'day' => 0, 'night' => 0];
                        $clients[$c][$m->shift === 'night' ? 'night' : 'day']++;
                    } elseif (isset($w[$m->status])) {
                        $w[$m->status]++;
                    }
                }
                uasort($clients, fn ($x, $y) => ($y['day'] + $y['night']) <=> ($x['day'] + $x['night']));

                // Double duty: present on day and night of one date (where each was).
                $doubles = [];
                foreach ($marks->where('status', Attendance::PRESENT)->groupBy(fn ($m) => Carbon::parse($m->work_date)->toDateString()) as $date => $d) {
                    if ($d->pluck('shift')->unique()->count() > 1) {
                        $doubles[] = [
                            'date' => Carbon::parse($date)->format('D j M'),
                            'day' => $names[(int) $d->firstWhere('shift', 'day')?->client_id] ?? '-',
                            'night' => $names[(int) $d->firstWhere('shift', 'night')?->client_id] ?? '-',
                        ];
                    }
                }
                $w['double'] = count($doubles);

                // Overtime entries he worked this month (any state).
                $otRows = [];
                $ot = ['approved' => 0, 'amount' => 0.0, 'salary' => 0.0, 'cash' => 0.0, 'review' => 0, 'rejected' => 0];
                foreach ($ots as $o) {
                    $state = $o->isFullyApproved() ? 'approved' : ($o->isRejected() ? 'rejected' : 'review');
                    $ot[$state === 'approved' ? 'approved' : $state]++;
                    if ($state === 'approved') {
                        $ot['amount'] += (float) $o->amount;
                        $ot[$o->pay_by === 'salary' ? 'salary' : 'cash'] += (float) $o->amount;
                    }
                    $otRows[] = [
                        'date' => Carbon::parse($o->entry_date)->format('D j M'),
                        'shift' => ucfirst((string) $o->shift),
                        'client' => $names[(int) $o->client_id] ?? ($o->client_site_note ?: '-'),
                        'type' => method_exists($o, 'typeLabel') ? $o->typeLabel() : (string) $o->reason,
                        'for' => $o->absentEmployee?->name ?: ($o->absent_employee_note ?: ''),
                        'pay' => $o->pay_by === 'salary' ? 'With salary' : 'Cash',
                        'amount' => number_format((float) $o->amount, 2),
                        'state' => ['approved' => 'Approved', 'review' => 'In review', 'rejected' => 'Rejected'][$state],
                    ];
                }

                $w['clients'] = array_values($clients);
                $w['doubles'] = $doubles;
                $w['ot'] = $ot;
                $w['ot_rows'] = $otRows;
                $w['month'] = Carbon::parse($ym . '-01')->format('F Y');
                $worked[$ym][$empId] = $w;
            }
        }

        $out = [];
        foreach ($salaries as $s) {
            $ym = Carbon::parse($s->salary_month)->format('Y-m');
            $current = $now[(int) $s->employee_id] ?? null;
            $w = $worked[$ym][(int) $s->employee_id] ?? null;
            if ($w) {
                $w['name'] = $s->employee?->name;
                $w['home'] = (int) $s->client_id;
            }
            $out[$s->id] = [
                'shift' => $s->shift ?: $current,
                'snapshot' => (bool) $s->shift,
                'now' => $current,
                'worked' => $w,
            ];
        }

        return $out;
    }

    /** client id => business name (or name). */
    private static function clientNames(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        return $ids ? Client::whereIn('id', $ids)->get(['id', 'name', 'business_name'])
            ->mapWithKeys(fn ($c) => [(int) $c->id => $c->business_name ?: $c->name])->all() : [];
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

    /**
     * The WORKED cell: "26 shifts · 24 N · 2 D" with chips (double, absent, off, ...), the
     * clients he was present at, his overtime for the month, and a Details button that opens
     * the day-by-day breakdown (salaries._shift_detail_modal).
     */
    public static function worked(?array $w, ?string $shift): string
    {
        if (! $w || (! $w['marks'] && empty($w['ot_rows']))) {
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

        // Where he worked: his own client first-named by count; another client stands out.
        $clients = $w['clients'] ?? [];
        if ($clients) {
            $home = (int) ($w['home'] ?? 0);
            $parts = [];
            foreach (array_slice($clients, 0, 2) as $c) {
                $n = $c['day'] + $c['night'];
                $parts[] = '<span' . (count($clients) > 1 ? ' style="white-space:nowrap"' : '') . '>' . e($c['name']) . ' <strong>' . $n . '</strong></span>';
            }
            $more = count($clients) > 2 ? ' <span class="text-muted">+' . (count($clients) - 2) . ' more</span>' : '';
            $html .= '<div class="small text-muted mt-1" style="line-height:1.25"><i class="bx bx-buildings"></i> '
                . implode(' · ', $parts) . $more
                . (count($clients) > 1 ? $chip(count($clients) . ' clients', '#ecfdf3', '#067647', 'Present at more than one client this month') : '')
                . '</div>';
        }

        // Overtime he worked this month.
        $ot = $w['ot'] ?? null;
        if ($ot && ($ot['approved'] || $ot['review'] || $ot['rejected'])) {
            $line = '<i class="bx bx-time-five"></i> OT <strong>' . $ot['approved'] . '</strong> · GH₵ ' . number_format($ot['amount'], 2);
            $split = [];
            if ($ot['salary'] > 0) {
                $split[] = number_format($ot['salary'], 2) . ' with salary';
            }
            if ($ot['cash'] > 0) {
                $split[] = number_format($ot['cash'], 2) . ' cash';
            }
            $line .= $split ? ' <span class="text-muted">(' . implode(' · ', $split) . ')</span>' : '';
            if ($ot['review']) {
                $line .= $chip($ot['review'] . ' in review', '#fff7e6', '#a15c00', 'Overtime not yet fully approved: not paid yet');
            }
            if ($ot['rejected']) {
                $line .= $chip($ot['rejected'] . ' rejected', '#f2f4f7', '#475467', 'Rejected overtime: never paid');
            }
            $html .= '<div class="small mt-1" style="line-height:1.25">' . $line . '</div>';
        }

        $detail = [
            'name' => $w['name'] ?? '', 'month' => $w['month'] ?? '',
            'clients' => $clients, 'doubles' => $w['doubles'] ?? [], 'ot' => $w['ot_rows'] ?? [],
        ];

        return $html . '<div class="mt-1"><button type="button" class="btn btn-link btn-sm p-0 shift-detail-btn" style="font-size:.75rem" data-detail="'
            . e(json_encode($detail)) . '">Details <i class="bx bx-chevron-right"></i></button></div>';
    }
}
