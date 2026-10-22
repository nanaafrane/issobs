<?php

namespace App\Http\Controllers;

use App\Models\AccountPayment;
use App\Models\Bank;
use App\Models\BankTransaction;
use App\Services\Banking\AccountBook;
use App\Services\Banking\AccountReports;
use App\Support\SubmissionGuard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Bank & cash accounts: overview, one account's statement, setting accounts up.
 * Money moving out (transfers, payments) is in AccountMovementController.
 */
class BankController extends Controller
{
    /** Who sees balances and statements, and records money movements. */
    public const VIEW_ROLES = ['Invoice', 'Finance Manager', 'Director'];

    /** Who corrects balances, closes accounts, cancels / reverses. */
    public const CORRECT_ROLES = ['Finance Manager', 'Director'];

    public function __construct(private AccountReports $reports)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $group = in_array($request->input('group'), ['day', 'week', 'month', 'year'], true) ? $request->input('group') : 'day';
        [$from, $to] = $this->period($request, now()->startOfMonth(), now());

        $banks = Bank::orderByRaw("CASE WHEN kind = 'cash' THEN 0 ELSE 1 END")->orderBy('name')->get();
        $live = $banks->filter(fn ($b) => $b->is_active !== false || abs((float) $b->total) > 0.004);
        $inRange = fn () => BankTransaction::whereBetween('entry_date', [$from->toDateString(), $to->toDateString()]);

        // Per account for the period (transfers between our accounts included here: they
        // do move this account's balance).
        $perBank = $inRange()
            ->selectRaw('bank_id, COALESCE(SUM(credit),0) AS in_total, COALESCE(SUM(debit),0) AS out_total')
            ->groupBy('bank_id')->get()->keyBy('bank_id');

        // Business money in / out: transfers between our own accounts are not income or spending.
        $flow = $this->reports->flow($from, $to);
        $inTotal = $flow['total_in'];
        $outTotal = $flow['total_out'];
        $net = $flow['net'];

        $cashBalance = (float) $banks->filter->isCash()->sum('total');
        $bankBalance = (float) $banks->reject->isCash()->sum('total');
        $totalBalance = $cashBalance + $bankBalance;

        $limit = 2000;
        $transactions = $inRange()->with('bank')->orderByDesc('entry_date')->orderByDesc('id')->limit($limit + 1)->get();
        $truncated = $transactions->count() > $limit;
        $transactions = $transactions->take($limit);

        $bucket = fn (Carbon $d) => match ($group) {
            'week' => $d->copy()->startOfWeek()->format('Y-m-d'),
            'month' => $d->format('Y-m'),
            'year' => $d->format('Y'),
            default => $d->format('Y-m-d'),
        };
        $label = fn (Carbon $d) => match ($group) {
            'week' => $d->copy()->startOfWeek()->format('d M Y'),
            'month' => $d->format('M Y'),
            'year' => $d->format('Y'),
            default => $d->format('d M Y'),
        };
        $series = $inRange()
            ->whereNotIn('channel', ['transfer_in', 'transfer_out', 'loan_in', 'loan_out'])
            ->selectRaw('entry_date AS d, COALESCE(SUM(credit),0) AS i, COALESCE(SUM(debit),0) AS o, COUNT(*) AS c')
            ->groupBy('entry_date')->orderBy('entry_date')->get()
            ->groupBy(fn ($r) => $bucket(Carbon::parse($r->d)))
            ->map(function ($rows) use ($label) {
                $in = (float) $rows->sum('i');
                $out = (float) $rows->sum('o');

                return ['label' => $label(Carbon::parse($rows->first()->d)), 'count' => (int) $rows->sum('c'), 'in' => $in, 'out' => $out, 'net' => $in - $out];
            })->values()->all();

        $checks = $this->reports->checks($banks);

        // 30-day outlook from business money only.
        $last = $this->reports->flow(now()->subDays(29)->startOfDay(), now());
        $avgDailyNet = $last['net'] / 30;
        $projection = [
            'avg_daily_net' => $avgDailyNet,
            'balance' => $totalBalance + $avgDailyNet * 30,
            'runway_days' => $avgDailyNet < 0 && $totalBalance > 0 ? (int) floor($totalBalance / abs($avgDailyNet)) : null,
        ];

        $uncleared = AccountPayment::with('bank')->where('status', 'issued')->orderBy('payment_date')->get();
        $awaiting = $this->reports->awaitingPayment();
        $owedRows = \App\Models\Creditor::where('balance', '>', 0);
        $creditorsOwed = ['amount' => round((float) $owedRows->sum('balance'), 2), 'count' => $owedRows->count()];

