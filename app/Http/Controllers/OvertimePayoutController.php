<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Field;
use App\Models\Overtime;
use App\Models\OvertimePayout;
use App\Services\Overtime\OvertimePayouts;
use App\Support\SubmissionGuard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Overtime pay-outs: finance pays an office's approved overtime for a period in one go;
 * the office records each guard as paid. See App\Services\Overtime\OvertimePayouts.
 */
class OvertimePayoutController extends Controller
{
    public function __construct(private OvertimePayouts $payouts)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $allowed = $this->allowedFieldIds();
        $owed = $this->payouts->owed($allowed);
        $owedByOffice = $owed->groupBy('field_id')->map(fn ($g) => [
            'field' => $g->first()->field,
            'count' => $g->count(),
            'amount' => round((float) $g->sum('amount'), 2),
            'from' => $g->min('entry_date'),
            'to' => $g->max('entry_date'),
        ])->sortByDesc('amount')->values();

        $status = in_array($request->input('status'), ['open', 'closed', 'cancelled'], true) ? $request->input('status') : null;
        $payouts = OvertimePayout::with(['field', 'bank', 'user'])
            ->withCount(['entries', 'entries as paid_count' => fn ($q) => $q->whereNotNull('disbursed_at')])
            ->withSum(['entries as unpaid_amount' => fn ($q) => $q->whereNull('disbursed_at')], 'amount')
            ->when($allowed !== null, fn ($q) => $q->whereIn('field_id', $allowed ?: [0]))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')->limit(300)->get();

        $held = $payouts->where('status', 'open')->where('method', 'cash');
        $canFund = $this->canFund();

