<?php

namespace App\Http\Controllers;

use App\Exports\SalaryUpload\TemplateExport;
use App\Models\Field;
use App\Models\SalaryUploadBatch;
use App\Support\PayrollMonth;
use App\Support\SalaryCalculator;
use App\Support\SalaryUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Payroll > Salaries > Upload salaries:
 *   1) download a template for a month (and office), with only the columns chosen
 *   2) upload it - every row is checked and every change shown; nothing is saved
 *   3) tick the columns to apply and apply - rows with errors are left out and can be
 *      downloaded, fixed and uploaded again
 *   4) undo an applied upload (salaries not paid or changed since)
 */
class SalaryUploadController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            abort_unless(SalaryUpload::canUse(Auth::user()), 403, 'Only Accounts can upload salaries.');

            return $next($request);
        });
    }

    private function month(Request $request): \Carbon\Carbon
    {
        $m = $request->input('month');

        return PayrollMonth::parse(is_string($m) && preg_match('/^\d{4}-\d{2}/', $m) ? substr($m, 0, 7) : now()->subMonthNoOverflow()->format('Y-m'));
    }

    private function hasTemplateRows(\Carbon\Carbon $month, ?int $fieldId = null): bool
    {
        return (bool) \App\Models\Salary::whereBetween('salary_month', PayrollMonth::span($month))
            ->whereIn('payment_status', SalaryUpload::EDITABLE_STATUSES)
            ->when($fieldId, fn ($q) => $q->where('field_id', $fieldId))
            ->exists();
    }

    /**
     * The salary month an upload belongs to, so the page's links (back to that month's
     * salaries, another upload) stay on it: the month most of its salaries are in, else
     * the month saved with it, else the month in the address.
     */
    private function uploadMonth(SalaryUploadBatch $upload, \Illuminate\Support\Collection $salaries, Request $request): \Carbon\Carbon
    {
        $ym = $salaries->map(fn ($s) => $s->salary_month?->format('Y-m'))->filter()->countBy()->sortDesc()->keys()->first();
        if ($ym) {
            return PayrollMonth::parse($ym);
        }
        if ($upload->months) {
            try {
                return PayrollMonth::parse(\Carbon\Carbon::parse('1 ' . explode(',', $upload->months)[0])->format('Y-m'));
            } catch (\Throwable $e) {
            }
        }

        return $this->month($request);
    }

    private function pageData(Request $request): array
    {
        $user = Auth::user();
        $recent = SalaryUploadBatch::with(['user', 'appliedBy'])
            ->when(! SalaryUpload::canApply($user), fn ($q) => $q->where('user_id', $user->id))
            ->latest()->limit(15)->get();

        $month = $this->month($request);
        $fieldId = $request->integer('field_id') ?: null;

        return [
            'month' => $month,
            'fieldId' => $fieldId,
            'fields' => Field::orderBy('id')->get(['id', 'name']),
            'earnings' => SalaryCalculator::EARNINGS,
            'deductions' => SalaryCalculator::DEDUCTIONS,
            'canApply' => SalaryUpload::canApply($user),
            'recent' => $recent,
            'canDownloadTemplate' => $this->hasTemplateRows($month, $fieldId),
        ];
    }

    public function index(Request $request)
    {
        return view('salaries.upload', $this->pageData($request));
    }

    public function availability(Request $request)
    {
        $request->validate([
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])/'],
            'field_id' => ['nullable', 'integer'],
        ]);

        $month = $this->month($request);
        $fieldId = $request->integer('field_id') ?: null;
        $hasRows = $this->hasTemplateRows($month, $fieldId);

        return response()->json([
            'has_rows' => $hasRows,
            'message' => $hasRows
                ? ''
                : 'No unpaid salaries have been added for ' . $month->format('F Y') . ($fieldId ? ' for that office' : '') . '. Add employees on "Add to Salaries" first before downloading the template.',
        ]);
    }

    public function template(Request $request)
    {
        $request->validate([
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])/'],
            'field_id' => ['nullable', 'integer'],
            'columns' => ['required', 'array', 'min:1'],
        ], ['columns.required' => 'Tick at least one column to put in the file.']);

        $month = $this->month($request);
        $columns = SalaryUpload::columnsFromRequest($request->input('columns')) ?: array_keys(SalaryCalculator::editable());
        $fieldId = $request->integer('field_id') ?: null;
        $rows = SalaryUpload::templateRows($month, $fieldId, $columns);
        if (! $rows) {
            return back()->withInput()->with('error', 'No unpaid salaries in ' . $month->format('F Y') . ($fieldId ? ' for that office' : '') . '. Add employees on "Add to Salaries" first.');
        }
        $office = $fieldId ? Field::find($fieldId)?->name : null;
        $name = 'Salaries_' . $month->format('Y-m') . ($office ? '_' . Str::slug($office) : '') . '_' . now()->format('Ymd_His') . '.xlsx';

        return (new TemplateExport(Auth::user(), $columns, $rows, $month->format('F Y'), $office))->download($name);
    }

    /** Upload: keep the file, then show the check. Also used by the old salaries.upload form. */
    public function check(Request $request, string $field = 'file')
    {
        $request->validate([
            $field => ['required', 'file', 'max:10240', function ($attr, $file, $fail) {
                if (! in_array(strtolower($file->getClientOriginalExtension()), ['xlsx', 'xls', 'csv'], true)) {
                    $fail('Upload an Excel file (.xlsx) - the template from this page.');
                }
            }],
        ], [$field . '.required' => 'Choose the salary file to upload.']);

        $file = $request->file($field);
        $ext = strtolower($file->getClientOriginalExtension());
        $stored = $file->storeAs('salary-uploads', Str::uuid() . '.' . $ext, 'local');
        $upload = SalaryUploadBatch::create([
            'user_id' => Auth::id(),
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $stored,
        ]);

        $read = SalaryUpload::read($stored);
        if ($read['error']) {
            $upload->update(['status' => 'unreadable']);

            return redirect()->route('salary-upload.index', ['month' => $request->input('month')])->withErrors([$field => $read['error']])->withInput();
        }

        return redirect()->route('salary-upload.show', ['upload' => $upload, 'month' => $request->input('month')]);
    }

    public function show(Request $request, SalaryUploadBatch $upload)
    {
        $this->authorizeUpload($upload);
        if ($upload->status !== 'checked') {
            $names = \App\Models\employee::whereIn('id', array_column($upload->changes ?? [], 'employee_id') ?: [0])->pluck('name', 'id');

            $salaries = \App\Models\Salary::whereIn('id', array_keys($upload->changes ?? []) ?: [0])->get(['id', 'salary_month']);

            return view('salaries.upload', array_merge($this->pageData($request), [
                'upload' => $upload, 'done' => true, 'names' => $names,
                'month' => $this->uploadMonth($upload, $salaries, $request),
            ]));
        }

        $read = SalaryUpload::read($upload->stored_path);
        if ($read['error']) {
            return redirect()->route('salary-upload.index')->withErrors(['file' => $read['error']]);
        }
        $apply = SalaryUpload::columnsFromRequest($request->has('apply_sent') ? (array) $request->input('columns', []) : null);
        $check = SalaryUpload::check($read, $apply);
        $s = $check['summary'];
        $upload->update([
            'columns' => $read['columns'],
            'months' => implode(', ', $s['months']) ?: null,
            'total_rows' => $s['total'], 'ready_rows' => $s['ready'], 'error_rows' => $s['errors'], 'warning_rows' => $s['warnings'],
        ]);

        return view('salaries.upload', array_merge($this->pageData($request), [
            'upload' => $upload,
            'read' => $read,
            'results' => $check['results'],
            'summary' => $s,
            'labels' => SalaryCalculator::editable(),
            'month' => $this->uploadMonth($upload, collect($check['results'])->pluck('salary')->filter(), $request),
        ]));
    }

    public function apply(Request $request, SalaryUploadBatch $upload)
    {
        $this->authorizeUpload($upload);
        abort_unless(SalaryUpload::canApply(Auth::user()), 403, 'Only a Finance Manager or an Accounts Manager can apply a salary upload.');
        if ($upload->status !== 'checked') {
            return redirect()->route('salary-upload.show', $upload)->with('error', 'This file has already been ' . $upload->status . '.');
        }

        $read = SalaryUpload::read($upload->stored_path);
        if ($read['error']) {
            return redirect()->route('salary-upload.index')->withErrors(['file' => $read['error']]);
        }
        $apply = SalaryUpload::columnsFromRequest((array) $request->input('columns', [])) ?? [];
        if (! $apply) {
            return redirect()->route('salary-upload.show', $upload)->with('error', 'Tick at least one column to apply.');
        }

        $result = SalaryUpload::apply($upload, $read, $apply, Auth::user());
        if (! $result['updated']) {
            return redirect()->route('salary-upload.show', ['upload' => $upload, 'apply_sent' => 1, 'columns' => $apply])
                ->with('error', 'Nothing was saved: no row without errors changes anything.');
        }

        $s = $result['check']['summary'];
        $msg = $result['updated'] . ' salaries updated from ' . $upload->original_name . ' (' . implode(', ', array_map(fn ($k) => SalaryCalculator::editable()[$k], $apply)) . ').'
            . ' Net pay ' . number_format((float) $upload->net_before, 2) . ' -> ' . number_format((float) $upload->net_after, 2) . '.';
        if ($s['errors']) {
            $msg .= ' ' . $s['errors'] . ' rows with errors were not saved - download them from this page, fix and upload again.';
        }
        if ($result['skipped']) {
            $msg .= ' ' . $result['skipped'] . ' were approved or changed while you were checking, so were left as they are.';
        }

        return redirect()->route('salary-upload.show', $upload)->with('success', $msg);
    }

    public function errors(SalaryUploadBatch $upload)
    {
        $this->authorizeUpload($upload);
        $read = SalaryUpload::read($upload->stored_path);
        abort_if((bool) $read['error'], 422, $read['error'] ?? '');
        $check = SalaryUpload::check($read, $upload->status === 'applied' ? $upload->applied_columns : null);

        $labels = SalaryCalculator::editable();
        $rows = [];
        $errors = [];
        foreach ($check['results'] as $r) {
            if ($r['state'] !== 'error') {
                continue;
            }
            $s = $r['salary'];
            $raw = $read['rows'][$r['row']];
            $rows[] = [
                'salary_id' => $s?->id ?? $raw['salary_id'] ?? null,
                'employee_id' => $raw['employee_id'] ?? $s?->employee_id,
                'name' => $s?->employee?->name ?? ($raw['name'] ?? null),
                'office' => $s?->field?->name ?? ($raw['office'] ?? null),
                'client' => $s ? ($s->client?->business_name ?: $s->client?->name) : ($raw['client'] ?? null),
                'status' => $s?->payment_status ?? ($raw['status'] ?? null),
                'values' => array_intersect_key($raw, array_flip($read['columns'])) + array_fill_keys($read['columns'], null),
                'absent_days' => $raw['absent_days'] ?? null,
                'absent_attendance' => $raw['absent_attendance'] ?? null,
                'earnings_elsewhere' => $raw['earnings_elsewhere'] ?? null,
                'deductions_elsewhere' => $raw['deductions_elsewhere'] ?? null,
                'current_net' => $s?->net_salary === null ? null : round((float) $s->net_salary, 2),
            ];
            $cols = [];
            foreach ($r['errors'] as $e) {
                $key = $e['col'] && ($e['col'] === 'salary_id' || $e['col'] === 'employee_id' || isset($labels[$e['col']])) ? $e['col'] : 'status';
                $cols[$key] = isset($cols[$key]) ? $cols[$key] . "\n" . $e['msg'] : $e['msg'];
            }
            $errors[] = [
                'messages' => array_map(fn ($e) => $e['msg'], $r['errors']),
                'cols' => $cols,
            ];
        }
        abort_if(! $rows, 404, 'No rows with errors.');

        return (new TemplateExport(Auth::user(), $read['columns'], $rows, $upload->months ?: 'salary upload', null, $errors))
            ->download('Salary_upload_errors_' . pathinfo($upload->original_name, PATHINFO_FILENAME) . '.xlsx');
    }

    public function undo(SalaryUploadBatch $upload)
    {
        $this->authorizeUpload($upload);
        abort_unless(SalaryUpload::canApply(Auth::user()), 403, 'Only a Finance Manager or an Accounts Manager can undo a salary upload.');
        if ($upload->status !== 'applied') {
            return redirect()->route('salary-upload.show', $upload)->with('error', 'Only an applied upload can be undone.');
        }
        $result = SalaryUpload::undo($upload, Auth::user());
        $msg = $result['restored'] . ' salaries put back to what they were before ' . $upload->original_name . '.';
        $response = redirect()->route('salary-upload.show', $upload)->with('success', $msg);
        if ($result['kept']) {
            $response->with('warning', count($result['kept']) . ' left as they are now: ' . implode('; ', $result['kept']) . '.');
        }

        return $response;
    }

    private function authorizeUpload(SalaryUploadBatch $upload): void
    {
        abort_unless((int) $upload->user_id === (int) Auth::id() || SalaryUpload::canApply(Auth::user()), 404);
    }
}
