<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FleetScope;
use App\Models\ExpenseType;
use App\Models\MaintenanceType;
use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use App\Services\Fleet\FleetReports;
use App\Services\Fleet\MaintenanceExpenses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Vehicle maintenance (roadworthy, insurance, tyres, repairs…): the office records the job,
 * branch review, finance approval; the approved job becomes one expense paid from Payments.
 * Also the maintenance types users can add, and what is due / expired.
 */
class MaintenanceController extends Controller
{
    use FleetScope;

    public function __construct(private MaintenanceExpenses $expenses, private FleetReports $reports)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        [$from, $to] = $this->period($request->hasAny(['month', 'from', 'to']) ? $request : $request->merge(['from' => now()->startOfYear()->toDateString(), 'to' => now()->toDateString()]));
        $allowed = $this->allowedFieldIds();
        $fieldId = $request->integer('field_id') ?: null;
        $vehicleId = $request->integer('vehicle_id') ?: null;
        $typeId = $request->integer('type_id') ?: null;
        if ($fieldId) {
            $this->mustSeeField($fieldId);
        }

        $rows = VehicleMaintenance::with(['vehicle', 'type', 'field', 'expense'])
            ->whereBetween('service_date', [$from->toDateString(), $to->toDateString()])
            ->when($allowed !== null, fn ($q) => $q->whereIn('field_id', $allowed ?: [0]))
            ->when($fieldId, fn ($q) => $q->where('field_id', $fieldId))
            ->when($vehicleId, fn ($q) => $q->where('vehicle_id', $vehicleId))
            ->when($typeId, fn ($q) => $q->where('maintenance_type_id', $typeId))
            ->orderByDesc('service_date')->orderByDesc('id')->get();

        $vehicles = Vehicle::where('status', '!=', 'disposed')->when($allowed !== null, fn ($q) => $q->whereIn('field_id', $allowed ?: [0]))->orderBy('reg_no')->get();
        $types = MaintenanceType::active()->orderBy('name')->get();
        $fields = $this->visibleFields();
        $due = $this->reports->due($fieldId ? [$fieldId] : $allowed)->whereIn('state', ['overdue', 'soon', 'missing']);
        $live = $rows->reject->isRejected();
        $byType = $live->groupBy(fn ($m) => $m->type?->name ?? 'Other')->map(fn ($g) => round((float) $g->sum('amount'), 2))->sortDesc();
        $user = Auth::user();

