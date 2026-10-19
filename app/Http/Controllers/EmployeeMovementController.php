<?php

namespace App\Http\Controllers;

use App\Exports\EmployeeMovementsExport;
use App\Models\employee;
use App\Models\EmployeeMovement;
use App\Models\Field;
use App\Support\EmployeeMovements;
use App\Support\EmployeeMovementsReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

/**
 * HR movements report (new recruits, re-instated, terminated, transfers) for a month or a
 * date range. Replaces the old NRRIT page; its URLs (employeesnrrit, employeesnrritView)
 * open this page.
 */
class EmployeeMovementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Head office sees every office; a field office its own office and sub-offices. */
    private function scope(): array
    {
        $user = Auth::user();
        $all = $user->hasRole(['Invoice', 'Finance Manager', 'Director', 'Internal Auditor'])
            || ($user->hasRole(['Manager']) && ($user->hasPermission('HR') || $user->hasPermission('Administration')));
        $ids = $all ? Field::orderBy('name')->pluck('id')->map(fn ($id) => (int) $id)->all()
            : Field::managedIds($user->field_id ? (int) $user->field_id : null);

        return [$all, $ids];
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string, 3: ?array, 4: bool, 5: array} */
    private function params(Request $request): array
    {
        [$all, $allowed] = $this->scope();
        $mode = $request->input('mode') === 'range' ? 'range' : 'month';
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->input('month')) ? $request->input('month') : now()->format('Y-m');
        if ($mode === 'range') {
            try {
                $from = Carbon::parse($request->input('from'));
                $to = Carbon::parse($request->input('to'));
            } catch (\Throwable) {
                $from = now()->startOfMonth();
                $to = now()->endOfMonth();
            }
            if ($to->lt($from)) {
                [$from, $to] = [$to, $from];
            }
        } else {
            $from = Carbon::parse($month . '-01');
            $to = $from->copy()->endOfMonth();
        }

        $field = $request->filled('field') ? (int) $request->input('field') : null;
        $fieldIds = $field && in_array($field, $allowed, true) ? Field::managedIds($field) : ($all ? null : $allowed);
        if ($fieldIds !== null) {
            $fieldIds = array_values(array_intersect($fieldIds, $allowed));
        }

        return [$from, $to, $mode, $fieldIds, $all, $allowed];
    }

    public function index(Request $request)
    {
        [$from, $to, $mode, $fieldIds, $all, $allowed] = $this->params($request);
        $report = EmployeeMovementsReport::build($from, $to, $fieldIds);
        $offices = Field::whereIn('id', $allowed ?: [0])->orderBy('name')->get(['id', 'name', 'parent_field_id']);

        $canCorrect = $this->canCorrect();

        return view('employees.movements', compact('report', 'mode', 'all', 'offices', 'canCorrect') + [
            'field' => $request->filled('field') ? (int) $request->input('field') : null,
            'month' => $from->format('Y-m'),
        ]);
    }

    public function export(Request $request)
    {
        [$from, $to, , $fieldIds] = $this->params($request);
        $report = EmployeeMovementsReport::build($from, $to, $fieldIds);
        $name = 'HR_MOVEMENTS_' . ($from->isSameMonth($to) && $from->day === 1 ? strtoupper($from->format('F_Y')) : $from->format('Ymd') . '-' . $to->format('Ymd')) . '.xlsx';

        return Excel::download(new EmployeeMovementsExport($report), $name);
    }

    /** Head office corrects dates and reasons (old records with an estimated or missing date). */
    private function canCorrect(): bool
    {
        $user = Auth::user();

        return $user->hasRole(['Invoice', 'Finance Manager']) || ($user->hasRole(['Manager']) && $user->hasPermission('HR'));
    }

    /**
     * Correct an approved movement's date (and a termination's reason / note). Used for the
     * history copied from old records, whose dates were estimated or unknown. What happened
     * (the type, the employee) never changes here.
     */
    public function update(Request $request, EmployeeMovement $movement)
    {
        abort_unless($this->canCorrect(), 403, 'Only head office can correct movements.');
        abort_unless($movement->status === EmployeeMovements::APPROVED, 422, 'Only approved movements can be corrected here.');
        $termination = $movement->type === EmployeeMovements::TERMINATION;
        $data = $request->validate([
            'effective_date' => ['required', 'date', 'after_or_equal:1990-01-01', 'before_or_equal:' . now()->addDays(90)->toDateString()],
            'reason' => $termination ? ['required', \Illuminate\Validation\Rule::in(array_keys(EmployeeMovements::REASONS))] : ['nullable'],
            'note' => [$termination && $request->input('reason') === 'other' ? 'required' : 'nullable', 'string', 'max:255'],
        ], ['note.required' => 'Say what the reason is when you choose "Other".']);

        $old = $movement->effective_date?->format('d M Y') ?? 'no date';
        $movement->effective_date = $data['effective_date'];
        $movement->date_estimated = false;
        if ($termination) {
            $movement->reason = $data['reason'];
        }
        if (array_key_exists('note', $data)) {
            $movement->note = $data['note'];
        }
        $movement->save();
        if (in_array($movement->type, [EmployeeMovements::TERMINATION, EmployeeMovements::REINSTATEMENT], true)) {
            EmployeeMovements::alignEstimatedHire($movement->employee_id, $movement->effective_date->toDateString());
        }

        // A corrected hire date also fills an empty date of joining.
        if ($movement->type === EmployeeMovements::HIRE) {
            employee::whereKey($movement->employee_id)->whereNull('date_of_joining')->update(['date_of_joining' => $data['effective_date']]);
        }

        $name = employee::whereKey($movement->employee_id)->value('name') ?? 'Employee #' . $movement->employee_id;

        return back()->with('success', EmployeeMovements::LABELS[$movement->type] . ' of ' . $name . ' corrected: ' . $old . ' → '
            . $movement->effective_date->format('d M Y') . '.');
    }

    /**
     * Undo a termination / re-instatement entered by mistake (head office / HR), or withdraw a
     * request still waiting (also the person who asked). Kept in the history as cancelled;
     * counts in nothing - a mistaken termination is not reported as terminated + re-instated.
     */
    public function undo(Request $request, EmployeeMovement $movement)
    {
        $data = $request->validate(['why' => ['required', 'string', 'min:5', 'max:150']],
            ['why.required' => 'Say why (e.g. "terminated the wrong guard").', 'why.min' => 'Say why in a few words.']);
        $pending = $movement->status === EmployeeMovements::PENDING;
        $error = $pending
            ? EmployeeMovements::withdraw($movement, Auth::user(), $data['why'])
            : EmployeeMovements::undo($movement, Auth::user(), $data['why']);
        if ($error) {
            return back()->with('error', $error);
        }
        $name = employee::whereKey($movement->employee_id)->value('name');

        return back()->with('success', ($pending ? 'Request withdrawn' : EmployeeMovements::LABELS[$movement->type] . ' undone')
            . ' for ' . $name . '. Status is now ' . employee::whereKey($movement->employee_id)->value('status') . '.');
    }

    /** Old NRRIT URLs: ?month=YYYY-MM keeps working. */
    public function legacy(Request $request)
    {
        return redirect()->route('employees.movements', array_filter(['month' => $request->input('month')]));
    }
}