        return view('banks.index', compact(
            'banks', 'live', 'perBank', 'from', 'to', 'group', 'inTotal', 'outTotal', 'net', 'flow',
            'totalBalance', 'cashBalance', 'bankBalance', 'transactions', 'truncated', 'series', 'checks', 'projection',
            'uncleared', 'awaiting', 'creditorsOwed'
        ));
    }

    /** One account: statement for a period, uncleared cheques, details, corrections. */
    public function show(Request $request, Bank $bank)
    {
        abort_unless(Auth::user()?->hasRole(self::VIEW_ROLES), 403);
        [$from, $to] = $this->period($request, now()->startOfMonth(), now());
        $st = $this->reports->statement($bank, $from, $to);
        $check = $this->reports->checks(collect([$bank]))[$bank->id] ?? null;
        $canCorrect = Auth::user()?->hasRole(self::CORRECT_ROLES) ?? false;
        $hasHistory = BankTransaction::where('bank_id', $bank->id)->exists();

        return view('banks.show', compact('bank', 'st', 'check', 'canCorrect', 'hasHistory'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()?->hasRole(self::VIEW_ROLES), 403);
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(Bank::KINDS))],
            'name' => ['required', 'string', 'max:120', 'unique:banks,name'],
            'acc_number' => ['required_if:kind,bank', 'nullable', 'string', 'max:60'],
            'branch' => ['required_if:kind,bank', 'nullable', 'string', 'max:120'],
            'custodian' => ['required_if:kind,cash', 'nullable', 'string', 'max:120'],
            'opening_balance' => ['nullable', 'numeric', $request->input('kind') === 'cash' ? 'min:0' : 'min:-999999999'],
            'opening_date' => ['nullable', 'date', 'before_or_equal:today'],
        ], [], ['acc_number' => 'account number', 'custodian' => 'who holds the cash']);

        $bank = Bank::create([
            'kind' => $data['kind'],
            'name' => $data['name'],
            'acc_number' => $data['acc_number'] ?? '',      // columns are NOT NULL; a cash account has none
            'branch' => $data['branch'] ?? '',
            'custodian' => $data['custodian'] ?? null,
            'opening_balance' => round((float) ($data['opening_balance'] ?? 0), 2),
            'total' => round((float) ($data['opening_balance'] ?? 0), 2),
            'opening_date' => $data['opening_date'] ?? now()->toDateString(),
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('banks.show', $bank)->with('success', ($bank->isCash() ? 'Cash account' : 'Bank account') . ' ' . $bank->name . ' added.');
    }

    /** Details only. The balance never changes here — use an adjustment. */
    public function update(Request $request, Bank $bank)
    {
        abort_unless(Auth::user()?->hasRole(self::VIEW_ROLES), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('banks', 'name')->ignore($bank->id)],
            'acc_number' => [$bank->isCash() ? 'nullable' : 'required', 'nullable', 'string', 'max:60'],
            'branch' => ['nullable', 'string', 'max:120'],
            'custodian' => ['nullable', 'string', 'max:120'],
        ]);
        $data['acc_number'] = $data['acc_number'] ?? '';
        $data['branch'] = $data['branch'] ?? '';
        $bank->fill($data)->save();

        return back()->with('success', 'Account details updated.');
    }

    /** Correct the balance with a reason (cash count, bank charge on the statement…). */
    public function adjust(Request $request, Bank $bank, AccountBook $book, SubmissionGuard $guard)
    {
        abort_unless(Auth::user()?->hasRole(self::CORRECT_ROLES), 403);
        $request->validate(['amount' => ['required', 'numeric'], 'reason' => ['required', 'string', 'max:300'], 'adjust_date' => ['nullable', 'date']]);

        return $guard->once($request, 'bank_adjust', function () use ($request, $bank, $book) {
            $book->adjust($bank, (float) $request->input('amount'), (string) $request->input('reason'), $request->input('adjust_date'));

            return redirect()->route('banks.show', $bank)->with('success', 'Adjustment posted.');
        });
    }

    /** Close (or reopen) an account. Closed accounts keep their history but take no new money. */
    public function toggle(Bank $bank)
    {
        abort_unless(Auth::user()?->hasRole(self::CORRECT_ROLES), 403);
        if ($bank->is_active !== false && abs((float) $bank->total) > 0.004) {
            return back()->with('error', 'Move the balance out first: only an account at GH₵ 0.00 can be closed.');
        }
        $bank->is_active = $bank->is_active === false;
        $bank->save();

        return back()->with('success', $bank->name . ($bank->is_active ? ' reopened.' : ' closed. Its history is kept.'));
    }

    /** Only an account that was never used can be deleted; anything else is closed instead. */
    public function destroy(Bank $bank)
    {
        abort_unless(Auth::user()?->hasRole(self::CORRECT_ROLES), 403);
        $used = BankTransaction::where('bank_id', $bank->id)->exists()
            || \App\Models\Salary::where('bank_id', $bank->id)->exists()
            || \App\Models\PaymentInfo::where('bank_id', $bank->id)->exists()
            || \App\Models\Receipt::where('cheque_to_bank_id', $bank->id)->orWhere('transfer_to_bank_id', $bank->id)->exists();
        if ($used || abs((float) $bank->total) > 0.004) {
            return back()->with('error', $bank->name . ' has history, so it cannot be deleted. Close it instead (balance must be 0.00).');
        }
        $bank->delete();

        return redirect()->route('banks.index')->with('success', $bank->name . ' deleted.');
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function period(Request $request, Carbon $defaultFrom, Carbon $defaultTo): array
    {
        try {
            $from = Carbon::parse($request->input('from', $defaultFrom))->startOfDay();
            $to = Carbon::parse($request->input('to', $defaultTo))->endOfDay();
        } catch (\Throwable) {
            [$from, $to] = [$defaultFrom->copy()->startOfDay(), $defaultTo->copy()->endOfDay()];
        }
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }
}
