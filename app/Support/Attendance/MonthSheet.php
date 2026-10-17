<?php

namespace App\Support\Attendance;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\ClientShiftPlan;
use App\Models\employee;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * One client's month, laid out like the Excel time sheet: a row per guard, a
 * column per day, plus totals and day-by-day cover against the shift plan.
 *
 * Differences from the spreadsheet, on purpose:
 *  - Absent counts only days marked absent. The sheet used "31 - present", which
 *    made a new recruit's days before joining (the "-" cells) count as absent.
 *  - OFF / SICK / PASS are kept apart from present but still count as paid days
 *    (the sheet's legend gives each of them 1).
 *  - Scans held for review ("?") count for nothing until operations approve them.
 */
class MonthSheet
{
    public Carbon $month;
    public ?ClientShiftPlan $plan;
    /** @var array<int, array{date: Carbon, day: int, weekday: string, future: bool}> */
    public array $days = [];
    /** @var array<int, array> */
    public array $rows = [];
    /** @var array<int, array{day: int, night: int}> present guards per date and shift */
    public array $cover = [];
    public array $totals = [];

    public function __construct(public Client $client, CarbonInterface|string|null $month)
    {
        $this->month = ShiftPlans::monthStart($month);
        $this->plan = ShiftPlans::forMonth($client->id, $this->month);
        $this->build();
    }

    private function build(): void
    {
        $end = $this->month->copy()->endOfMonth()->startOfDay();
        $today = Carbon::today();
        for ($d = $this->month->copy(); $d->lte($end); $d->addDay()) {
            $this->days[$d->day] = ['date' => $d->copy(), 'day' => $d->day, 'weekday' => $d->format('D'), 'future' => $d->gt($today)];
            $this->cover[$d->day] = ['day' => 0, 'night' => 0];
        }

        $records = Attendance::where('client_id', $this->client->id)
            ->whereBetween('work_date', [$this->month->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('employee_id');

        // Guards posted now, plus anyone with records this month who has since moved on.
        $guards = Roster::guards($this->client)->keyBy('id');
        $former = $records->keys()->diff($guards->keys())->all();
        if ($former) {
            foreach (employee::whereIn('id', $former)->orderBy('name')->get(['id', 'name', 'phone_number', 'location', 'client_id', 'status']) as $e) {
                $e->attendance_shift = null;
                $e->former = true;
                $guards[$e->id] = $e;
            }
        }

        $elapsed = collect($this->days)->reject(fn ($d) => $d['future'])->count();
        $sum = ['worked' => 0, 'paid' => 0, 'absent' => 0, 'pending' => 0, 'unmarked' => 0];

        foreach ($guards as $guard) {
            /** @var Collection $mine */
            $mine = $records->get($guard->id, collect());
            $cells = [];
            foreach ($mine as $r) {
                $cells[$r->work_date->day][$r->shift] = $r;
                if ($r->status === Attendance::PRESENT) {
                    $this->cover[$r->work_date->day][$r->shift]++;
                }
            }

            $row = [
                'employee' => $guard,
                'shift' => $guard->attendance_shift,
                'former' => (bool) ($guard->former ?? false),
                'cells' => $cells,
                'worked' => $mine->where('status', Attendance::PRESENT)->count(),
                'paid' => $mine->whereIn('status', Attendance::PAID)->count(),
                'absent' => $mine->where('status', Attendance::ABSENT)->count(),
                'pending' => $mine->where('status', Attendance::PENDING)->count(),
            ];
            $markedDays = collect($cells)->keys()->filter(fn ($day) => ! $this->days[$day]['future'])->count();
            $row['unmarked'] = $row['former'] ? 0 : max(0, $elapsed - $markedDays);

            foreach ($sum as $k => $_) {
                $sum[$k] += $row[$k];
            }
            $this->rows[] = $row;
        }

        $dayReq = (int) ($this->plan->day_guards ?? 0);
        $nightReq = (int) ($this->plan->night_guards ?? 0);
        $shortfall = 0;
        foreach ($this->days as $n => $d) {
            if (! $d['future']) {
                $shortfall += max(0, $dayReq - $this->cover[$n]['day']) + max(0, $nightReq - $this->cover[$n]['night']);
            }
        }

        $posted = ['day' => 0, 'night' => 0, 'rotate' => 0, 'unset' => 0];
        foreach ($this->rows as $row) {
            if (! $row['former']) {
                $posted[$row['shift'] && isset(Roster::SHIFTS[$row['shift']]) ? $row['shift'] : 'unset']++;
            }
        }
        $postedTotal = array_sum($posted);

        $this->totals = $sum + [
            'required' => $dayReq + $nightReq,
            'required_day' => $dayReq,
            'required_night' => $nightReq,
            'posted' => $postedTotal,
            'posted_by_shift' => $posted,
            // "TOTAL SHORTAGES" on the Excel sheet: guards the plan needs that are not posted.
            'shortage' => Roster::shortage($dayReq, $nightReq, $posted),
            'elapsed_days' => $elapsed,
            'required_guard_days' => ($dayReq + $nightReq) * $elapsed,
            // Shift slots nobody was present for, so far this month (what an ABSENT DEDUCTION would bill).
            'uncovered_guard_days' => $shortfall,
        ];
    }

    /** Grid code for one guard on one day: "P", "A", "OFF", "?", "P/P" for a double shift, "" when unmarked. */
    public static function code(array $cell): string
    {
        return collect(['day', 'night'])
            ->filter(fn ($s) => isset($cell[$s]))
            ->map(function ($s) use ($cell) {
                $r = $cell[$s];
                // Overtime awaiting approval: show the reason it will become, e.g. "S?".
                if ($r->status === Attendance::PENDING && $r->overtime_id && ($to = ShiftReasons::guardStatus($r->reason) ?? ($r->reason === 'Present' ? Attendance::PRESENT : null))) {
                    return Attendance::CODES[$to] . '?';
                }

                return Attendance::CODES[$r->status] ?? '?';
            })
            ->implode('/');
    }

    /** CSS class for a cell, from the "worst" status in it. */
    public static function css(array $cell): string
    {
        $statuses = collect($cell)->pluck('status');
        foreach ([Attendance::PENDING => 'att-pending', Attendance::ABSENT => 'att-absent', Attendance::TERMINATED => 'att-terminated', Attendance::PRESENT => 'att-present'] as $s => $css) {
            if ($statuses->contains($s)) {
                return $css;
            }
        }

        return $statuses->isEmpty() ? '' : 'att-leave';
    }
}
