<?php

namespace App\Http\Controllers;

use App\Models\AccountPayment;
use App\Models\AccountTransfer;
use App\Models\Bank;
use App\Services\Banking\AccountBook;
use App\Services\Banking\AccountReports;
use App\Services\Banking\MovePlan;
use App\Support\SubmissionGuard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Money leaving an account: transfers between our own accounts (e.g. funding Main Cash
 * with cheques from several banks) and payments to others (suppliers, approved expenses…).
 */
class AccountMovementController extends Controller
{
    public function __construct(private AccountBook $book, private AccountReports $reports)
    {
        $this->middleware('auth');
    }

    /* ---------------- transfers ---------------- */

    public function transfers(Request $request)
    {
        $this->allow(BankController::VIEW_ROLES);
        $month = $this->month($request->input('month'));
        $transfers = AccountTransfer::with(['from', 'to', 'user'])
            ->whereBetween('transfer_date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->orderByDesc('transfer_date')->orderByDesc('id')->get();
        // One line per move (a move can have several sources and destinations).
        $moves = $transfers->groupBy('batch')->map(fn ($rows) => $this->summarise($rows))->values();

        return view('banks.transfers', compact('transfers', 'moves', 'month'));
    }

    public function createTransfer(Request $request)
    {
        $this->allow(BankController::VIEW_ROLES);
        $accounts = Bank::operating()->orderByRaw("CASE WHEN kind = 'cash' THEN 0 ELSE 1 END")->orderBy('name')->get();
        $toId = $request->integer('to') ?: null;
        $fromId = $request->integer('from') ?: null;
        $accountsJs = $accounts->map(fn (Bank $a) => [
            'id' => $a->id, 'name' => $a->name, 'cash' => $a->isCash(), 'balance' => round((float) $a->total, 2),
            'leave' => MovePlan::leaveOptions($a), 'arrive' => MovePlan::arriveOptions($a),
        ])->values();

        return view('banks.transfer_create', compact('accounts', 'accountsJs', 'toId', 'fromId'));
    }

    /** Live preview for the Move money screen: the plan, or the first thing wrong with it. Saves nothing. */
    public function previewTransfer(Request $request)
    {
        $this->allow(BankController::VIEW_ROLES);
        $input = $this->moveInput($request);
        $ids = collect($input['sources'])->pluck('bank_id')->merge(collect($input['destinations'])->pluck('bank_id'))->filter()->map(fn ($v) => (int) $v)->unique();
        $banks = Bank::whereIn('id', $ids)->get()->keyBy('id');
        try {
            $plan = MovePlan::make($input, $banks);
        } catch (ValidationException $e) {
            $field = array_key_first($e->errors());

            return response()->json(['ok' => false, 'field' => $field, 'message' => $e->errors()[$field][0]]);
        }

        $after = $banks->map(fn ($b) => round((float) $b->total, 2))->all();
        $lines = collect($plan->lines)->sortBy(fn ($l) => $l['amount'] < 0 ? 0 : 1)->map(function ($l) use (&$after) {
            $after[$l['bank']->id] = round($after[$l['bank']->id] + $l['amount'], 2);

            return ['account' => $l['bank']->name, 'cash' => $l['bank']->isCash(), 'date' => $l['date']->format('d M Y'), 'narration' => $l['narration'],
                'in' => $l['amount'] > 0 ? $l['amount'] : null, 'out' => $l['amount'] < 0 ? -$l['amount'] : null, 'after' => $after[$l['bank']->id]];
        })->values();
        $short = $banks->filter(fn ($b) => $b->isCash() && $after[$b->id] < -0.004)->map(fn ($b) => $b->name . ' only holds GH₵ ' . number_format((float) $b->total, 2) . '.')->values();

        // A bank going below zero is allowed (overdraft, or the books are behind the statement) but worth a look.
        $warnings = $banks->filter(fn ($b) => ! $b->isCash() && $after[$b->id] < -0.004 && (float) $b->total >= -0.004)
            ->map(fn ($b) => $b->name . ' goes below zero (' . number_format($after[$b->id], 2) . ') — check the balance before saving.')->values();

        // The same cheque number from the same bank already entered (a move or a payment)?
        foreach (collect($plan->sources)->where('how', 'cheque') as $src) {
            $seen = AccountTransfer::where('from_bank_id', $src['bank']->id)->where('method', 'cheque')->where('reference', $src['reference'])->whereNull('reversed_at')->orderBy('id')->first();
            $paid = AccountPayment::where('bank_id', $src['bank']->id)->where('method', 'cheque')->where('reference', $src['reference'])->where('status', '!=', 'cancelled')->first();
            if ($seen || $paid) {
                $warnings->push('Cheque ' . $src['reference'] . ' from ' . $src['bank']->name . ' was already entered on '
                    . ($seen ? $seen->transfer_date->format('d M Y') . ' (move #' . $seen->id . ')' : $paid->payment_date->format('d M Y') . ' (payment #' . $paid->id . ')') . '. Is this the same cheque?');
            }
        }

        return response()->json([
            'ok' => $short->isEmpty(),
            'message' => $short->first(),
            'warnings' => $warnings,
            'total' => $plan->total,
            'lines' => $lines,
            'pairs' => collect($plan->pairs)->map(fn ($p) => [
                'from' => $plan->sources[$p['out']]['bank']->name, 'to' => $plan->destinations[$p['in']]['bank']->name, 'amount' => $p['amount'],
                'how' => MovePlan::LEAVE[$plan->sources[$p['out']]['how']] . ($plan->sources[$p['out']]['reference'] ? ' ' . $plan->sources[$p['out']]['reference'] : '')
                    . ' → ' . strtolower(MovePlan::ARRIVE[$p['in_method']] . ($p['in_method'] === 'cash' ? ($plan->destinations[$p['in']]['bank']->isCash() ? ' received' : ' deposited') : '')),
            ])->values(),
        ]);
    }

    public function storeTransfer(Request $request, SubmissionGuard $guard)
    {
        $this->allow(BankController::VIEW_ROLES);

        if ($request->has('sources')) {
            $request->validate([
                'date' => ['required', 'date', 'before_or_equal:today'],
                'notes' => ['nullable', 'string', 'max:500'],
                'sources' => ['required', 'array', 'min:1', 'max:30'],
                'destinations' => ['required', 'array', 'min:1', 'max:30'],
            ], [], ['date' => 'date']);
            $input = $this->moveInput($request);

            return $guard->once($request, 'account_transfer', function () use ($input) {
                $made = $this->book->move($input);
                $s = $this->summarise($made->load(['from', 'to']));

                return redirect()->route('money.transfers.show', $made->first())
                    ->with('success', 'GH₵ ' . number_format($s['total'], 2) . ' moved: ' . $s['from'] . ' → ' . $s['to'] . '.');
            });
        }

        // The older single-destination form (still accepted).
        $data = $request->validate([
            'to_bank_id' => ['required', 'integer', 'exists:banks,id'],
            'transfer_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.from_bank_id' => ['nullable', 'integer', 'exists:banks,id'],
            'lines.*.method' => ['nullable', Rule::in(array_keys(AccountTransfer::METHODS))],
            'lines.*.reference' => ['nullable', 'string', 'max:60'],
            'lines.*.amount' => ['nullable', 'numeric', 'min:0'],
        ], [], ['to_bank_id' => 'account receiving the money', 'lines.*.amount' => 'amount', 'lines.*.from_bank_id' => 'account it comes from']);
        $data['lines'] = array_values(array_filter($data['lines'], fn ($l) => ! empty($l['from_bank_id']) || (float) ($l['amount'] ?? 0) > 0));
        foreach ($data['lines'] as $i => $l) {
            if (empty($l['from_bank_id'])) {
                return back()->withInput()->withErrors(["lines.$i.from_bank_id" => 'Choose the account the money comes from (line ' . ($i + 1) . ').']);
            }
        }

        return $guard->once($request, 'account_transfer', function () use ($data) {
            $made = $this->book->transfer($data);

            return redirect()->route('money.transfers.show', $made->first())
                ->with('success', 'GH₵ ' . number_format($made->sum('amount'), 2) . ' moved to ' . $made->first()->to?->name . '.');
        });
    }

    public function showTransfer(AccountTransfer $transfer)
    {
        $this->allow(BankController::VIEW_ROLES);
        $batch = AccountTransfer::with(['from', 'to', 'user', 'reversedBy'])->where('batch', $transfer->batch)->orderBy('id')->get();
        $lines = \App\Models\BankTransaction::with('bank')->whereIn('transfer_id', $batch->pluck('id'))->orderBy('id')->get();
        $canCorrect = Auth::user()?->hasRole(BankController::CORRECT_ROLES) ?? false;
        $move = $this->summarise($batch);

        return view('banks.transfer_show', compact('transfer', 'batch', 'lines', 'canCorrect', 'move'));
    }

    /**
     * A move as entered: its source lines and destination lines (older transfers: one source per
     * cheque / transfer, one destination), plus totals and a one-line description.
     */
    private function summarise($rows): array
    {
        $rows = $rows->sortBy('id')->values();
        $outs = $rows->groupBy(fn ($t) => $t->leg_out ?? ('x' . $t->id))->map(fn ($g) => [
            'bank' => $g->first()->from, 'method' => $g->first()->method, 'reference' => $g->first()->reference,
            'date' => $g->first()->transfer_date, 'amount' => round((float) $g->sum('amount'), 2),
        ])->values();
        $ins = $rows->groupBy(fn ($t) => $t->leg_in ?? ('to' . $t->to_bank_id))->map(fn ($g) => [
            'bank' => $g->first()->to, 'method' => $g->first()->in_method, 'reference' => $g->first()->in_reference,
            'date' => $g->first()->in_date ?? $g->first()->transfer_date, 'amount' => round((float) $g->sum('amount'), 2),
            'from' => $g->map(fn ($t) => $t->routeLabel())->unique()->implode(', '),
        ])->values();
        $names = fn ($legs) => $legs->pluck('bank.name')->unique()->implode(', ');

        return [
            'first' => $rows->first(), 'outs' => $outs, 'ins' => $ins, 'total' => round((float) $rows->sum('amount'), 2),
            'from' => $names($outs), 'to' => $names($ins), 'reversed' => $rows->whereNull('reversed_at')->isEmpty(),
            'how' => $rows->map(fn ($t) => $t->routeLabel())->unique()->implode(' · '),
        ];
    }

    /** The move as posted by the screen (or a preview request), tidied. */
    private function moveInput(Request $request): array
    {
        $rows = fn ($key) => collect((array) $request->input($key, []))->filter(fn ($r) => is_array($r))->map(fn ($r) => [
            'bank_id' => $r['bank_id'] ?? null, 'how' => $r['how'] ?? null, 'amount' => $r['amount'] ?? null,
            'reference' => $r['reference'] ?? null, 'date' => $r['date'] ?? null, 'cheque' => $r['cheque'] ?? null,
        ])->values()->all();

        return ['date' => $request->input('date'), 'notes' => $request->input('notes'), 'sources' => $rows('sources'), 'destinations' => $rows('destinations')];
    }

    public function reverseTransfer(Request $request, AccountTransfer $transfer, SubmissionGuard $guard)
    {
        $this->allow(BankController::CORRECT_ROLES);

        return $guard->once($request, 'account_transfer_reverse', function () use ($request, $transfer) {
            $this->book->reverseTransfer($transfer, (string) $request->input('reversal_reason'));

            return redirect()->route('money.transfers.show', $transfer)->with('success', 'Reversed. Both accounts are back where they were.');
        });
    }

    /* ---------------- payments ---------------- */

    public function payments(Request $request)
    {
        $this->allow(BankController::VIEW_ROLES);
        $month = $this->month($request->input('month'));
        $bankId = $request->integer('bank') ?: null;
        $category = array_key_exists((string) $request->input('category'), AccountPayment::CATEGORIES) ? $request->input('category') : null;

        $status = array_key_exists((string) $request->input('status'), AccountPayment::STATUSES) ? $request->input('status') : null;
        $payments = AccountPayment::with(['bank', 'user'])->withCount('expenses')
            ->when($status !== 'issued', fn ($q) => $q->whereBetween('payment_date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()]))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($bankId, fn ($q) => $q->where('bank_id', $bankId))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderByDesc('payment_date')->orderByDesc('id')->get();
        $live = $payments->where('status', '!=', 'cancelled');
        $byCategory = $live->groupBy('category')->map(fn ($g, $k) => ['label' => AccountPayment::CATEGORIES[$k] ?? $k, 'amount' => round($g->sum('amount'), 2), 'count' => $g->count()])->sortByDesc('amount');
        $uncleared = AccountPayment::with('bank')->where('status', 'issued')->orderBy('payment_date')->get();
        $awaiting = $this->reports->awaitingPayment();
        $banks = Bank::orderBy('name')->get();

        return view('banks.payments', compact('payments', 'live', 'byCategory', 'uncleared', 'awaiting', 'month', 'bankId', 'category', 'status', 'banks'));
    }

    public function createPayment(Request $request)
    {
        $this->allow(BankController::VIEW_ROLES);
        $accounts = Bank::operating()->orderByRaw("CASE WHEN kind = 'cash' THEN 0 ELSE 1 END")->orderBy('name')->get();
        $awaiting = $this->reports->awaitingPayment();
        $ticked = array_map('intval', (array) $request->input('expenses', []));
        $mode = $ticked || $request->input('for') === 'expenses' ? 'expenses' : 'other';

        return view('banks.payment_create', compact('accounts', 'awaiting', 'ticked', 'mode'));
    }

    public function storePayment(Request $request, SubmissionGuard $guard)
    {
        $this->allow(BankController::VIEW_ROLES);
        // "Pay approved expenses": the amount is the ticked expenses' total; otherwise no expenses.
        if ($request->input('mode') === 'expenses') {
            if (! array_filter((array) $request->input('expense_ids', []))) {
                return back()->withInput()->withErrors(['expense_ids' => 'Tick the approved expenses being paid.']);
            }
            $request->merge(['category' => 'expenses', 'amount' => null]);
        } else {
            $request->merge(['expense_ids' => []]);
        }
        $data = $request->validate([
            'bank_id' => ['required', 'integer', 'exists:banks,id'],
            'payee' => ['required', 'string', 'max:150'],
            'method' => ['required', Rule::in(array_keys(AccountPayment::METHODS))],
            'reference' => ['nullable', 'string', 'max:60'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'category' => ['required', Rule::in(array_keys(AccountPayment::CATEGORIES))],
            'description' => ['nullable', 'string', 'max:500'],
            'expense_ids' => ['nullable', 'array'],
            'expense_ids.*' => ['integer'],
        ], [], ['bank_id' => 'account paid from', 'payee' => 'paid to']);

        return $guard->once($request, 'account_payment', function () use ($data) {
            $p = $this->book->pay($data);

            return redirect()->route('money.payments.show', $p)
                ->with('success', 'Payment of GH₵ ' . number_format($p->amount, 2) . ' to ' . $p->payee . ' recorded from ' . $p->bank?->name . '.');
        });
    }

    public function showPayment(AccountPayment $payment)
    {
        $this->allow(BankController::VIEW_ROLES);
        $payment->load(['bank', 'user', 'cancelledBy', 'bankLines', 'expenses.type', 'expenses.field']);
        $canCorrect = Auth::user()?->hasRole(BankController::CORRECT_ROLES) ?? false;

        return view('banks.payment_show', compact('payment', 'canCorrect'));
    }

    public function clearPayment(Request $request, AccountPayment $payment)
    {
        $this->allow(BankController::VIEW_ROLES);
        $this->book->clearCheque($payment, $request->input('cleared_date'));

        return back()->with('success', 'Cheque ' . $payment->reference . ' marked cleared.');
    }

    public function cancelPayment(Request $request, AccountPayment $payment, SubmissionGuard $guard)
    {
        $this->allow(BankController::CORRECT_ROLES);

        return $guard->once($request, 'account_payment_cancel', function () use ($request, $payment) {
            $this->book->cancelPayment($payment, (string) $request->input('cancel_reason'));

            return redirect()->route('money.payments.show', $payment)->with('success', 'Payment cancelled and the money put back on ' . $payment->bank?->name . '.');
        });
    }

    private function allow(array $roles): void
    {
        abort_unless(Auth::user()?->hasRole($roles), 403);
    }

    private function month(?string $value): Carbon
    {
        try {
            return $value ? Carbon::createFromFormat('Y-m', $value)->startOfMonth() : now()->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }
}