        return view('fleet.maintenance', compact('rows', 'from', 'to', 'fieldId', 'vehicleId', 'typeId', 'vehicles', 'types', 'fields', 'due', 'byType', 'live', 'user'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->mustSeeField($data['field_id']);
        $m = DB::transaction(function () use ($data) {
            $m = new VehicleMaintenance($data);
            $m->user_1 = Auth::id();
            $m->status_1 = 'approved';   // the creator's own confirmation
            $m->date_1 = now();
            $m->save();

            return $m;
        });

        return back()->with('success', $m->type->name . ' for ' . $m->vehicle->reg_no . ' recorded (GH₵ ' . number_format($m->amount, 2) . '). It now goes for branch review.');
    }

    public function edit(VehicleMaintenance $maintenance)
    {
        $this->mustSeeField($maintenance->field_id);
        abort_unless($maintenance->isEditableBy(Auth::user()), 403, 'This maintenance can no longer be edited.');
        $allowed = $this->allowedFieldIds();
        $vehicles = Vehicle::where('status', '!=', 'disposed')->when($allowed !== null, fn ($q) => $q->whereIn('field_id', $allowed ?: [0]))->orderBy('reg_no')->get();
        $types = MaintenanceType::where(fn ($q) => $q->where('is_active', true)->orWhere('id', $maintenance->maintenance_type_id))->orderBy('name')->get();
        $fields = $this->visibleFields();

        return view('fleet.maintenance_edit', compact('maintenance', 'vehicles', 'types', 'fields'));
    }

    public function update(Request $request, VehicleMaintenance $maintenance)
    {
        $this->mustSeeField($maintenance->field_id);
        abort_unless($maintenance->isEditableBy(Auth::user()), 403, 'This maintenance can no longer be edited.');
        $data = $this->validated($request);
        $this->mustSeeField($data['field_id']);
        DB::transaction(function () use ($maintenance, $data) {
            $maintenance->update($data);
            $this->expenses->sync($maintenance);   // approved and not paid: the expense follows the change
        });

        return redirect()->route('fleet.maintenance')->with('success', 'Maintenance updated.');
    }

    public function destroy(VehicleMaintenance $maintenance)
    {
        $this->mustSeeField($maintenance->field_id);
        abort_unless($maintenance->isEditableBy(Auth::user()), 403, 'This maintenance can no longer be deleted.');
        $this->expenses->remove($maintenance);

        return back()->with('success', 'Maintenance deleted.');
    }

    public function approve(VehicleMaintenance $maintenance)
    {
        return $this->decide($maintenance, 'approved');
    }

    public function reject(VehicleMaintenance $maintenance)
    {
        return $this->decide($maintenance, 'rejected');
    }

    private function decide(VehicleMaintenance $m, string $decision)
    {
        $stage = $m->currentStage();
        abort_if(is_null($stage), 404, 'There is no pending approval stage on this record.');
        abort_unless($m->canActOnStage(Auth::user(), $stage), 403);
        DB::transaction(function () use ($m, $stage, $decision) {
            $m->update(["user_{$stage}" => Auth::id(), "status_{$stage}" => $decision, "date_{$stage}" => now()]);
            $this->expenses->sync($m);
        });

        return back()->with('success', $m->type->name . ' for ' . $m->vehicle->reg_no . ' ' . $decision
            . ($m->fresh()->expense_id ? ': it is now an approved expense waiting to be paid (Payments › Pay approved expenses).' : '.'));
    }

    // ------------------------------------------------------------------
    // Maintenance types
    // ------------------------------------------------------------------

    public function types()
    {
        $types = MaintenanceType::with('expenseType')->withCount('records')->withSum('records', 'amount')->orderBy('name')->get();
        $expenseTypes = ExpenseType::orderBy('name')->get();
        $canManage = $this->canManageTypes();

        return view('fleet.maintenance_types', compact('types', 'expenseTypes', 'canManage'));
    }

    public function storeType(Request $request)
    {
        abort_unless($this->canManageTypes(), 403);
        $t = MaintenanceType::create($this->validatedType($request) + ['created_by' => Auth::id()]);

        return back()->with('success', 'Maintenance type "' . $t->name . '" added.');
    }

    public function updateType(Request $request, MaintenanceType $type)
    {
        abort_unless($this->canManageTypes(), 403);
        $type->update($this->validatedType($request, $type));

        return back()->with('success', 'Maintenance type "' . $type->name . '" saved.');
    }

    /** Finance, and the managers of each office (they know what their vehicles need). */
    private function canManageTypes(): bool
    {
        return $this->isFinance() || (Auth::user()?->hasRole(['Field Manager', 'Manager']) ?? false);
    }

    private function validatedType(Request $request, ?MaintenanceType $type = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('maintenance_types', 'name')->ignore($type?->id)],
            'expense_type_id' => ['nullable', 'integer', 'exists:expense_types,id'],
            'repeat_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'repeat_km' => ['nullable', 'integer', 'min:100', 'max:1000000'],
            'notes' => ['nullable', 'string', 'max:300'],
        ]);
        $data['is_document'] = $request->boolean('is_document');
        $data['is_active'] = $type ? $request->boolean('is_active') : true;

        return $data;
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'maintenance_type_id' => ['required', 'integer', 'exists:maintenance_types,id'],
            'field_id' => ['nullable', 'integer', 'exists:fields,id'],
            'service_date' => ['required', 'date', 'before_or_equal:today'],
            'vendor' => ['nullable', 'string', 'max:150'],
            'reference' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'odometer' => ['nullable', 'integer', 'min:0', 'max:5000000'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:service_date'],
            'next_due_km' => ['nullable', 'integer', 'min:0'],
        ], [], ['maintenance_type_id' => 'maintenance type', 'vehicle_id' => 'vehicle', 'valid_until' => 'valid until / next due']);

        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        $type = MaintenanceType::findOrFail($data['maintenance_type_id']);
        $data['field_id'] ??= $vehicle->field_id;
        // Defaults from the type: roadworthy / insurance valid 12 months, service every 5,000 km…
        if (empty($data['valid_until']) && $type->repeat_months) {
            $data['valid_until'] = \Carbon\Carbon::parse($data['service_date'])->addMonthsNoOverflow($type->repeat_months)->toDateString();
        }
        if (empty($data['next_due_km']) && $type->repeat_km && ! empty($data['odometer'])) {
            $data['next_due_km'] = (int) $data['odometer'] + (int) $type->repeat_km;
        }
        if ($type->is_document && empty($data['valid_until'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['valid_until' => $type->name . ' has an expiry date: enter it.']);
        }

        return $data;
    }
}
