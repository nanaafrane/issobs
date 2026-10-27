<?php

namespace App\Services\Fleet;

use App\Models\FuelEntry;
use App\Models\MaintenanceType;
use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Fleet figures by the day the fuel was drawn / the work was done (not when it was paid —
 * the cash book shows when money left an account).
 *
 *  vehicleSummary()  per vehicle for a period: litres, fuel cost, maintenance by type, total,
 *                    km driven, km per litre, cost per km.
 *  due()             renewals and services that have expired or fall due soon.
 *
 * Rejected entries are left out. Pending ones are counted (they are real fuel / work) and
 * shown separately as "not yet approved".
 */
class FleetReports
{
    /**
     * @param  array<int>|null  $fieldIds  null = every office
     * @return array{rows: Collection, types: Collection, totals: array}
     */
    public function vehicleSummary(Carbon $from, Carbon $to, ?array $fieldIds = null): array
    {
        $range = [$from->toDateString(), $to->toDateString()];
        $notRejected = fn ($q) => $q->where(fn ($w) => $w->whereNull('status_2')->orWhere('status_2', '!=', 'rejected'))
            ->where(fn ($w) => $w->whereNull('status_3')->orWhere('status_3', '!=', 'rejected'));

        $fuel = FuelEntry::whereBetween('entry_date', $range)->where($notRejected)
            ->when($fieldIds !== null, fn ($q) => $q->whereIn('field_id', $fieldIds ?: [0]))
            ->orderBy('entry_date')->orderBy('id')->get()->groupBy('vehicle_id');
        $work = VehicleMaintenance::with('type')->whereBetween('service_date', $range)->where($notRejected)
            ->when($fieldIds !== null, fn ($q) => $q->whereIn('field_id', $fieldIds ?: [0]))
            ->get()->groupBy('vehicle_id');

        $vehicleIds = $fuel->keys()->merge($work->keys())->unique();
        $vehicles = Vehicle::with('field')->whereIn('id', $vehicleIds)
            ->orWhere(fn ($q) => $q->where('status', 'active')->when($fieldIds !== null, fn ($w) => $w->whereIn('field_id', $fieldIds ?: [0])))
            ->orderBy('reg_no')->get();

        $types = collect();
        $rows = $vehicles->map(function (Vehicle $v) use ($fuel, $work, &$types) {
            $f = $fuel->get($v->id, collect());
            $w = $work->get($v->id, collect());
            $byType = $w->groupBy(fn ($m) => $m->type?->name ?? 'Other')->map(fn ($g) => round((float) $g->sum('amount'), 2));
            $types = $types->merge($byType->keys());

            $consumption = $this->consumption($f);
            $fuelCost = round((float) $f->sum('amount'), 2);
            $maint = round((float) $w->sum('amount'), 2);
            $km = $consumption['km'] ?? $this->kmFromReadings($f->pluck('odometer')->merge($w->pluck('odometer')));

            return [
                'vehicle' => $v,
                'fills' => $f->count(),
                'litres' => round((float) $f->sum('litres'), 2),
                'fuel' => $fuelCost,
                'fuel_pending' => round((float) $f->filter(fn ($e) => ! $e->isFullyApproved())->sum('amount'), 2),
                'maintenance' => $maint,
                'by_type' => $byType,
                'total' => round($fuelCost + $maint, 2),
                'km' => $km,
                'km_per_litre' => $consumption['km_per_litre'],
                'cost_per_km' => $km ? round(($fuelCost + $maint) / $km, 2) : null,
            ];
        })->filter(fn ($r) => $r['total'] > 0 || $r['vehicle']->status === 'active')->values();

        $types = $types->unique()->sort()->values();

        return [
            'rows' => $rows,
            'types' => $types,
            'totals' => [
                'litres' => round($rows->sum('litres'), 2),
                'fuel' => round($rows->sum('fuel'), 2),
                'maintenance' => round($rows->sum('maintenance'), 2),
                'total' => round($rows->sum('total'), 2),
                'by_type' => $types->mapWithKeys(fn ($t) => [$t => round($rows->sum(fn ($r) => (float) ($r['by_type'][$t] ?? 0)), 2)]),
            ],
        ];
    }

    /**
     * Full-tank method: km between the first and last reading in the period, over the litres put
     * in AFTER the first reading (the first fill-up was used before it).
     *
     * @return array{km: ?int, km_per_litre: ?float}
     */
    public function consumption(Collection $fills): array
    {
        $read = $fills->filter(fn ($e) => $e->odometer !== null)->sortBy([['odometer', 'asc'], ['entry_date', 'asc']])->values();
        if ($read->count() < 2) {
            return ['km' => null, 'km_per_litre' => null];
        }
        $first = $read->first();
        $last = $read->last();
        $km = (int) $last->odometer - (int) $first->odometer;
        $litres = (float) $fills->filter(fn ($e) => $e->id !== $first->id && $e->odometer !== null && $e->odometer > $first->odometer)->sum('litres');

        return ['km' => $km > 0 ? $km : null, 'km_per_litre' => $km > 0 && $litres > 0 ? round($km / $litres, 2) : null];
    }