        return view('overtime.payouts', compact('owed', 'owedByOffice', 'payouts', 'held', 'status', 'canFund'));
    }

    public function create(Request $request)
    {
        abort_unless($this->canFund(), 403);
        $fields = Field::orderBy('name')->get();
        $fieldId = $request->integer('field') ?: null;
        $from = $this->date($request->input('from')) ?? now()->startOfWeek();
        $to = $this->date($request->input('to')) ?? now();
        $entries = $fieldId ? $this->payouts->owed([$fieldId], $from, $to) : collect();
        $accounts = Bank::operating()->orderByRaw("CASE WHEN kind = 'cash' THEN 0 ELSE 1 END")->orderBy('name')->get();

        return view('overtime.payout_create', compact('fields', 'fieldId', 'from', 'to', 'entries', 'accounts'));
    }

    public function store(Request $request, SubmissionGuard $guard)
    {
        abort_unless($this->canFund(), 403);
        $data = $request->validate([
            'field_id' => ['required', 'integer', 'exists:fields,id'],
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'before_or_equal:today'],
            'method' => ['required', Rule::in(array_keys(OvertimePayout::METHODS))],
            'overtime_ids' => ['required', 'array', 'min:1'],
            'overtime_ids.*' => ['integer'],
            'bank_id' => ['required_unless:method,payroll', 'nullable', 'integer', 'exists:banks,id'],
            'pay_method' => ['nullable', Rule::in(['cheque', 'transfer'])],
            'reference' => ['nullable', 'string', 'max:60'],
            'held_by' => ['required_if:method,cash', 'nullable', 'string', 'max:120'],
            'payment_date' => ['nullable', 'date', 'before_or_equal:today'],
            'payroll_month' => ['required_if:method,payroll', 'nullable', 'date_format:Y-m'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], ['bank_id' => 'account the money comes from', 'held_by' => 'officer given the cash', 'overtime_ids' => 'overtime entries']);
        if (! empty($data['payroll_month'])) {
            $data['payroll_month'] = $data['payroll_month'] . '-01';
        }

        return $guard->once($request, 'overtime_payout', function () use ($data) {
            $p = $this->payouts->create($data);

            return redirect()->route('overtime.payouts.show', $p)->with('success', $p->label() . ' created: GH₵ ' . number_format($p->total, 2)
                . ($p->method === 'payroll' ? ' to be paid with the ' . $p->payroll_month->format('F Y') . ' salaries.' : ' paid from ' . $p->bank?->name . '. Tick each guard as they are paid.'));
        });
    }

    public function show(OvertimePayout $payout)
    {
        $this->mustSee($payout);
        $payout->load(['field', 'bank', 'payment', 'expense', 'user']);
        $entries = $payout->entries()->with(['otEmployee', 'client', 'absentEmployee'])->orderBy('entry_date')->orderBy('shift')->get();
        $accounts = Bank::operating()->orderByRaw("CASE WHEN kind = 'cash' THEN 0 ELSE 1 END")->orderBy('name')->get();
        $check = $payout->method === 'payroll' && $payout->status !== 'cancelled'
            ? $this->payouts->payrollCheck($payout->payroll_month, [$payout->field_id]) : collect();
        $canFund = $this->canFund();
        $canCorrect = Auth::user()?->hasRole(BankController::CORRECT_ROLES) ?? false;

        return view('overtime.payout_show', compact('payout', 'entries', 'accounts', 'check', 'canFund', 'canCorrect'));
    }

    public function disburse(Request $request, OvertimePayout $payout, SubmissionGuard $guard)
    {
        $this->mustSee($payout);
        $request->validate(['overtime_ids' => ['required', 'array'], 'disbursed_at' => ['nullable', 'date'], 'refs' => ['nullable', 'array']]);

        return $guard->once($request, 'overtime_disburse', function () use ($request, $payout) {
            $n = $this->payouts->disburse($payout, (array) $request->input('overtime_ids'), $request->input('disbursed_at'), (array) $request->input('refs', []));

            return redirect()->route('overtime.payouts.show', $payout)->with('success', $n . ' ' . ($n === 1 ? 'guard' : 'guards') . ' marked as paid.');
        });
    }

    public function giveBack(Request $request, OvertimePayout $payout, SubmissionGuard $guard)
    {
        abort_unless($this->canFund(), 403);
        $request->validate(['overtime_ids' => ['required', 'array'], 'bank_id' => ['required', 'integer', 'exists:banks,id'], 'reason' => ['required', 'string', 'max:300']]);

        return $guard->once($request, 'overtime_give_back', function () use ($request, $payout) {
            $amount = $this->payouts->giveBack($payout, (array) $request->input('overtime_ids'), $request->integer('bank_id'), (string) $request->input('reason'));

            return redirect()->route('overtime.payouts.show', $payout)->with('success', 'GH₵ ' . number_format($amount, 2) . ' returned. Those entries are owed again and can go on the next pay-out.');
        });
    }

    public function cancel(Request $request, OvertimePayout $payout, SubmissionGuard $guard)
    {
        abort_unless(Auth::user()?->hasRole(BankController::CORRECT_ROLES), 403);

        return $guard->once($request, 'overtime_payout_cancel', function () use ($request, $payout) {
            $this->payouts->cancel($payout, (string) $request->input('cancel_reason'));

            return redirect()->route('overtime.payouts.show', $payout)->with('success', 'Pay-out cancelled. Its entries are owed again' . ($payout->payment_id ? ' and the money is back on the account.' : '.'));
        });
    }

    public function payrollCheck(Request $request)
    {
        abort_unless($this->canFund(), 403);
        $month = $this->date($request->input('month') ? $request->input('month') . '-01' : null) ?? now()->startOfMonth();
        $rows = $this->payouts->payrollCheck($month);

        return view('overtime.payroll_check', compact('rows', 'month'));
    }

    /** Head-office finance raises and funds pay-outs. */
    private function canFund(): bool
    {
        return Auth::user()?->hasRole(BankController::VIEW_ROLES) ?? false;
    }

    /** Head office sees every office; an office sees itself and its sub-offices. */
    private function allowedFieldIds(): ?array
    {
        $user = Auth::user();
        if ($user?->hasRole(BankController::VIEW_ROLES)) {
            return null;
        }

        return Field::managedIds($user?->field_id ? (int) $user->field_id : null);
    }

    private function mustSee(OvertimePayout $payout): void
    {
        $allowed = $this->allowedFieldIds();
        abort_if($allowed !== null && ! in_array((int) $payout->field_id, $allowed, true), 403);
    }

    private function date(?string $v): ?Carbon
    {
        try {
            return $v ? Carbon::parse($v)->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
