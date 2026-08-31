<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOvertimeRequest;
use App\Http\Requests\UpdateOvertimeRequest;
use App\Models\Client;
use App\Models\employee;
use App\Models\Field;
use App\Models\Overtime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OvertimeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Daily entry grid (the digitised version of the OVERTIME_TEMPLATE
     * sheet) - one working day, split Day/Night, with a running total per
     * shift and a "add another row" quick-entry form at the bottom of
     * each shift, matching how the paper/Excel sheet is actually filled
     * in daily by branch staff.
     */
    public function index(Request $request)
    {
        $date = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();
        $user = Auth::user();

        $fieldId = $request->input('field_id', $user->field_id);

        $fields = in_array($user->role?->name, ['Finance Manager', 'Director'])
            || ($user->department?->name === 'HR' && $user->role?->name === 'Manager')
                ? Field::all()
                : Field::where('id', $fieldId)->orWhere('parent_field_id', $fieldId)->get();

        $query = Overtime::with(['absentEmployee', 'client', 'otEmployee', 'officer', 'field'])
            ->whereDate('entry_date', $date);

        if ($fieldId) {
            $query->whereIn('field_id', $fields->pluck('id'));
        }

        $day = (clone $query)->where('shift', 'day')->orderBy('id')->get();
        $night = (clone $query)->where('shift', 'night')->orderBy('id')->get();

        $dayTotal = $day->sum('amount');
        $nightTotal = $night->sum('amount');

        $reasons = Overtime::REASONS;

        // quick KPI strip: today / this week / this month for the visible field(s)
        $scopeIds = $fields->pluck('id');
        $weekTotal = Overtime::whereIn('field_id', $scopeIds)
            ->whereBetween('entry_date', [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()])
            ->sum('amount');
        $monthTotal = Overtime::whereIn('field_id', $scopeIds)
            ->whereMonth('entry_date', $date->month)->whereYear('entry_date', $date->year)
            ->sum('amount');

        return view('overtime.index', compact(
            'date', 'fields', 'fieldId', 'day', 'night', 'dayTotal', 'nightTotal',
            'reasons', 'weekTotal', 'monthTotal'
        ));
    }

    /**
     * Searchable employee lookup, used by the select2 fields for
     * EMPLOYEE NAME / O.T EMPLOYEE NAME / OFFICER ON DUTY.
     */
    public function employeeOptions(Request $request)
    {
        $term = $request->input('q', $request->input('term'));

        $employees = employee::query()
            ->where(function ($query) {
                $query->whereRaw('LOWER(status) = ?', ['active'])
                    ->orWhereRaw('LOWER(status) = ?', ['approved']);
            })
            ->when($term, function ($query, $value) {
                $query->where(function ($inner) use ($value) {
                    $inner->where('name', 'like', "%{$value}%")
                        ->orWhere('phone_number', 'like', "%{$value}%");
                });
            })
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'phone_number', 'field_id', 'role_id']);

        return response()->json($employees->map(fn ($e) => [
            'id' => $e->id,
            'text' => $e->name . ($e->phone_number ? " ({$e->phone_number})" : ''),
        ]));
    }

    /** Searchable client lookup, used by the CLIENTS select2 field. */
    public function clientOptions(Request $request)
    {
        $term = $request->input('q', $request->input('term'));

        $clients = Client::query()
            ->where(function ($query) {
                $query->whereRaw('LOWER(status) = ?', ['active'])
                    ->orWhereRaw('LOWER(status) = ?', ['approved']);
            })
            ->when($term, function ($query, $value) {
                $query->where(function ($inner) use ($value) {
                    $inner->where('business_name', 'like', "%{$value}%")
                        ->orWhere('name', 'like', "%{$value}%");
                });
            })
            ->orderBy('business_name')
            ->limit(25)
            ->get(['id', 'name', 'business_name']);

        return response()->json($clients->map(fn ($c) => [
            'id' => $c->id,
            'text' => trim(($c->business_name ?: $c->name) . ($c->business_name && $c->name ? " - {$c->name}" : '')),
        ]));
    }

    public function store(StoreOvertimeRequest $request)
    {
        $overtime = Overtime::create(array_merge($request->validated(), [
            'user_1' => Auth::id(),
            'status_1' => 'approved', // creator's own confirmation, same convention as Expense
            'date_1' => now(),
        ]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'row' => $overtime->load(['absentEmployee', 'client', 'otEmployee', 'officer']),
            ]);
        }

        return back()->with('success', 'Overtime entry added.');
    }

    public function update(UpdateOvertimeRequest $request, Overtime $overtime)
    {
        abort_unless($overtime->isEditableBy(Auth::user()), 403,
            'This entry can no longer be edited - Branch Review has already started.');

        $overtime->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Overtime entry updated.');
    }

    public function destroy(Overtime $overtime)
    {
        abort_unless($overtime->isEditableBy(Auth::user()), 403,
            'This entry can no longer be deleted - Branch Review has already started.');

        $overtime->delete();

        return back()->with('success', 'Overtime entry deleted.');
    }

    public function approve(Overtime $overtime)
    {
        $stage = $overtime->currentStage();
        abort_if(is_null($stage), 404, 'There is no pending approval stage on this entry.');
        abort_unless($overtime->canActOnStage(Auth::user(), $stage), 403);

        $overtime->update([
            "user_{$stage}" => Auth::id(),
            "status_{$stage}" => 'approved',
            "date_{$stage}" => now(),
        ]);

        return back()->with('success', 'Overtime entry approved.');
    }

    public function reject(Overtime $overtime)
    {
        $stage = $overtime->currentStage();
        abort_if(is_null($stage), 404, 'There is no pending approval stage on this entry.');
        abort_unless($overtime->canActOnStage(Auth::user(), $stage), 403);

        $overtime->update([
            "user_{$stage}" => Auth::id(),
            "status_{$stage}" => 'rejected',
            "date_{$stage}" => now(),
        ]);

        return back()->with('success', 'Overtime entry rejected.');
    }

    // -----------------------------------------------------------------
    // Reporting: daily / weekly / monthly / yearly, with breakdowns by
    // field office, client and OT employee, plus a trend chart feed for
    // ApexCharts (already used elsewhere in ISSOBS).
    // -----------------------------------------------------------------

    public function report(Request $request)
    {
        $period = $request->input('period', 'monthly'); // daily|weekly|monthly|yearly
        $anchor = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();

        [$from, $to] = match ($period) {
            'daily' => [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay()],
            'weekly' => [$anchor->copy()->startOfWeek(), $anchor->copy()->endOfWeek()],
            'yearly' => [$anchor->copy()->startOfYear(), $anchor->copy()->endOfYear()],
            default => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
        };

        $base = Overtime::whereBetween('entry_date', [$from, $to]);

        $total = (clone $base)->sum('amount');
        $count = (clone $base)->count();

        $byField = (clone $base)->join('fields', 'fields.id', '=', 'overtimes.field_id')
            ->select('fields.name as field_name', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('fields.name')->orderByDesc('total')->get();

        $byShift = (clone $base)->select('shift', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('shift')->get();

        $byReason = (clone $base)->select('reason', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('reason')->orderByDesc('total')->get();

        $topClients = (clone $base)->join('clients', 'clients.id', '=', 'overtimes.client_id')
            ->select('clients.id', 'clients.business_name', 'clients.name', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('clients.id', 'clients.business_name', 'clients.name')
            ->orderByDesc('total')->limit(10)->get();

        $topEmployees = (clone $base)->join('employees', 'employees.id', '=', 'overtimes.ot_employee_id')
            ->select('employees.id', 'employees.name', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('employees.id', 'employees.name')
            ->orderByDesc('total')->limit(10)->get();

        // Trend line: daily buckets across the selected window, for the chart
        $trend = (clone $base)->select('entry_date', DB::raw('SUM(amount) as total'))
            ->groupBy('entry_date')->orderBy('entry_date')->get();

        // Simple next-period projection: average of the last 4 comparable
        // periods, so a branch head can anticipate next month/week's overtime
        // exposure at a glance (see DESIGN.md for the reasoning).
        $projection = $this->projectNextPeriod($period, $anchor);

        return view('overtime.report', compact(
            'period', 'anchor', 'from', 'to', 'total', 'count',
            'byField', 'byShift', 'byReason', 'topClients', 'topEmployees', 'trend', 'projection'
        ));
    }

    protected function projectNextPeriod(string $period, Carbon $anchor): float
    {
        $samples = [];

        for ($i = 1; $i <= 4; $i++) {
            [$from, $to] = match ($period) {
                'daily' => [$anchor->copy()->subDays($i)->startOfDay(), $anchor->copy()->subDays($i)->endOfDay()],
                'weekly' => [$anchor->copy()->subWeeks($i)->startOfWeek(), $anchor->copy()->subWeeks($i)->endOfWeek()],
                'yearly' => [$anchor->copy()->subYears($i)->startOfYear(), $anchor->copy()->subYears($i)->endOfYear()],
                default => [$anchor->copy()->subMonths($i)->startOfMonth(), $anchor->copy()->subMonths($i)->endOfMonth()],
            };

            $samples[] = Overtime::whereBetween('entry_date', [$from, $to])->sum('amount');
        }

        return $samples ? round(array_sum($samples) / count($samples), 2) : 0.0;
    }
}
