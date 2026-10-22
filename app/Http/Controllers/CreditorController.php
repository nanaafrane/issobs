<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Creditor;
use App\Models\CreditorEntry;
use App\Services\Banking\AccountReports;
use App\Services\Banking\CreditorBook;
use App\Support\SubmissionGuard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Creditors: money borrowed by the company (a director's loan, a shareholder, a lender)
 * and paid back. See App\Services\Banking\CreditorBook.
 */
class CreditorController extends Controller
{
    public function __construct(private CreditorBook $book)
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->allow(BankController::VIEW_ROLES);
        $creditors = Creditor::withCount('entries')->orderByDesc('balance')->orderBy('name')->get();
        $monthStart = now()->startOfMonth()->toDateString();
        $thisMonth = CreditorEntry::where('entry_date', '>=', $monthStart)
            ->selectRaw("creditor_id, SUM(CASE WHEN owed_change > 0 THEN owed_change ELSE 0 END) AS added, SUM(CASE WHEN owed_change < 0 THEN -owed_change ELSE 0 END) AS reduced")
            ->groupBy('creditor_id')->get()->keyBy('creditor_id');
        $last = CreditorEntry::selectRaw('creditor_id, MAX(entry_date) AS last')->groupBy('creditor_id')->pluck('last', 'creditor_id');

        return view('creditors.index', compact('creditors', 'thisMonth', 'last'));
    }

    public function store(Request $request)
    {
        $this->allow(BankController::VIEW_ROLES);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:creditors,name'],
            'kind' => ['required', Rule::in(array_keys(Creditor::KINDS))],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'notes' => ['nullable', 'string', 'max:500'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'opening_date' => ['nullable', 'date', 'before_or_equal:today'],
        ]);
        $c = $this->book->create($data);

        return redirect()->route('creditors.show', $c)->with('success', $c->name . ' added as a creditor.');
    }

    public function show(Request $request, Creditor $creditor, AccountReports $reports)
    {
        $this->allow(BankController::VIEW_ROLES);
        try {
            $from = Carbon::parse($request->input('from', $creditor->opening_date ?? now()->startOfYear()))->startOfDay();
            $to = Carbon::parse($request->input('to', now()))->endOfDay();
        } catch (\Throwable) {
            [$from, $to] = [now()->startOfYear(), now()->endOfDay()];
        }
        $st = $this->book->statement($creditor, $from, $to);
        $accounts = Bank::operating()->orderByRaw("CASE WHEN kind = 'cash' THEN 0 ELSE 1 END")->orderBy('name')->get();
        $awaiting = $reports->awaitingPayment();
        $canCorrect = Auth::user()?->hasRole(BankController::CORRECT_ROLES) ?? false;

        return view('creditors.show', compact('creditor', 'st', 'from', 'to', 'accounts', 'awaiting', 'canCorrect'));
    }

    public function update(Request $request, Creditor $creditor)
    {
        $this->allow(BankController::VIEW_ROLES);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('creditors', 'name')->ignore($creditor->id)],
            'kind' => ['required', Rule::in(array_keys(Creditor::KINDS))],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $creditor->fill($data)->save();

        return back()->with('success', 'Details saved.');
    }

    public function receive(Request $request, Creditor $creditor, SubmissionGuard $guard)
    {
        $this->allow(BankController::VIEW_ROLES);
        $data = $request->validate($this->moneyRules());

        return $guard->once($request, 'creditor_receive', function () use ($creditor, $data) {
            $e = $this->book->receive($creditor, $data);

            return redirect()->route('creditors.show', $creditor)->with('success', 'GH₵ ' . number_format($e->owed_change, 2) . ' received from ' . $creditor->name . ' into ' . $e->bank?->name . '. The company now owes GH₵ ' . number_format($creditor->fresh()->balance, 2) . '.');
        });
    }

    public function onBehalf(Request $request, Creditor $creditor, SubmissionGuard $guard)
    {
        $this->allow(BankController::VIEW_ROLES);
        $data = $request->validate([
            'entry_date' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'payee' => ['nullable', 'string', 'max:150'],
            'reference' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:300'],
            'expense_ids' => ['nullable', 'array'],
            'expense_ids.*' => ['integer'],
        ]);

        return $guard->once($request, 'creditor_on_behalf', function () use ($creditor, $data) {
            $e = $this->book->onBehalf($creditor, $data);

            return redirect()->route('creditors.show', $creditor)->with('success', 'Recorded: ' . $creditor->name . ' paid GH₵ ' . number_format($e->owed_change, 2) . ' for the company.');
        });
    }

    public function repay(Request $request, Creditor $creditor, SubmissionGuard $guard)
    {
        $this->allow(BankController::VIEW_ROLES);
        $data = $request->validate($this->moneyRules() + ['interest' => ['nullable', 'numeric', 'min:0']]);

        return $guard->once($request, 'creditor_repay', function () use ($creditor, $data) {
            $e = $this->book->repay($creditor, $data);

            return redirect()->route('creditors.show', $creditor)->with('success', 'GH₵ ' . number_format(-$e->owed_change, 2) . ' repaid to ' . $creditor->name
                . ($e->interest ? ' plus GH₵ ' . number_format($e->interest, 2) . ' interest' : '') . '. Still owed: GH₵ ' . number_format($creditor->fresh()->balance, 2) . '.');
        });
    }

    /** A link to one entry (from a ledger line or an expense) opens the creditor's statement. */
    public function entry(CreditorEntry $entry)
    {
        $this->allow(BankController::VIEW_ROLES);

        return redirect()->to(route('creditors.show', ['creditor' => $entry->creditor_id, 'from' => $entry->entry_date->copy()->startOfMonth()->toDateString()]) . '#entry-' . $entry->id);
    }

    public function reverse(Request $request, CreditorEntry $entry, SubmissionGuard $guard)
    {
        $this->allow(BankController::CORRECT_ROLES);

        return $guard->once($request, 'creditor_reverse', function () use ($request, $entry) {
            $this->book->reverse($entry, (string) $request->input('reversal_reason'));

            return redirect()->route('creditors.show', $entry->creditor_id)->with('success', 'Entry #' . $entry->id . ' reversed.');
        });
    }

    private function moneyRules(): array
    {
        return [
            'bank_id' => ['required', 'integer', 'exists:banks,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'entry_date' => ['required', 'date', 'before_or_equal:today'],
            'method' => ['nullable', Rule::in(['transfer', 'cheque', 'cash', 'momo'])],
            'reference' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function allow(array $roles): void
    {
        abort_unless(Auth::user()?->hasRole($roles), 403);
    }
}
