<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FleetScope;
use App\Models\employee;
use App\Models\FuelEntry;
use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use App\Services\Fleet\FleetReports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** The vehicle register, one vehicle's history, and the monthly fleet cost report. */
class VehicleController extends Controller
{
    use FleetScope;

    public function __construct(private FleetReports $reports)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $allowed = $this->allowedFieldIds();
        $status = array_key_exists((string) $request->input('status'), Vehicle::STATUSES) ? $request->input('status') : 'active';
        $vehicles = Vehicle::with(['field', 'driver'])
            ->when($allowed !== null, fn ($q) => $q->whereIn('field_id', $allowed ?: [0]))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderBy('reg_no')->get();
        $due = $this->reports->due($allowed)->whereIn('state', ['overdue', 'soon', 'missing']);
        $fields = $this->visibleFields();
        $drivers = $this->driverOptions();

        return view('fleet.vehicles', compact('vehicles', 'due', 'fields', 'drivers', 'status'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->mustSeeField($data['field_id'] ?? null);
        $v = Vehicle::create($data + ['user_id' => Auth::id()]);

        return redirect()->route('fleet.vehicles.show', $v)->with('success', $v->reg_no . ' added to the fleet.');
    }

    public function show(Request $request, Vehicle $vehicle)
    {
        $this->mustSeeField($vehicle->field_id);
        $vehicle->load(['field', 'driver']);
        if (! $request->hasAny(['month', 'from', 'to'])) {
            $request->merge(['from' => now()->startOfYear()->toDateString(), 'to' => now()->toDateString()]);   // default: this year
        }
        [$from, $to] = $this->period($request);
        $fuel = FuelEntry::with(['payout', 'driver'])->where('vehicle_id', $vehicle->id)->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('entry_date')->orderByDesc('id')->get();
        $work = VehicleMaintenance::with(['type', 'expense'])->where('vehicle_id', $vehicle->id)->whereBetween('service_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('service_date')->orderByDesc('id')->get();
        $summary = $this->reports->vehicleSummary($from, $to)['rows']->firstWhere('vehicle.id', $vehicle->id);
        $due = $this->reports->due(null, 30, $vehicle->id);
        $fields = $this->visibleFields();
        $drivers = $this->driverOptions($vehicle->driver_employee_id);

        return view('fleet.vehicle_show', compact('vehicle', 'fuel', 'work', 'summary', 'due', 'from', 'to', 'fields', 'drivers'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $this->mustSeeField($vehicle->field_id);
        $data = $this->validated($request, $vehicle);
        $this->mustSeeField($data['field_id'] ?? null);
        $vehicle->update($data);

        return redirect()->route('fleet.vehicles.show', $vehicle)->with('success', $vehicle->reg_no . ' updated.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $this->mustSeeField($vehicle->field_id);
        if (FuelEntry::withTrashed()->where('vehicle_id', $vehicle->id)->exists() || VehicleMaintenance::withTrashed()->where('vehicle_id', $vehicle->id)->exists()) {
            return back()->with('error', $vehicle->reg_no . ' has fuel or maintenance history: mark it "Sold / disposed" instead.');
        }
        $vehicle->delete();

        return redirect()->route('fleet.vehicles')->with('success', $vehicle->reg_no . ' removed.');
    }

    /** Monthly fleet cost: per vehicle fuel, maintenance by type, km, km/L, cost per km. */
    public function report(Request $request)
    {
        [$from, $to] = $this->period($request);
        $allowed = $this->allowedFieldIds();
        $fieldId = $request->integer('field_id') ?: null;
        if ($fieldId) {
            $this->mustSeeField($fieldId);
        }
        $report = $this->reports->vehicleSummary($from, $to, $fieldId ? [$fieldId] : $allowed);
        $fields = $this->visibleFields();

        return view('fleet.report', compact('report', 'from', 'to', 'fields', 'fieldId'));
    }

    private function validated(Request $request, ?Vehicle $vehicle = null): array
    {
        $request->merge(['reg_no' => Vehicle::normaliseReg($request->input('reg_no'))]);

        $data = $request->validate([
            'reg_no' => ['required', 'string', 'max:30', Rule::unique('vehicles', 'reg_no')->ignore($vehicle?->id)],
            'name' => ['nullable', 'string', 'max:120'],
            'kind' => ['required', Rule::in(array_keys(Vehicle::KINDS))],
            'fuel_type' => ['required', Rule::in(array_keys(Vehicle::FUEL_TYPES))],
            'field_id' => ['required', 'integer', 'exists:fields,id'],
            'driver_employee_id' => ['nullable', 'integer', 'exists:employees,id', $this->driverRule($vehicle?->driver_employee_id)],
            'tank_litres' => ['nullable', 'numeric', 'min:0', 'max:2000'],
            'opening_odometer' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(array_keys(Vehicle::STATUSES))],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], ['reg_no' => 'registration number', 'field_id' => 'office']);
        $data['status'] = $data['status'] ?? ($vehicle?->status ?? 'active');

        return $data;
    }
}