    private function kmFromReadings(Collection $readings): ?int
    {
        $r = $readings->filter()->map(fn ($v) => (int) $v);

        return $r->count() >= 2 && $r->max() > $r->min() ? $r->max() - $r->min() : null;
    }

    /**
     * For every active vehicle and every tracked maintenance type that the vehicle has had (and
     * every document type — roadworthy / insurance — even if never recorded): when it is due.
     *
     * @param  array<int>|null  $fieldIds
     * @return Collection<int, array{vehicle: Vehicle, type: MaintenanceType, last: ?VehicleMaintenance,
     *                     due_date: ?Carbon, due_km: ?int, current_km: ?int, state: string, days: ?int}>
     */
    public function due(?array $fieldIds = null, int $soonDays = 30, ?int $vehicleId = null): Collection
    {
        $vehicles = Vehicle::with('field')->where('status', 'active')
            ->when($fieldIds !== null, fn ($q) => $q->whereIn('field_id', $fieldIds ?: [0]))
            ->when($vehicleId, fn ($q) => $q->whereKey($vehicleId))
            ->orderBy('reg_no')->get();
        if ($vehicles->isEmpty()) {
            return collect();
        }
        $types = MaintenanceType::active()->get()->filter->isTracked()->keyBy('id');
        $latest = VehicleMaintenance::whereIn('vehicle_id', $vehicles->pluck('id'))->whereIn('maintenance_type_id', $types->keys())
            ->where(fn ($w) => $w->whereNull('status_2')->orWhere('status_2', '!=', 'rejected'))
            ->where(fn ($w) => $w->whereNull('status_3')->orWhere('status_3', '!=', 'rejected'))
            ->orderBy('service_date')->orderBy('id')->get()
            ->groupBy(fn ($m) => $m->vehicle_id . ':' . $m->maintenance_type_id)->map->last();

        $kmNow = FuelEntry::whereIn('vehicle_id', $vehicles->pluck('id'))->groupBy('vehicle_id')->selectRaw('vehicle_id, MAX(odometer) AS km')->pluck('km', 'vehicle_id');
        $kmWork = VehicleMaintenance::whereIn('vehicle_id', $vehicles->pluck('id'))->groupBy('vehicle_id')->selectRaw('vehicle_id, MAX(odometer) AS km')->pluck('km', 'vehicle_id');
        $today = now()->startOfDay();

        $out = collect();
        foreach ($vehicles as $v) {
            $current = max((int) ($kmNow[$v->id] ?? 0), (int) ($kmWork[$v->id] ?? 0), (int) $v->opening_odometer) ?: null;
            foreach ($types as $t) {
                $last = $latest->get($v->id . ':' . $t->id);
                if (! $last && ! $t->is_document) {
                    continue;   // a service never done on this vehicle is not tracked until it is first recorded
                }
                $dueDate = $last?->valid_until
                    ?? ($last && $t->repeat_months ? $last->service_date->copy()->addMonthsNoOverflow($t->repeat_months) : null);
                $dueKm = $last?->next_due_km ?? ($last && $t->repeat_km && $last->odometer ? (int) $last->odometer + (int) $t->repeat_km : null);

                $days = $dueDate ? (int) $today->diffInDays($dueDate, false) : null;
                $kmLeft = $dueKm && $current ? $dueKm - $current : null;
                $state = match (true) {
                    ! $last => 'missing',
                    ($days !== null && $days < 0) || ($kmLeft !== null && $kmLeft < 0) => 'overdue',
                    ($days !== null && $days <= $soonDays) || ($kmLeft !== null && $kmLeft <= 500) => 'soon',
                    default => 'ok',
                };
                $out->push(['vehicle' => $v, 'type' => $t, 'last' => $last, 'due_date' => $dueDate, 'due_km' => $dueKm,
                    'current_km' => $current, 'km_left' => $kmLeft, 'state' => $state, 'days' => $days]);
            }
        }

        $order = ['overdue' => 0, 'missing' => 1, 'soon' => 2, 'ok' => 3];

        return $out->sortBy(fn ($r) => sprintf('%d-%08d-%s', $order[$r['state']], ($r['days'] ?? 99999999) + 50000, $r['vehicle']->reg_no))->values();
    }
}
