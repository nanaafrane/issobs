<?php

namespace App\Http\Controllers;

use App\Exports\EmployeeUpload\ErrorReportExport;
use App\Exports\EmployeeUpload\UpdateTemplateExport;
use App\Models\EmployeeImport;
use App\Support\EmployeeBulkImport;
use App\Support\EmployeeCreator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Bulk UPDATE of existing employees:
 *   1) download the filtered (or full) list from the employee list, pre-filled
 *   2) upload it back - every change is previewed, field by field (nothing saved)
 *   3) confirm - rows are checked again, then applied in one transaction
 * The "Employee ID" column is the key. Blank cells mean "leave unchanged".
 */
class EmployeeBulkUpdateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function pageData(): array
    {
        $user = Auth::user();

        return [
            'canUpdate' => EmployeeCreator::workflowFor($user, true) !== null,
            'needsApprover' => EmployeeCreator::needsApprover($user),
            'approvers' => EmployeeCreator::approversFor($user),
            'recent' => EmployeeImport::where('user_id', $user->id)->where('mode', 'update')->where('status', 'completed')->latest()->limit(5)->get(),
        ];
    }

    public function index()
    {
        return view('employees.bulk-update', $this->pageData());
    }

    /** Download: the employee list's current filters (or everyone in scope when there are none). */
    public function template(Request $request)
    {
        $user = Auth::user();
        abort_if(EmployeeCreator::workflowFor($user, true) === null, 403, 'Your role cannot edit employees.');

        $ids = app(EmployeeController::class)->bulkUpdateEmployeeIds($request);
        if (! $ids) {
            return back()->with('error', 'No employees match the current filters.');
        }
        if (count($ids) > EmployeeBulkImport::MAX_UPDATE_ROWS) {
            return back()->with('error', count($ids) . ' employees match. Narrow the filters to ' . EmployeeBulkImport::MAX_UPDATE_ROWS . ' or fewer (e.g. one field office) and download again.');
        }

        return (new UpdateTemplateExport($user, $ids, Gate::allows('view-salaries')))
            ->download('ISSOBS_Employee_Update_' . now()->format('Y-m-d_His') . '.xlsx');
    }

    public function preview(Request $request)
    {
        $user = Auth::user();
        abort_if(EmployeeCreator::workflowFor($user, true) === null, 403, 'Your role cannot edit employees.');

        $needsApprover = EmployeeCreator::needsApprover($user);
        $allowedApprovers = EmployeeCreator::approversFor($user)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:10240',
            'approver_id' => [$needsApprover ? 'required' : 'nullable', 'integer', function ($attr, $value, $fail) use ($allowedApprovers) {
                if ($value !== null && ! in_array((int) $value, $allowedApprovers, true)) {
                    $fail('Choose who to assign these changes to from the list.');
                }
            }],
        ], ['approver_id.required' => 'Choose who to assign these changes to.']);

        $stored = $request->file('file')->storeAs('employee-imports', Str::uuid() . '.xlsx', 'local');
        $import = EmployeeImport::create([
            'user_id' => $user->id,
            'mode' => 'update',
            'approver_id' => $request->input('approver_id'),
            'original_name' => $request->file('file')->getClientOriginalName(),
            'stored_path' => $stored,
        ]);

        return $this->showPreview($import);
    }

    public function show(EmployeeImport $import)
    {
        $this->authorizeImport($import);

        return $this->showPreview($import);
    }

    private function showPreview(EmployeeImport $import)
    {
        [$read, $checked] = $this->check($import);
        if ($read['error']) {
            return redirect()->route('employees.bulk-update')->withErrors(['file' => $read['error']]);
        }
        $import->update([
            'total_rows' => $checked['summary']['total'],
            'valid_rows' => $checked['summary']['changed'],
            'error_rows' => $checked['summary']['errors'],
        ]);

        return view('employees.bulk-update', $this->pageData() + [
            'import' => $import,
            'results' => $checked['results'],
            'summary' => $checked['summary'],
        ]);
    }

    public function confirm(Request $request, EmployeeImport $import)
    {
        $this->authorizeImport($import);
        abort_if($import->status !== 'previewed', 409, 'This file has already been applied.');

        $user = Auth::user();
        [$read, $checked] = $this->check($import);   // check again: data may have changed since the preview
        if ($read['error']) {
            return redirect()->route('employees.bulk-update')->withErrors(['file' => $read['error']]);
        }

        $apply = array_filter($checked['results'], fn ($r) => ! $r['errors'] && $r['changes']);
        if (! $apply) {
            return redirect()->route('employees.bulk-update.show', $import)->withErrors(['file' => 'There are no changes to apply.']);
        }
        $payment = count(array_filter($apply, fn ($r) => $r['payment_change']));
        if ($payment && ! $request->boolean('confirm_payment_changes')) {
            return redirect()->route('employees.bulk-update.show', $import)
                ->withErrors(['confirm_payment_changes' => "Tick the box to confirm the $payment bank / payment changes."]);
        }

        // All rows in one transaction: either every change is applied or none is.
        [$updated, $audit] = DB::transaction(function () use ($apply, $user, $import) {
            $updated = [];
            $audit = [];
            foreach ($apply as $row) {
                EmployeeCreator::update($row['employee_id'], $row['employee'], $row['pay'], $user, $import->approver_id);
                $updated[(string) $row['row']] = $row['employee_id'];
                $audit[$row['employee_id']] = array_map(fn ($c) => ['field' => $c[0], 'old' => $c[1], 'new' => $c[2]], $row['changes']);
            }
            return [$updated, $audit];
        });

        $import->update([
            'status' => 'completed',
            'created_count' => count($updated),
            'skipped_count' => $checked['summary']['errors'],
            'created_ids' => $updated,
            'changes' => $audit,
            'completed_at' => now(),
        ]);

        $message = count($updated) . ' employees updated from ' . $import->original_name . '.';
        if ($import->skipped_count) {
            $message .= ' ' . $import->skipped_count . ' rows with errors were skipped.';
        }

        return redirect()->route('employees.bulk-update')->with('success', $message)->with('completed_import', $import->id);
    }

    public function errors(EmployeeImport $import)
    {
        $this->authorizeImport($import);
        [$read, $checked] = $this->check($import);
        abort_if((bool) $read['error'], 422, $read['error'] ?? '');

        return (new ErrorReportExport($read['rows'], $checked['results'], true))
            ->download('Update_errors_' . pathinfo($import->original_name, PATHINFO_FILENAME) . '.xlsx');
    }

    private function check(EmployeeImport $import): array
    {
        $read = EmployeeBulkImport::read($import->stored_path, 'local', 'update');
        if ($read['error']) {
            return [$read, ['results' => [], 'summary' => []]];
        }
        $ids = array_values(array_unique(array_filter(array_map(fn ($v) => EmployeeBulkImport::parseEmployeeId($v['employee_id']), $read['rows']))));
        $allowed = app(EmployeeController::class)->visibleEmployeeIds($ids);

        return [$read, EmployeeBulkImport::validateUpdate($read['rows'], Auth::user(), $allowed, Gate::allows('view-salaries'))];
    }

    private function authorizeImport(EmployeeImport $import): void
    {
        abort_unless((int) $import->user_id === (int) Auth::id() && $import->mode === 'update', 404);
    }
}
