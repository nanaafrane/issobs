<?php

namespace App\Http\Controllers;

use App\Exports\SalaryPaymentsExport;
use App\Support\PaymentRuns;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Salary payments of a payroll month: the numbered payments (runs) the Finance Manager
 * opens and closes, and the report in the layout of the old "salary payment report" sheet.
 */
class PaymentRunController extends Controller
{
    /** Same roles as the salaries report. */
    private const VIEW_ROLES = ['Invoice', 'Manager', 'Internal Auditor', 'Officer', 'Finance Manager'];

    public function __construct()
    {
        $this->middleware('auth');
    }

    private function authorizeView(): void
    {
        abort_unless(Auth::user()?->hasRole(self::VIEW_ROLES), 403);
    }

    private function authorizeManage(): void
    {
        abort_unless(Auth::user()?->hasRole(['Finance Manager']), 403, 'Only a Finance Manager can manage payments.');
    }

    private function monthFrom(Request $request)
    {
        $month = $request->input('month');
        if (! $month || ! preg_match('/^\d{4}-\d{2}(-\d{2})?$/', (string) $month)) {
            $month = DB::table('salaries')->max('salary_month') ?: now()->toDateString();
        }

        return PaymentRuns::month(strlen($month) === 7 ? $month . '-01' : $month);
    }

    public function index(Request $request)
    {
        $this->authorizeView();
        $month = $this->monthFrom($request);
        $report = PaymentRuns::report($month);
        $months = DB::table('salaries')->selectRaw('DISTINCT DATE_FORMAT(salary_month, "%Y-%m") as ym')
            ->whereNotNull('salary_month')->where('salary_month', '>', '2000-01-01')->orderByDesc('ym')->limit(36)->pluck('ym');
        $canManage = Auth::user()->hasRole(['Finance Manager']);

        return view('salaries.payments', compact('report', 'month', 'months', 'canManage'));
    }

    public function start(Request $request)
    {
        $this->authorizeManage();
        $data = $request->validate([
            'month' => ['required', 'date'],
            'run_date' => ['nullable', 'date'],
            'funds_released' => ['nullable', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);
        $previous = PaymentRuns::openRun($data['month']);
        $run = PaymentRuns::start($data['month'], $data['run_date'] ?? null, isset($data['funds_released']) ? (float) $data['funds_released'] : null,
            $data['reference'] ?? null, $data['notes'] ?? null);

        return redirect()->route('salary-payments.index', ['month' => PaymentRuns::month($data['month'])->format('Y-m')])
            ->with('success', ($previous ? 'Closed Payment ' . $previous->number . '. ' : '') . 'Started ' . PaymentRuns::label($run)
                . '. Every approval from now on is recorded in it.');
    }

    public function close(int $run)
    {
        $this->authorizeManage();
        $row = DB::table('payment_runs')->find($run) ?? abort(404);
        PaymentRuns::close($row->id);

        return back()->with('success', 'Closed ' . PaymentRuns::label($row) . '. The next approval opens Payment ' . ($row->number + 1)
            . ' automatically, or start it yourself to set its date and funds released.');
    }

    public function update(Request $request, int $run)
    {
        $this->authorizeManage();
        $row = DB::table('payment_runs')->find($run) ?? abort(404);
        $data = $request->validate([
            'run_date' => ['required', 'date'],
            'funds_released' => ['nullable', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);
        // Date, funds and reference are details of the payment; what was paid in it never changes here.
        DB::table('payment_runs')->where('id', $row->id)->update([
            'run_date' => $data['run_date'],
            'funds_released' => $data['funds_released'] ?? null,
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Updated Payment ' . $row->number . '.');
    }

    public function payDifferences(Request $request)
    {
        $this->authorizeManage();
        $data = $request->validate([
            'month' => ['required', 'date'],
            'salary' => ['nullable', 'array'],
            'salary.*' => ['integer'],
        ]);
        $ids = isset($data['salary']) ? array_map('intval', $data['salary']) : null;
        if ($ids === []) {
            return back()->with('error', 'No salaries selected.');
        }
        $result = PaymentRuns::payDifferences($data['month'], $ids);
        if (! $result['count']) {
            return back()->with('error', 'Nothing to pay: no approved salary is owed a difference.');
        }

        return back()->with('success', 'Paid the difference for ' . $result['count'] . ' salar' . ($result['count'] === 1 ? 'y' : 'ies')
            . ' (GH₵ ' . number_format($result['amount'], 2) . ') in ' . PaymentRuns::label($result['run']) . '.');
    }

    /** Who makes up one cell of the report. */
    public function cell(Request $request)
    {
        $this->authorizeView();
        $month = $this->monthFrom($request);
        $row = in_array($request->input('row'), [PaymentRuns::ROW_MAIN, PaymentRuns::ROW_HOLD, PaymentRuns::ROW_TOPUP], true) ? $request->input('row') : PaymentRuns::ROW_MAIN;
        $run = $request->input('run') === 'before' ? 'before' : (string) (int) $request->input('run');
        $field = $request->filled('field') ? (int) $request->input('field') : null;

        $people = PaymentRuns::cell($month, $run, $field, $row);

        return response()->json([
            'count' => $people->count(),
            'total' => round($people->sum('amount'), 2),
            'people' => $people->map(fn ($p) => [
                'employee_id' => $p->employee_id, 'name' => $p->name ?? 'Unknown', 'kind' => $p->kind, 'amount' => round((float) $p->amount, 2),
            ])->values(),
        ]);
    }

    public function export(Request $request)
    {
        $this->authorizeView();
        $month = $this->monthFrom($request);

        return Excel::download(new SalaryPaymentsExport(PaymentRuns::report($month)), strtoupper($month->format('F_Y')) . '_SALARY_PAYMENT_REPORT.xlsx');
    }
}
