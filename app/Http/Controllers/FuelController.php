<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FleetScope;
use App\Models\employee;
use App\Models\FuelEntry;
use App\Models\Vehicle;
use App\Services\Fleet\FuelEntries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Fuel request and usage: the office enters each fill-up; branch review, then finance approval;
 * approved fuel is paid through fuel pay-outs (FuelPayoutController).
 */
class FuelController extends Controller
{
    use FleetScope;

    public function __construct(private FuelEntries $entries)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        [$from, $to] = $this->period($request);
        $allowed = $this->allowedFieldIds();
        $fieldId = $request->integer('field_id') ?: null;
        $vehicleId = $request->integer('vehicle_id') ?: null;
        $state = in_array($request->input('state'), ['pending', 'approved', 'rejected', 'owed', 'paid'], true) ? $request->input('state') : null;
        if ($fieldId) {
            $this->mustSeeField($fieldId);
        }

        $rows = FuelEntry::with(['vehicle', 'field', 'driver', 'payout'])
            ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            ->when($allowed !== null, fn ($q) => $q->whereIn('field_id', $allowed ?: [0]))
            ->when($fieldId, fn ($q) => $q->where('field_id', $fieldId))
            ->when($vehicleId, fn ($q) => $q->where('vehicle_id', $vehicleId))
            ->when($state === 'pending', fn ($q) => $q->whereNull('status_3')->where(fn ($w) => $w->whereNull('status_2')->orWhere('status_2', '!=', 'rejected')))
            ->when($state === 'approved', fn ($q) => $q->where('status_3', 'approved'))
            ->when($state === 'rejected', fn ($q) => $q->where(fn ($w) => $w->where('status_2', 'rejected')->orWhere('status_3', 'rejected')))
            ->when($state === 'owed', fn ($q) => $q->owed())
            ->when($state === 'paid', fn ($q) => $q->whereNotNull('payout_id'))
            ->orderByDesc('entry_date')->orderByDesc('id')->get();

        $vehicles = Vehicle::with('field')->where('status', '!=', 'disposed')
            ->when($allowed !== null, fn ($q) => $q->whereIn('field_id', $allowed ?: [0]))->orderBy('reg_no')->get();
        $fields = $this->visibleFields();
        $drivers = $this->driverOptions();
        $stations = FuelEntry::whereNotNull('station')->distinct()->orderBy('station')->limit(50)->pluck('station');
        $live = $rows->reject->isRejected();
        $totals = [
            'amount' => round((float) $live->sum('amount'), 2),
            'litres' => round((float) $live->sum('litres'), 2),
            'diesel' => round((float) $live->where('fuel_type', 'diesel')->sum('litres'), 2),
            'super' => round((float) $live->where('fuel_type', 'super')->sum('litres'), 2),
            'owed' => round((float) $rows->filter(fn ($r) => $r->isFullyApproved() && ! $r->payout_id)->sum('amount'), 2),
            'pending' => round((float) $rows->filter(fn ($r) => ! $r->isFullyApproved() && ! $r->isRejected())->sum('amount'), 2),
        ];
        $user = Auth::user();

