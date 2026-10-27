<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FleetScope;
use App\Models\Bank;
use App\Models\Field;
use App\Models\FuelEntry;
use App\Models\FuelPayout;
use App\Services\Fleet\FuelPayouts;
use App\Support\SubmissionGuard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** Fuel pay-outs: approved fuel for an office and period paid in one go (one expense, one payment). */
class FuelPayoutController extends Controller
{
    use FleetScope;

    public function __construct(private FuelPayouts $payouts)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $allowed = $this->allowedFieldIds();
        $owed = $this->payouts->owed($allowed);
        $owedByOffice = $owed->groupBy(fn ($f) => $f->field_id . '|' . $f->station)->map(fn ($g) => [
            'field' => $g->first()->field,
            'station' => $g->first()->station,
            'count' => $g->count(),
            'litres' => round((float) $g->sum('litres'), 2),
            'amount' => round((float) $g->sum('amount'), 2),
            'from' => $g->min('entry_date'),
            'to' => $g->max('entry_date'),
        ])->sortByDesc('amount')->values();

        $payouts = FuelPayout::with(['field', 'bank', 'user'])->withCount('entries')
            ->when($allowed !== null, fn ($q) => $q->whereIn('field_id', $allowed ?: [0]))
            ->orderByDesc('id')->limit(300)->get();
        $canFund = $this->isFinance();

        return view('fleet.fuel_payouts', compact('owed', 'owedByOffice', 'payouts', 'canFund'));
    }

    public function create(Request $request)
    {
        abort_unless($this->isFinance(), 403);
        $fields = Field::orderBy('name')->get();
        $fieldId = $request->integer('field') ?: null;
        $station = trim((string) $request->input('station')) ?: null;
        $from = $this->date($request->input('from')) ?? now()->startOfMonth();
        $to = $this->date($request->input('to')) ?? now()->startOfDay();
        $entries = $fieldId ? $this->payouts->owed([$fieldId], $from, $to, $station) : collect();
        $accounts = Bank::operating()->orderByRaw("CASE WHEN kind = 'cash' THEN 0 ELSE 1 END")->orderBy('name')->get();
        $stations = FuelEntry::owed()->whereNotNull('station')->distinct()->orderBy('station')->pluck('station');

        return view('fleet.fuel_payout_create', compact('fields', 'fieldId', 'station', 'stations', 'from', 'to', 'entries', 'accounts'));
    }

    public function store(Request $request, SubmissionGuard $guard)
    {
        abort_unless($this->isFinance(), 403);
        $data = $request->validate([
            'field_id' => ['required', 'integer', 'exists:fields,id'],
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'before_or_equal:today'],
            'station' => ['nullable', 'string', 'max:100'],
            'fuel_ids' => ['required', 'array', 'min:1'],
            'fuel_ids.*' => ['integer'],
            'bank_id' => ['required', 'integer', 'exists:banks,id'],
            'payee' => ['required', 'string', 'max:150'],
            'pay_method' => ['nullable', Rule::in(['cheque', 'transfer', 'momo'])],
            'reference' => ['nullable', 'string', 'max:60'],
            'payment_date' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], ['bank_id' => 'account the money comes from', 'fuel_ids' => 'fuel entries']);

        return $guard->once($request, 'fuel_payout', function () use ($data) {
            $p = $this->payouts->create($data);

            return redirect()->route('fleet.fuel.payouts.show', $p)->with('success', $p->label() . ': GH₵ ' . number_format($p->total, 2) . ' paid to ' . $p->payee . ' from ' . $p->bank?->name . '.');
        });
    }

    public function show(FuelPayout $payout)
    {
        $this->mustSeeField($payout->field_id);
        $payout->load(['field', 'bank', 'payment', 'expense', 'user']);
        $entries = $payout->entries()->with(['vehicle', 'driver'])->orderBy('entry_date')->orderBy('id')->get();
        $canCorrect = Auth::user()?->hasRole(BankController::CORRECT_ROLES) ?? false;

        return view('fleet.fuel_payout_show', compact('payout', 'entries', 'canCorrect'));
    }

    public function cancel(Request $request, FuelPayout $payout, SubmissionGuard $guard)
    {
        abort_unless(Auth::user()?->hasRole(BankController::CORRECT_ROLES), 403);

        return $guard->once($request, 'fuel_payout_cancel', function () use ($request, $payout) {
            $this->payouts->cancel($payout, (string) $request->input('cancel_reason'));

            return redirect()->route('fleet.fuel.payouts.show', $payout)->with('success', 'Pay-out cancelled: the money is back on the account and the fuel is owed again.');
        });
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
