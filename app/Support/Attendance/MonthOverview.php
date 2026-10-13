<?php

namespace App\Support\Attendance;

use App\Models\Attendance;
use App\Models\Client;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every client's month on one screen: plan, guards posted, shortage, and
 * attendance totals. Same numbers as MonthSheet, worked out in a few queries.
 */
class MonthOverview
{
    /**
     * @param  Collection<int, Client>  $clients
     * @return Collection<int, array>
     */
    public static function rows(Collection $clients, CarbonInterface|string|null $month): Collection
    {
        $start = ShiftPlans::monthStart($month);
        $end = $start->copy()->endOfMonth()->startOfDay();
        $today = Carbon::today();
        $lastCounted = $end->lt($today) ? $end : $today;
        $elapsed = $lastCounted->lt($start) ? 0 : (int) $start->diffInDays($lastCounted) + 1;

        $ids = $clients->pluck('id')->map(fn ($id) => (int) $id)->all();
        $plans = ShiftPlans::forMonthMany($ids, $start);
        $posted = Roster::shiftCounts($ids);

        $counts = [];
        $cover = [];
        if ($ids) {
            $base = DB::table('attendances')->whereIn('client_id', $ids)
                ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()]);

            foreach ((clone $base)->selectRaw('client_id, status, COUNT(*) as n')->groupBy('client_id', 'status')->get() as $r) {
                $counts[(int) $r->client_id][$r->status] = (int) $r->n;
            }
            foreach ((clone $base)->where('status', Attendance::PRESENT)->where('work_date', '<=', $lastCounted->toDateString())
                ->selectRaw('client_id, work_date, shift, COUNT(*) as n')->groupBy('client_id', 'work_date', 'shift')->get() as $r) {
                $cover[(int) $r->client_id][substr((string) $r->work_date, 0, 10)][$r->shift] = (int) $r->n;
            }
        }

        return $clients->map(function (Client $client) use ($plans, $posted, $counts, $cover, $start, $elapsed) {
            $plan = $plans->get($client->id);
            $dayReq = (int) ($plan->day_guards ?? 0);
            $nightReq = (int) ($plan->night_guards ?? 0);
            $p = $posted[$client->id] ?? ['day' => 0, 'night' => 0, 'rotate' => 0, 'unset' => 0];
            $c = $counts[$client->id] ?? [];

            $uncovered = 0;
            for ($i = 0; $i < $elapsed; $i++) {
                $date = $start->copy()->addDays($i)->toDateString();
                $uncovered += max(0, $dayReq - ($cover[$client->id][$date]['day'] ?? 0))
                    + max(0, $nightReq - ($cover[$client->id][$date]['night'] ?? 0));
            }

            return [
                'client' => $client,
                'plan' => $plan,
                'required_day' => $dayReq,
                'required_night' => $nightReq,
                'required' => $dayReq + $nightReq,
                'posted' => array_sum($p),
                'posted_by_shift' => $p,
                'shortage' => Roster::shortage($dayReq, $nightReq, $p),
                'worked' => $c[Attendance::PRESENT] ?? 0,
                'paid' => collect(Attendance::PAID)->sum(fn ($s) => $c[$s] ?? 0),
                'absent' => $c[Attendance::ABSENT] ?? 0,
                'pending' => $c[Attendance::PENDING] ?? 0,
                'required_guard_days' => ($dayReq + $nightReq) * $elapsed,
                'uncovered_guard_days' => $plan ? $uncovered : null,
                'has_qr' => (bool) $client->attendance_token,
                'has_site' => $client->latitude !== null && $client->longitude !== null,
            ];
        })->values();
    }
}
