<?php

namespace App\Http\Controllers;

use App\Models\Field;
use App\Support\Attendance\AbsentDeduction;
use App\Support\PayrollMonth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Payroll > Absent deductions (Finance Manager).
 *
 * Shows, for a salary month, each guard's days marked Absent in attendance and the
 * deduction they come to, next to what salaries.absent holds now, and applies the
 * ones Finance ticks. Also where the daily rate rule is set.
 */
class AbsentDeductionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            abort_unless(Auth::user()?->hasRole(['Finance Manager']), 403, 'Only a Finance Manager can manage absent deductions.');

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $month = PayrollMonth::parse($request->query('month') ?: now()->subMonthNoOverflow()->format('Y-m'));
        $fieldId = $request->integer('field_id') ?: null;
        $lines = AbsentDeduction::preview($month, $fieldId);
        $settings = AbsentDeduction::settings();

        $history = DB::table('absent_deduction_changes')
            ->leftJoin('employees', 'employees.id', '=', 'absent_deduction_changes.employee_id')
            ->leftJoin('users', 'users.id', '=', 'absent_deduction_changes.user_id')
            ->whereBetween('absent_deduction_changes.salary_month', PayrollMonth::span($month))
            ->orderByDesc('absent_deduction_changes.id')->limit(30)
            ->get(['absent_deduction_changes.*', 'employees.name as employee_name', 'users.name as user_name']);

        return view('salaries.absent_deductions', [
            'month' => $month,
            'fieldId' => $fieldId,
            'fields' => Field::orderBy('name')->pluck('name', 'id'),
            'lines' => $lines,
            'settings' => $settings,
            'settingsBy' => $settings['user_id'] ? DB::table('users')->where('id', $settings['user_id'])->value('name') : null,
            'history' => $history,
        ]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'basis' => ['required', Rule::in(array_keys(AbsentDeduction::BASES))],
            'divisor' => ['required', Rule::in(array_keys(AbsentDeduction::DIVISORS))],
            'auto_on_create' => ['nullable', 'boolean'],
        ]);
        AbsentDeduction::saveSettings($data['basis'], $data['divisor'], $request->boolean('auto_on_create'), Auth::id());

        return back()->with('success', 'Daily rate rule saved: ' . AbsentDeduction::BASES[$data['basis']] . ' ÷ ' . AbsentDeduction::DIVISORS[$data['divisor']]
            . '. Salaries already set are not changed until you apply them below.');
    }

    public function apply(Request $request)
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'salaries' => ['required', 'array'],
            'salaries.*' => ['integer'],
        ], ['salaries.required' => 'Tick at least one salary to update.']);

        $r = AbsentDeduction::apply($data['month'], $data['salaries'], Auth::id());
        $msg = "Absent deduction updated on {$r['updated']} salary(ies).";
        if ($r['skipped']) {
            $msg .= " {$r['skipped']} skipped (already paid, unchanged, or no longer in the list).";
        }

        return back()->with('success', $msg);
    }
}
