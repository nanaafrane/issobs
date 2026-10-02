<?php

namespace App\Http\Controllers;

use App\Models\EmployeeImport;
use App\Support\EmployeeBulkImport;
use App\Support\EmployeeCreator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Exports\EmployeeUpload\ErrorReportExport;
use App\Exports\EmployeeUpload\TemplateExport;

/**
 * Bulk employee upload: 1) upload  2) preview (nothing saved)  3) confirm.
 * Every row is checked again at confirm time, so a phone number or account
 * taken by someone else since the preview is still caught.
 */
class EmployeeImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function pageData(): array
    {
        $user = Auth::user();

        return [
            'canCreate' => EmployeeCreator::workflowFor($user) !== null,
            'needsApprover' => EmployeeCreator::needsApprover($user),
            'approvers' => EmployeeCreator::approversFor($user),
            'recent' => EmployeeImport::where('user_id', $user->id)->where('mode', 'create')->where('status', 'completed')->latest()->limit(5)->get(),
        ];
    }

    public function index()
    {
        return view('employees.import', $this->pageData());
    }

    public function template()
    {
        return (new TemplateExport(Auth::user()))->download('ISSOBS_Employee_Upload_Template.xlsx');
    }

    public function preview(Request $request)
    {
        $user = Auth::user();
        abort_if(EmployeeCreator::workflowFor($user) === null, 403, 'Your role cannot create employees.');

        $needsApprover = EmployeeCreator::needsApprover($user);
        $allowedApprovers = EmployeeCreator::approversFor($user)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:5120',
            'approver_id' => [$needsApprover ? 'required' : 'nullable', 'integer', function ($attr, $value, $fail) use ($allowedApprovers) {
                if ($value !== null && ! in_array((int) $value, $allowedApprovers, true)) {
                    $fail('Choose who to assign these employees to from the list.');
                }
            }],
        ], ['approver_id.required' => 'Choose who to assign these employees to.']);

        $stored = $request->file('file')->storeAs('employee-imports', Str::uuid() . '.xlsx', 'local');
        $import = EmployeeImport::create([
            'user_id' => $user->id,
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
            return redirect()->route('employees.import')->withErrors(['file' => $read['error']]);
        }
        $import->update([
            'total_rows' => $checked['summary']['total'],
            'valid_rows' => $checked['summary']['valid'],
            'error_rows' => $checked['summary']['errors'],
        ]);

        return view('employees.import', $this->pageData() + [
            'import' => $import,
            'results' => $checked['results'],
            'summary' => $checked['summary'],
        ]);
    }

    public function confirm(EmployeeImport $import)
    {
        $this->authorizeImport($import);
        abort_if($import->status !== 'previewed', 409, 'This upload has already been imported.');

        $user = Auth::user();
        [$read, $checked] = $this->check($import);
        if ($read['error']) {
            return redirect()->route('employees.import')->withErrors(['file' => $read['error']]);
        }

        $valid = array_filter($checked['results'], fn ($r) => ! $r['errors']);
        if (! $valid) {
            return redirect()->route('employees.import.show', $import)->withErrors(['file' => 'No rows are ready to import. Fix the errors and upload again.']);
        }

        // All valid rows in one transaction: either they are all created or none are.
        $ids = DB::transaction(function () use ($valid, $user, $import) {
            $ids = [];
            foreach ($valid as $row) {
                // spreadsheet row => new employee id (lets the error report leave these rows out later)
                $ids[(string) $row['row']] = EmployeeCreator::create($row['employee'], $row['pay'], $user, $import->approver_id)->id;
            }
            return $ids;
        });

        $import->update([
            'status' => 'completed',
            'created_count' => count($ids),
            'skipped_count' => count($checked['results']) - count($ids),
            'created_ids' => $ids,
            'completed_at' => now(),
        ]);

        $message = count($ids) . ' employees created from ' . $import->original_name . '.';
        if ($import->skipped_count) {
            $message .= ' ' . $import->skipped_count . ' rows with errors were skipped - download the error report from Bulk upload to fix them.';
        }

        return redirect()->route('employees.import')->with('success', $message)->with('completed_import', $import->id);
    }

    public function errors(EmployeeImport $import)
    {
        $this->authorizeImport($import);
        [$read, $checked] = $this->check($import);
        abort_if((bool) $read['error'], 422, $read['error'] ?? '');

        // After a completed import, the created rows now "already exist" - report only the skipped rows.
        $created = array_map('intval', array_keys($import->created_ids ?? []));
        $results = array_filter($checked['results'], fn ($r) => ! in_array((int) $r['row'], $created, true));
        return (new ErrorReportExport($read['rows'], $results))
            ->download('Upload_errors_' . pathinfo($import->original_name, PATHINFO_FILENAME) . '.xlsx');
    }

    private function check(EmployeeImport $import): array
    {
        // Read with Laravel Excel straight from the "local" disk (App\Imports\EmployeeUploadImport).
        $read = EmployeeBulkImport::read($import->stored_path, 'local');
        $checked = $read['error'] ? ['results' => [], 'summary' => []] : EmployeeBulkImport::validate($read['rows'], Auth::user());

        return [$read, $checked];
    }

    private function authorizeImport(EmployeeImport $import): void
    {
        abort_unless((int) $import->user_id === (int) Auth::id() && ($import->mode ?? 'create') === 'create', 404);
    }
}
