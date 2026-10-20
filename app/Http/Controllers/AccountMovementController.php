<?php

namespace App\Http\Controllers;

use App\Models\AccountPayment;
use App\Models\AccountTransfer;
use App\Models\Bank;
use App\Services\Banking\AccountBook;
use App\Services\Banking\AccountReports;
use App\Support\SubmissionGuard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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

        return view('banks.transfers', compact('transfers', 'month'));
    }

    public function createTransfer(Request $request)
    {
        $this->allow(BankController::VIEW_ROLES);
        $accounts = Bank::operating()->orderByRaw("CASE WHEN kind = 'cash' THEN 0 ELSE 1 END")->orderBy('name')->get();
        $toId = $request->integer('to') ?: $accounts->firstWhere('kind', 'cash')?->id;
        $pairs = [];
        foreach ($accounts as $a) {
            foreach ($accounts as $b) {
                if ($a->id !== $b->id) {
                    $pairs[$a->id . '-' . $b->id] = AccountBook::methodsBetween($a, $b);
                }
            }
        }

        return view('banks.transfer_create', compact('accounts', 'toId', 'pairs'));
    }

    public function storeTransfer(Request $request, SubmissionGuard $guard)
    {
        $this->allow(BankController::VIEW_ROLES);
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

        return view('banks.transfer_show', compact('transfer', 'batch', 'lines', 'canCorrect'));
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
