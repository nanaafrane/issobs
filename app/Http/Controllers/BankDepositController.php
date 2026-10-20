<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBankDepositRequest;
use App\Models\Bank;
use App\Models\BankDeposit;
use App\Models\Field;
use App\Services\Banking\DepositBook;
use App\Support\SubmissionGuard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Bank Deposit: take waiting cash and MoMo to the bank, one slip at a time.
 * The bookkeeping is in App\Services\Banking\DepositBook.
 */
class BankDepositController extends Controller
{
    /** Who can make deposits. */
    public const DEPOSIT_ROLES = ['Invoice', 'Finance Manager', 'Director'];

    /** Who can reverse a deposit or close old collections as already banked. */
    public const CORRECT_ROLES = ['Finance Manager', 'Director'];

    public function __construct(private DepositBook $book)
    {
        $this->middleware('auth');
    }

    /** Deposits made (one row per slip), newest first, for a month. */
    public function index(Request $request)
    {
        abort_unless(Auth::user()?->hasRole(self::DEPOSIT_ROLES), 403);
        $month = $this->month($request->input('month'));
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $deposits = BankDeposit::with(['bank', 'user'])
            ->where(fn ($q) => $q->whereBetween('deposit_date', [$from->toDateString(), $to->toDateString()])
                ->orWhere(fn ($q) => $q->whereNull('deposit_date')->whereBetween('created_at', [$from, $to])))
            ->orderByDesc('deposit_date')->orderByDesc('id')
            ->get();

        $live = $deposits->whereNull('reversed_at');
        $summary = [
            'slips' => $live->count(),
            'banked' => round((float) $live->sum('total'), 2),
            'cash' => round((float) $live->sum(fn ($d) => (float) $d->cash_amount + (float) $d->cheque_amount), 2),
            'momo' => round((float) $live->sum('momo_amount'), 2),
            'charges' => round((float) $live->sum('momo_charge'), 2),
        ];
        $summary['charge_rate'] = $summary['momo'] > 0 ? round($summary['charges'] / $summary['momo'] * 100, 2) : null;

        $waiting = $this->book->waiting($this->allowedFieldIds());
        $waitingTotals = ['cash' => round($waiting->sum('cash_due'), 2), 'momo' => round($waiting->sum('momo_due'), 2), 'count' => $waiting->count()];

        return view('banks.deposit_list', compact('deposits', 'summary', 'month', 'waitingTotals'));
    }

    /** What is waiting to be deposited, and the slip form. */
    public function create(Request $request)
    {
        $allowed = $this->allowedFieldIds();
        $fieldId = $request->integer('field') ?: null;
        if ($fieldId && $allowed !== null && ! in_array($fieldId, $allowed, true)) {
            abort(403);
        }

        $waiting = $this->book->waiting($fieldId ? [$fieldId] : $allowed);
        $fields = $allowed === null ? Field::orderBy('name')->get() : Field::whereIn('id', $allowed)->orderBy('name')->get();
        $banks = Bank::banksOnly()->operating()->orderBy('name')->get();
        $canDeposit = Auth::user()?->hasRole(self::DEPOSIT_ROLES) ?? false;
        $canCorrect = Auth::user()?->hasRole(self::CORRECT_ROLES) ?? false;
        $chargeRate = is_numeric(config('banking.momo_charge_rate')) ? (float) config('banking.momo_charge_rate') : null;

        return view('banks.deposit_index', compact('waiting', 'fields', 'fieldId', 'banks', 'canDeposit', 'canCorrect', 'chargeRate'));
    }

    public function store(StoreBankDepositRequest $request, SubmissionGuard $guard)
    {
        return $guard->once($request, 'bank_deposit', function () use ($request) {
            $deposit = $this->book->deposit($request->validated());

            return redirect()->route('deposit.show', $deposit)
                ->with('success', 'Deposit saved: GH₵ ' . number_format($deposit->total, 2) . ' to ' . $deposit->bank?->name
                    . ((float) $deposit->momo_charge > 0 ? ' (MoMo withdrawal charge GH₵ ' . number_format($deposit->momo_charge, 2) . ' posted as a bank debit)' : '') . '.');
        });
    }

    /** One slip: what went on it, and its lines on the bank account. */
    public function show(BankDeposit $deposit)
    {
        abort_unless(Auth::user()?->hasRole(self::DEPOSIT_ROLES), 403);
        $deposit->load(['bank', 'user', 'reversedBy', 'bankLines']);
        $collections = $deposit->collections()
            ->leftJoin('receipts', 'receipts.id', '=', 'collections.receipt_id')
            ->leftJoin('clients', 'clients.id', '=', 'receipts.client_id')
            ->leftJoin('fields', 'fields.id', '=', 'collections.field_id')
            ->orderBy('collections.id')
            ->get(['collections.*', 'receipts.momo_transactin_id as momo_ref', \Illuminate\Support\Facades\DB::raw('COALESCE(clients.business_name, clients.name) as client_name'), 'fields.name as office']);
        $canCorrect = Auth::user()?->hasRole(self::CORRECT_ROLES) ?? false;

        return view('banks.deposit_show', compact('deposit', 'collections', 'canCorrect'));
    }

    public function reverse(Request $request, BankDeposit $deposit, SubmissionGuard $guard)
    {
        abort_unless(Auth::user()?->hasRole(self::CORRECT_ROLES), 403);

        return $guard->once($request, 'bank_deposit_reverse', function () use ($request, $deposit) {
            $this->book->reverse($deposit, (string) $request->input('reversal_reason'));

            return redirect()->route('deposit.show', $deposit)
                ->with('success', 'Deposit reversed. Its cash and MoMo are back on Bank Deposit, waiting to be deposited.');
        });
    }

    public function markBanked(Request $request, SubmissionGuard $guard)
    {
        abort_unless(Auth::user()?->hasRole(self::CORRECT_ROLES), 403);

        return $guard->once($request, 'bank_deposit_mark_banked', function () use ($request) {
            $n = $this->book->markAlreadyBanked((array) $request->input('collections', []), (string) $request->input('banked_reason'));

            return redirect()->route('deposit.create')->with('success', $n . ' ' . ($n === 1 ? 'collection' : 'collections') . ' marked as already banked. Nothing was posted to any bank.');
        });
    }

    /** Head office sees every office; everyone else only their own (Tema also sees Shai Hills). */
    private function allowedFieldIds(): ?array
    {
        $user = Auth::user();
        if ($user?->hasRole(['Finance Manager', 'Invoice', 'Director'])) {
            return null;
        }

        return $user?->field_id == 3 ? [3, 7] : array_values(array_filter([(int) $user?->field_id]));
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
