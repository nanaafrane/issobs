<?php

namespace App\Support\Attendance;

use App\Models\ClientShiftPlan;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Works out which shift (and which date) a scan belongs to.
 *
 *  - A night shift that runs past midnight belongs to the date it started on:
 *    with a 18:00-06:00 night, a scan at 02:00 on the 5th counts for the night of the 4th.
 *  - Guards arrive early, so a scan up to EARLY_MINUTES before their own shift
 *    starts counts for that shift (05:15 for a 06:00 day shift is the day shift).
 */
class ShiftClock
{
    public const EARLY_MINUTES = 120;
    public const LATE_AFTER_MINUTES = 60;

    /**
     * @param  string|null  $preferred  the guard's own shift at this client: day | night | rotate | null
     * @return array{date: Carbon, shift: string, in_own_shift: bool, late_minutes: int}
     */
    public static function resolve(CarbonInterface $at, ?ClientShiftPlan $plan, ?string $preferred = null): array
    {
        $times = self::times($plan);
        $at = Carbon::instance($at);

        // 1. The guard's own shift, allowing for an early arrival.
        if (in_array($preferred, ['day', 'night'], true)) {
            $own = self::windowFor($at, $preferred, $times, self::EARLY_MINUTES);
            if ($own) {
                return $own + ['in_own_shift' => true];
            }
        }

        // 2. Otherwise whichever shift the clock says it is.
        $hit = self::windowFor($at, 'day', $times, 0) ?? self::windowFor($at, 'night', $times, 0);
        if (! $hit) {
            // Gap between shifts (e.g. day 07:00-15:00, night 19:00-05:00): take the next one to start.
            $hit = self::windowFor($at, 'day', $times, self::EARLY_MINUTES)
                ?? self::windowFor($at, 'night', $times, self::EARLY_MINUTES)
                ?? ['date' => $at->copy()->startOfDay(), 'shift' => 'day', 'late_minutes' => 0];
        }

        $hit['in_own_shift'] = ! in_array($preferred, ['day', 'night'], true) || $hit['shift'] === $preferred;

        return $hit;
    }

    /** @return array{day_start:string,day_end:string,night_start:string,night_end:string} */
    public static function times(?ClientShiftPlan $plan): array
    {
        $t = ShiftPlans::DEFAULT_TIMES;
        if ($plan) {
            foreach ($t as $k => $default) {
                $t[$k] = ShiftPlans::time($plan->{$k}, $default);
            }
        }

        return $t;
    }

    /**
     * If $at falls in a $shift that started on $at's date or the date before (opening
     * $early minutes before the start), return that shift's date and how late $at is.
     */
    private static function windowFor(Carbon $at, string $shift, array $times, int $early): ?array
    {
        [$sh, $sm] = array_map('intval', explode(':', $times[$shift . '_start']));
        [$eh, $em] = array_map('intval', explode(':', $times[$shift . '_end']));

        foreach ([0, 1] as $daysBack) {
            $date = $at->copy()->startOfDay()->subDays($daysBack);
            $start = $date->copy()->setTime($sh, $sm);
            $end = $date->copy()->setTime($eh, $em);
            if ($end->lessThanOrEqualTo($start)) {
                $end->addDay();                       // crosses midnight
            }
            if ($at->greaterThanOrEqualTo($start->copy()->subMinutes($early)) && $at->lessThan($end)) {
                return [
                    'date' => $date,
                    'shift' => $shift,
                    'late_minutes' => max(0, (int) $start->diffInMinutes($at, false)),
                ];
            }
        }

        return null;
    }
}