        return view('fleet.fuel', compact('rows', 'from', 'to', 'fieldId', 'vehicleId', 'state', 'vehicles', 'fields', 'drivers', 'stations', 'totals', 'user'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['field_id'] ??= Vehicle::find($data['vehicle_id'])?->field_id;
        $this->mustSeeField($data['field_id']);
        $entry = $this->entries->create($data);

        return back()->with('success', 'Fuel for ' . $entry->vehicle->reg_no . ' added (GH₵ ' . number_format($entry->amount, 2) . ', ' . number_format($entry->litres, 2) . ' L). It now goes for branch review.');
    }

    public function edit(FuelEntry $fuel)
    {
        $this->mustSeeField($fuel->field_id);
        abort_unless($fuel->isEditableBy(Auth::user()), 403, 'This entry can no longer be edited.');
        $allowed = $this->allowedFieldIds();
        $vehicles = Vehicle::where('status', '!=', 'disposed')->when($allowed !== null, fn ($q) => $q->whereIn('field_id', $allowed ?: [0]))->orderBy('reg_no')->get();
        $fields = $this->visibleFields();
        $drivers = $this->driverOptions($fuel->driver_employee_id);
        $stations = FuelEntry::whereNotNull('station')->distinct()->orderBy('station')->limit(50)->pluck('station');

        return view('fleet.fuel_edit', compact('fuel', 'vehicles', 'fields', 'drivers', 'stations'));
    }

    public function update(Request $request, FuelEntry $fuel)
    {
        $this->mustSeeField($fuel->field_id);
        abort_unless($fuel->isEditableBy(Auth::user()), 403, 'This entry can no longer be edited.');
        $data = $this->validated($request, $fuel);
        $data['field_id'] ??= Vehicle::find($data['vehicle_id'])?->field_id;
        $this->mustSeeField($data['field_id']);
        $this->entries->update($fuel, $data);

        return redirect()->route('fleet.fuel', ['month' => $fuel->entry_date->format('Y-m')])->with('success', 'Fuel entry updated.');
    }

    public function destroy(FuelEntry $fuel)
    {
        $this->mustSeeField($fuel->field_id);
        abort_unless($fuel->isEditableBy(Auth::user()), 403, 'This entry can no longer be deleted.');
        $fuel->delete();

        return back()->with('success', 'Fuel entry deleted.');
    }

    public function approve(FuelEntry $fuel)
    {
        return $this->decide($fuel, 'approved');
    }

    public function reject(FuelEntry $fuel)
    {
        return $this->decide($fuel, 'rejected');
    }

    /** Approve / reject the ticked entries at once (finance approving a day's sheet). */
    public function bulk(Request $request)
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer'], 'decision' => ['required', Rule::in(['approved', 'rejected'])]]);
        $done = 0;
        foreach (FuelEntry::whereIn('id', $request->input('ids'))->get() as $f) {
            $stage = $f->currentStage();
            if ($stage && $f->canActOnStage(Auth::user(), $stage) && ! $f->isLockedForPay()) {
                $f->update(["user_{$stage}" => Auth::id(), "status_{$stage}" => $request->input('decision'), "date_{$stage}" => now()]);
                $done++;
            }
        }

        return back()->with($done ? 'success' : 'error', $done ? $done . ' fuel ' . ($done === 1 ? 'entry' : 'entries') . ' ' . $request->input('decision') . '.' : 'None of the ticked entries were waiting for you.');
    }

    private function decide(FuelEntry $fuel, string $decision)
    {
        $stage = $fuel->currentStage();
        abort_if(is_null($stage), 404, 'There is no pending approval stage on this entry.');
        abort_unless($fuel->canActOnStage(Auth::user(), $stage), 403);
        $fuel->update(["user_{$stage}" => Auth::id(), "status_{$stage}" => $decision, "date_{$stage}" => now()]);

        return back()->with('success', 'Fuel entry ' . $decision . '.');
    }

    private function validated(Request $request, ?FuelEntry $current = null): array
    {
        return $request->validate([
            'entry_date' => ['required', 'date', 'before_or_equal:today'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'field_id' => ['nullable', 'integer', 'exists:fields,id'],
            'driver_employee_id' => ['nullable', 'integer', 'exists:employees,id', $this->driverRule($current?->driver_employee_id)],
            'purpose' => ['nullable', 'string', 'max:150'],
            'fuel_type' => ['nullable', Rule::in(array_keys(Vehicle::FUEL_TYPES))],
            'litres' => ['nullable', 'numeric', 'min:0.01', 'max:5000'],
            'price_per_litre' => ['nullable', 'numeric', 'min:0.01', 'max:1000'],
            'amount_requested' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'station' => ['nullable', 'string', 'max:100'],
            'odometer' => ['nullable', 'integer', 'min:0', 'max:5000000'],
            'receipt_no' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], ['amount' => 'fuel issued', 'vehicle_id' => 'vehicle', 'odometer' => 'km reading']);
    }
}
