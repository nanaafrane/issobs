<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOvertimeRequest;
use App\Http\Requests\UpdateOvertimeRequest;
use App\Models\Client;
use App\Models\employee;
use App\Models\Field;
use App\Models\Attendance;
use App\Models\Overtime;
use App\Support\Attendance\Roster;
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
        // dd($date);

        $fieldId = $request->input('field_id', $user->field_id);
        // dd($fieldId);

        $fields = in_array($user->role?->name, ['Finance Manager', 'Director']) || ($user->department?->name === 'HR' && $user->role?->name === 'Manager') ? Field::all() : Field::where('id', $fieldId)->orWhere('parent_field_id', $fieldId)->get();
     
        // dd($fields->pluck('id'));

        $query = Overtime::with(['absentEmployee', 'client', 'otEmployee', 'officer', 'field', 'attendanceLinks'])
            ->whereDate('entry_date', $date);
        // dd($query);

        if ($fieldId) {
            $query->whereIn('field_id', $fields->pluck('id'));
        }

        $day = (clone $query)->where('shift', 'day')->orderBy('id')->get();
        $night = (clone $query)->where('shift', 'night')->orderBy('id')->get();

        // dd($day, $night);

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

        // allow for hr to acces overtime temporaly
        if($user->department?->name == 'HR' && $user->role?->name == 'Manager' )
            {
            return view('hr.index', compact(
            'date', 'fields', 'fieldId', 'day', 'night', 'dayTotal', 'nightTotal',
            'reasons', 'weekTotal', 'monthTotal'
        ));
            }
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
        $selectName = $request->input('select_name');

        $employees = employee::query()
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->when($request->input('field_id'), function ($q, $fid) {
                $q->where('field_id', $fid);
            })
            ->when($selectName === 'officer_id', function ($q) {
                $q->whereHas('role', fn($r) => $r->whereIn('name', ['Supervisor','Site Supervisor', 'Patrol Supervisor']));
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
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->when($request->input('field_id'), function ($q, $fid) {
                $q->where('field_id', $fid);
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

    /**
     * Step 2 of the entry form: the guards posted to the chosen client, with what the
     * attendance sheet already says for that date and shift, so it's clear who didn't show.
     */
    public function clientGuards(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'shift' => ['required', 'in:day,night'],
            'except' => ['nullable', 'integer'],   // overtime entry being edited
        ]);
        $client = Client::findOrFail($data['client_id']);
        $guards = Roster::guards($client);

        $records = Attendance::whereIn('employee_id', $guards->pluck('id')->all() ?: [0])
            ->where('work_date', $data['date'])->where('shift', $data['shift'])->get()->keyBy('employee_id');
        $onOvertime = Overtime::whereDate('entry_date', $data['date'])->where('shift', $data['shift'])
            ->whereIn('absent_employee_id', $guards->pluck('id')->all() ?: [0])
            ->when($data['except'] ?? null, fn ($q, $id) => $q->where('id', '!=', $id))
            ->pluck('id', 'absent_employee_id');

        $rows = $guards->map(function ($g) use ($records, $onOvertime, $data) {
            $r = $records->get($g->id);
            $status = match (true) {
                isset($onOvertime[$g->id]) => 'Already on overtime #' . $onOvertime[$g->id],
                ! $r => 'nothing recorded',
                $r->status === Attendance::PRESENT => 'present' . ($r->checked_in_at ? ' (signed in ' . $r->checked_in_at->format('H:i') . ')' : ''),
                default => strtolower(Attendance::LABELS[$r->status] ?? $r->status),
            };
            $shift = $g->attendance_shift;
            // Most likely missing first: rostered on this shift and not present.
            $rank = ($shift === $data['shift'] ? 0 : (in_array($shift, ['day', 'night'], true) ? 2 : 1))
                + ($r && $r->status === Attendance::PRESENT ? 3 : 0);

            return [
                'id' => $g->id,
                'text' => $g->name . ' · ' . ($shift ? ucfirst($shift) : 'no shift') . ' · ' . $status,
                'disabled' => isset($onOvertime[$g->id]),
                'rank' => $rank,
                'name' => $g->name,
            ];
        })->sortBy([['rank', 'asc'], ['name', 'asc']])->values();

        $group = fn ($label, $items) => $items->isEmpty() ? null : ['text' => $label, 'children' => $items->map(fn ($r) => array_intersect_key($r, array_flip(['id', 'text', 'disabled'])))->values()];

        return response()->json(array_values(array_filter([
            $group('On the ' . $data['shift'] . ' shift', $rows->where('rank', 0)),
            $group('Rotate / no shift set', $rows->where('rank', 1)),
            $group('Other shift, or already present', $rows->where('rank', '>=', 2)),
        ])));
    }

    /**
     * Step 3: guards who can fill the gap - active guards in this field (and its sub-fields)
     * who are not already working that date and shift (present / awaiting review on the
     * attendance sheet, or doing another overtime).
     */
    public function availableGuards(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'shift' => ['required', 'in:day,night'],
            'field_id' => ['nullable', 'integer'],
            'exclude' => ['nullable', 'string', 'max:20'],   // absent guard id; "vacant" / blank when nobody is named
            'except' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $busy = self::busyGuards($data['date'], $data['shift'], $data['except'] ?? null);
        if (! empty($data['exclude']) && ctype_digit((string) $data['exclude'])) {
            $busy[(int) $data['exclude']] = 'the absent guard';
        }

        $fieldIds = ! empty($data['field_id'])
            ? Field::where('id', $data['field_id'])->orWhere('parent_field_id', $data['field_id'])->pluck('id')->all()
            : [];

        $guards = employee::query()
            ->whereIn('status', Roster::ACTIVE_STATUSES)
            ->when($fieldIds, fn ($q) => $q->whereIn('field_id', $fieldIds))
            ->whereNotIn('id', array_keys($busy) ?: [0])
            ->when($data['q'] ?? null, fn ($q, $v) => $q->where(fn ($i) => $i->where('name', 'like', "%{$v}%")->orWhere('phone_number', 'like', "%{$v}%")))
            ->orderBy('name')
            ->limit(60)
            ->get(['id', 'name', 'phone_number', 'client_id']);

        $clients = Client::whereIn('id', $guards->pluck('client_id')->filter()->unique()->all() ?: [0])->get(['id', 'name', 'business_name'])->keyBy('id');
        $shifts = \Illuminate\Support\Facades\DB::table('client_employee')->whereIn('employee_id', $guards->pluck('id')->all() ?: [0])
            ->whereNotNull('shift')->orderBy('id')->get(['employee_id', 'client_id', 'shift'])
            ->mapWithKeys(fn ($r) => [$r->employee_id . ':' . $r->client_id => $r->shift]);

        $rows = $guards->map(function ($g) use ($clients, $shifts, $data) {
            $own = $shifts[$g->id . ':' . $g->client_id] ?? null;
            $c = $clients->get($g->client_id);

            return [
                'id' => $g->id,
                'text' => $g->name . ' · ' . ($c ? ($c->business_name ?: $c->name) : 'no client') . ($own ? ' (' . $own . ')' : ''),
                'group' => $own === null || $own === 'rotate' ? 1 : ($own === $data['shift'] ? 2 : 0),
            ];
        });

        $labels = [0 => 'Off this shift (works the other shift)', 1 => 'Rotate / no shift set', 2 => 'Rostered on this shift (check they are free)'];

        return response()->json($rows->groupBy('group')->sortKeys()->map(fn ($items, $g) => [
            'text' => $labels[$g],
            'children' => $items->map(fn ($r) => ['id' => $r['id'], 'text' => $r['text']])->values(),
        ])->values());
    }

    /** The O.T guard can't already be working that date and shift. */
    private function ensureOtGuardIsFree(array $data, ?int $exceptOvertime = null): void
    {
        $busy = self::busyGuards(Carbon::parse($data['entry_date'])->toDateString(), $data['shift'], $exceptOvertime);
        if (isset($busy[(int) $data['ot_employee_id']])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'ot_employee_id' => 'This guard is ' . $busy[(int) $data['ot_employee_id']] . '. Pick a guard who is free for this shift.',
            ]);
        }
        if (! empty($data['absent_employee_id']) && (int) $data['absent_employee_id'] === (int) $data['ot_employee_id']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['ot_employee_id' => 'The O.T guard can\'t be the absent guard.']);
        }
    }

    /**
     * [employee_id => why] for guards already working this date and shift:
     * present or awaiting review on the attendance sheet, or the O.T guard on another entry.
     */
    public static function busyGuards(string $date, string $shift, ?int $exceptOvertime = null): array
    {
        $busy = [];
        Attendance::where('work_date', $date)->where('shift', $shift)
            ->whereIn('status', [Attendance::PRESENT, Attendance::PENDING])
            ->whereNull('overtime_id')   // cells written by overtime are covered by the overtime query below
            ->get(['employee_id', 'status'])
            ->each(function ($r) use (&$busy) {
                $busy[(int) $r->employee_id] = 'already ' . ($r->status === Attendance::PRESENT ? 'present' : 'signed in (awaiting review)') . ' on the attendance sheet for this shift';
            });
        Overtime::whereDate('entry_date', $date)->where('shift', $shift)
            ->when($exceptOvertime, fn ($q) => $q->where('id', '!=', $exceptOvertime))
            ->get(['id', 'ot_employee_id'])
            ->each(function ($o) use (&$busy) {
                $busy[(int) $o->ot_employee_id] = 'already doing overtime #' . $o->id;
            });

        return $busy;
    }

    /** Daily page on the entry's own date and field, scrolled to and highlighting the entry. */
    public function open(int $id)
    {
        $overtime = Overtime::withTrashed()->find($id);
        if (! $overtime || $overtime->trashed()) {
            return redirect('overtime')->with('error', "Overtime entry #{$id} was deleted.");
        }

        return redirect(url('overtime') . '?' . http_build_query([
            'date' => $overtime->entry_date->format('Y-m-d'),
            'field_id' => $overtime->field_id,
            'highlight' => $overtime->id,
        ]) . '#ot-' . $overtime->id);
    }

    public function store(StoreOvertimeRequest $request)
    {
        $this->ensureOtGuardIsFree($request->validated());
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

        $this->ensureOtGuardIsFree($request->validated(), $overtime->id);
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
        $user = Auth::user();
        [$from, $to] = match ($period) {
            'daily' => [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay()],
            'weekly' => [$anchor->copy()->startOfWeek(), $anchor->copy()->endOfWeek()],
            'monthly' => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
            'quarterly' => [$anchor->copy()->startOfQuarter(), $anchor->copy()->endOfQuarter()],
            'semiannual' => ($anchor->month <= 6)
                ? [$anchor->copy()->startOfYear(), $anchor->copy()->startOfYear()->addMonths(5)->endOfMonth()]
                : [$anchor->copy()->startOfYear()->addMonths(6), $anchor->copy()->endOfYear()],
            'yearly' => [$anchor->copy()->startOfYear(), $anchor->copy()->endOfYear()],
            default => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
        };

        $base = Overtime::whereBetween('entry_date', [$from, $to]);

        $total = (clone $base)->sum('amount');
        $count = (clone $base)->count();

        $byField = (clone $base)->join('fields', 'fields.id', '=', 'overtimes.field_id')
            ->select('fields.id as field_id', 'fields.name as field_name', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('fields.id', 'fields.name')->orderByDesc('total')->get();

        $byShift = (clone $base)->select('shift', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('shift')->get();

        // For reasons, count distinct absent employees (people who didn't go to work)
        $byReason = (clone $base)
            ->whereNotNull('absent_employee_id')
            ->select('reason', DB::raw('COUNT(DISTINCT absent_employee_id) as cnt'), DB::raw('SUM(amount) as total'))
            ->groupBy('reason')
            ->orderByDesc('cnt')
            ->get();

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
        $trendLabels = $trend->map(fn ($point) => Carbon::parse($point->entry_date)->format('D, j M Y'));

        // Simple next-period projection: average of the last 4 comparable
        // periods, so a branch head can anticipate next month/week's overtime
        // exposure at a glance (see DESIGN.md for the reasoning).
        $projection = $this->projectNextPeriod($period, $anchor);

         if($user->department?->name == 'HR' && $user->role?->name == 'Manager' )
            {
                return view('hr.report', compact(
                    'period', 'anchor', 'from', 'to', 'total', 'count',
                    'byField', 'byShift', 'byReason', 'topClients', 'topEmployees', 'trend', 'trendLabels', 'projection'
                ));
            }

        return view('overtime.report', compact(
            'period', 'anchor', 'from', 'to', 'total', 'count',
            'byField', 'byShift', 'byReason', 'topClients', 'topEmployees', 'trend', 'trendLabels', 'projection'
        ));
    }

    /**
     * Return clients for a field and employees involved in shortage/absent
     * (used by the report's modal drilldowns).
     */
    public function fieldDetails(Request $request, $fieldId)
    {
        // Accept optional period + date (anchor) to match the report window
        $period = $request->input('period', 'monthly');
        $anchor = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();

        [$from, $to] = match ($period) {
            'daily' => [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay()],
            'weekly' => [$anchor->copy()->startOfWeek(), $anchor->copy()->endOfWeek()],
            'monthly' => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
            'quarterly' => [$anchor->copy()->startOfQuarter(), $anchor->copy()->endOfQuarter()],
            'semiannual' => ($anchor->month <= 6)
                ? [$anchor->copy()->startOfYear(), $anchor->copy()->startOfYear()->addMonths(5)->endOfMonth()]
                : [$anchor->copy()->startOfYear()->addMonths(6), $anchor->copy()->endOfYear()],
            'yearly' => [$anchor->copy()->startOfYear(), $anchor->copy()->endOfYear()],
            default => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
        };

        // Clients with counts within the same window for this field
        $clients = (clone Overtime::query())
            ->join('clients', 'clients.id', '=', 'overtimes.client_id')
            ->where('overtimes.field_id', $fieldId)
            ->whereBetween('overtimes.entry_date', [$from, $to])
            ->select('clients.id', 'clients.business_name', 'clients.name', DB::raw('COUNT(*) as cnt'), DB::raw('SUM(overtimes.amount) as total'))
            ->groupBy('clients.id', 'clients.business_name', 'clients.name')
            ->orderByDesc('cnt')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'business_name' => $c->business_name,
                'name' => $c->name,
                'entries' => (int) $c->cnt,
                'total' => (float) $c->total,
            ]);

        // Employees involved in Shortage/Absent within the same window for this field
        $entries = Overtime::with(['otEmployee', 'absentEmployee'])
            ->where('field_id', $fieldId)
            ->whereIn('reason', ['Shortage', 'Absent'])
            ->whereBetween('entry_date', [$from, $to])
            ->get();

        $employeeNames = collect();
        foreach ($entries as $e) {
            if ($e->otEmployee) $employeeNames->push($e->otEmployee->name);
            if ($e->absentEmployee) $employeeNames->push($e->absentEmployee->name);
        }

        $employees = $employeeNames->unique()->values()->map(fn($n) => ['name' => $n]);

        return response()->json(['clients' => $clients, 'employees' => $employees]);
    }

    /**
     * Return clients and employees related to a specific reason (drilldown).
     */
    public function reasonDetails(Request $request, $reason)
    {
        // Return only employees who didn't go to work for this reason within the same report window
        $period = $request->input('period', 'monthly');
        $anchor = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();

        [$from, $to] = match ($period) {
            'daily' => [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay()],
            'weekly' => [$anchor->copy()->startOfWeek(), $anchor->copy()->endOfWeek()],
            'monthly' => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
            'quarterly' => [$anchor->copy()->startOfQuarter(), $anchor->copy()->endOfQuarter()],
            'semiannual' => ($anchor->month <= 6)
                ? [$anchor->copy()->startOfYear(), $anchor->copy()->startOfYear()->addMonths(5)->endOfMonth()]
                : [$anchor->copy()->startOfYear()->addMonths(6), $anchor->copy()->endOfYear()],
            'yearly' => [$anchor->copy()->startOfYear(), $anchor->copy()->endOfYear()],
            default => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
        };

        $employees = (clone Overtime::query())
            ->join('employees', 'employees.id', '=', 'overtimes.absent_employee_id')
            ->where('overtimes.reason', $reason)
            ->whereBetween('overtimes.entry_date', [$from, $to])
            ->whereNotNull('overtimes.absent_employee_id')
            ->select('employees.id', 'employees.name', DB::raw('COUNT(*) as occurrences'))
            ->groupBy('employees.id', 'employees.name')
            ->orderByDesc('occurrences')
            ->get()
            ->map(fn($e) => ['id' => $e->id, 'name' => $e->name, 'occurrences' => (int) $e->occurrences]);

        return response()->json(['employees' => $employees]);
    }

    /**
     * Return absent employees and replacement employees for a client in the selected window.
     */
    public function clientDetails(Request $request, $clientId)
    {
        $period = $request->input('period', 'monthly');
        $anchor = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();

        [$from, $to] = match ($period) {
            'daily' => [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay()],
            'weekly' => [$anchor->copy()->startOfWeek(), $anchor->copy()->endOfWeek()],
            'monthly' => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
            'quarterly' => [$anchor->copy()->startOfQuarter(), $anchor->copy()->endOfQuarter()],
            'semiannual' => ($anchor->month <= 6)
                ? [$anchor->copy()->startOfYear(), $anchor->copy()->startOfYear()->addMonths(5)->endOfMonth()]
                : [$anchor->copy()->startOfYear()->addMonths(6), $anchor->copy()->endOfYear()],
            'yearly' => [$anchor->copy()->startOfYear(), $anchor->copy()->endOfYear()],
            default => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
        };

        // Absent employees for this client
        $absent = (clone Overtime::query())
            ->join('employees', 'employees.id', '=', 'overtimes.absent_employee_id')
            ->where('overtimes.client_id', $clientId)
            ->whereBetween('overtimes.entry_date', [$from, $to])
            ->whereNotNull('overtimes.absent_employee_id')
            ->select('employees.id', 'employees.name')
            ->distinct()
            ->orderBy('employees.name')
            ->get()
            ->map(fn($e) => ['id' => $e->id, 'name' => $e->name]);

        // Replacement employees (who worked overtime for this client where someone was absent)
        $replacements = (clone Overtime::query())
            ->join('employees', 'employees.id', '=', 'overtimes.ot_employee_id')
            ->where('overtimes.client_id', $clientId)
            ->whereBetween('overtimes.entry_date', [$from, $to])
            ->whereNotNull('overtimes.absent_employee_id')
            ->whereNotNull('overtimes.ot_employee_id')
            ->select('employees.id', 'employees.name', DB::raw('COUNT(*) as entries'), DB::raw('SUM(overtimes.amount) as total'))
            ->groupBy('employees.id', 'employees.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => ['id' => $r->id, 'name' => $r->name, 'entries' => (int)$r->entries, 'total' => (float)$r->total]);

        return response()->json(['absent' => $absent, 'replacements' => $replacements]);
    }

    /**
     * Return clients where an employee worked overtime in the selected window.
     */
    public function employeeDetails(Request $request, $employeeId)
    {
        $period = $request->input('period', 'monthly');
        $anchor = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();

        [$from, $to] = match ($period) {
            'daily' => [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay()],
            'weekly' => [$anchor->copy()->startOfWeek(), $anchor->copy()->endOfWeek()],
            'monthly' => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
            'quarterly' => [$anchor->copy()->startOfQuarter(), $anchor->copy()->endOfQuarter()],
            'semiannual' => ($anchor->month <= 6)
                ? [$anchor->copy()->startOfYear(), $anchor->copy()->startOfYear()->addMonths(5)->endOfMonth()]
                : [$anchor->copy()->startOfYear()->addMonths(6), $anchor->copy()->endOfYear()],
            'yearly' => [$anchor->copy()->startOfYear(), $anchor->copy()->endOfYear()],
            default => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
        };

        $clients = (clone Overtime::query())
            ->join('clients', 'clients.id', '=', 'overtimes.client_id')
            ->where('overtimes.ot_employee_id', $employeeId)
            ->whereBetween('overtimes.entry_date', [$from, $to])
            ->select('clients.id', 'clients.business_name', 'clients.name', DB::raw('COUNT(*) as entries'), DB::raw('SUM(overtimes.amount) as total'))
            ->groupBy('clients.id', 'clients.business_name', 'clients.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'business_name' => $c->business_name, 'name' => $c->name, 'entries' => (int)$c->entries, 'total' => (float)$c->total]);

        return response()->json(['clients' => $clients]);
    }

    protected function projectNextPeriod(string $period, Carbon $anchor): float
    {
        $samples = [];

        for ($i = 1; $i <= 4; $i++) {
            if ($period === 'daily') {
                $from = $anchor->copy()->subDays($i)->startOfDay();
                $to = $anchor->copy()->subDays($i)->endOfDay();
            } elseif ($period === 'weekly') {
                $from = $anchor->copy()->subWeeks($i)->startOfWeek();
                $to = $anchor->copy()->subWeeks($i)->endOfWeek();
            } elseif ($period === 'monthly') {
                $from = $anchor->copy()->subMonths($i)->startOfMonth();
                $to = $anchor->copy()->subMonths($i)->endOfMonth();
            } elseif ($period === 'quarterly') {
                $sub = $anchor->copy()->subMonths(3 * $i);
                $from = $sub->copy()->startOfQuarter();
                $to = $sub->copy()->endOfQuarter();
            } elseif ($period === 'semiannual') {
                $sub = $anchor->copy()->subMonths(6 * $i);
                if ($sub->month <= 6) {
                    $from = $sub->copy()->startOfYear();
                    $to = $sub->copy()->startOfYear()->addMonths(5)->endOfMonth();
                } else {
                    $from = $sub->copy()->startOfYear()->addMonths(6);
                    $to = $sub->copy()->endOfYear();
                }
            } elseif ($period === 'yearly') {
                $from = $anchor->copy()->subYears($i)->startOfYear();
                $to = $anchor->copy()->subYears($i)->endOfYear();
            } else {
                $from = $anchor->copy()->subMonths($i)->startOfMonth();
                $to = $anchor->copy()->subMonths($i)->endOfMonth();
            }

            $samples[] = Overtime::whereBetween('entry_date', [$from, $to])->sum('amount');
        }

        return $samples ? round(array_sum($samples) / count($samples), 2) : 0.0;
    }
}
