<?php

namespace App\Services\Fleet;

use App\Models\AccountPayment;
use App\Models\Bank;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\Field;
use App\Models\FuelEntry;
use App\Models\FuelPayout;
use App\Services\Banking\AccountBook;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Paying for fuel, the overtime way: approved fill-ups are "owed"; finance pays an office's
 * fuel for a period (optionally one station's bill) in one go. That makes ONE expense (type
 * Fuel, the office, the total) and ONE payment (category Fuel) from a bank or Main Cash, so
 * the statement shows one line and the cash book shows it under Fuel.
 *
 * Unlike overtime there is nothing to hand out afterwards (the fuel is already in the tank),
 * so a pay-out is paid at once; a mistake is cancelled (payment reversed, entries owed again).
 */
class FuelPayouts
{
    public function __construct(private AccountBook $book)
    {
    }

    /** Approved fuel not on any pay-out yet. */
    public function owed(?array $fieldIds = null, ?Carbon $from = null, ?Carbon $to = null, ?string $station = null): Collection
    {
        return FuelEntry::with(['vehicle', 'field', 'driver'])->owed()
            ->when($fieldIds !== null, fn ($q) => $q->whereIn('field_id', $fieldIds ?: [0]))
            ->when($from, fn ($q) => $q->whereDate('entry_date', '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->whereDate('entry_date', '<=', $to->toDateString()))
            ->when($station, fn ($q) => $q->where('station', $station))
            ->orderBy('entry_date')->orderBy('id')
            ->get();
    }

    /**
     * @param  array{field_id:int, period_from:string, period_to:string, fuel_ids:array<int>, bank_id:int,
     *               payee:string, pay_method?:?string, reference?:?string, payment_date?:?string,
     *               station?:?string, notes?:?string}  $input
     */
    public function create(array $input): FuelPayout
    {
        $from = Carbon::parse($input['period_from'])->startOfDay();
        $to = Carbon::parse($input['period_to'])->startOfDay();
        if ($from->gt($to)) {
            throw ValidationException::withMessages(['period_to' => 'The period ends before it starts.']);
        }
        $ids = array_values(array_unique(array_map('intval', array_filter((array) ($input['fuel_ids'] ?? [])))));
        if (! $ids) {
            throw ValidationException::withMessages(['fuel_ids' => 'Tick the fuel entries this pay-out covers.']);
        }
        $payee = trim((string) ($input['payee'] ?? ''));
        if ($payee === '') {
            throw ValidationException::withMessages(['payee' => 'Who is paid — the fuel station, or the person who bought the fuel?']);
        }
        $station = trim((string) ($input['station'] ?? '')) ?: null;

        return DB::transaction(function () use ($input, $from, $to, $ids, $payee, $station) {
            $field = Field::findOrFail((int) $input['field_id']);
            $entries = FuelEntry::whereIn('id', $ids)->lockForUpdate()->get();
            $bad = $entries->filter(fn (FuelEntry $f) => $f->status_3 !== 'approved' || $f->payout_id
                || (int) $f->field_id !== $field->id || $f->entry_date->lt($from) || $f->entry_date->gt($to)
                || ($station && $f->station !== $station))->pluck('id')
                ->merge(array_diff($ids, $entries->pluck('id')->all()));
            if ($bad->isNotEmpty()) {
                throw ValidationException::withMessages(['fuel_ids' => 'Fuel #' . $bad->implode(', #')
                    . ' is not approved, already paid, or not ' . $field->name . ' fuel between those dates. Refresh and try again.']);
            }
            $total = round((float) $entries->sum('amount'), 2);
            if ($total <= 0) {
                throw ValidationException::withMessages(['fuel_ids' => 'The ticked entries add up to GH₵ 0.00.']);
            }

            $bank = Bank::find((int) ($input['bank_id'] ?? 0));
            if (! $bank) {
                throw ValidationException::withMessages(['bank_id' => 'Choose the account the money comes from.']);
            }
            $payMethod = $bank->isCash() ? 'cash' : (string) ($input['pay_method'] ?? 'cheque');

            $payout = FuelPayout::create([
                'field_id' => $field->id,
                'period_from' => $from,
                'period_to' => $to,
                'station' => $station,
                'payee' => mb_substr($payee, 0, 150),
                'bank_id' => $bank->id,
                'total' => $total,
                'litres' => round((float) $entries->sum('litres'), 2),
                'notes' => ($n = trim((string) ($input['notes'] ?? ''))) === '' ? null : mb_substr($n, 0, 500),
                'user_id' => Auth::id(),
            ]);
            FuelEntry::whereIn('id', $ids)->update(['payout_id' => $payout->id]);

            $vehicles = $entries->load('vehicle')->pluck('vehicle.reg_no')->filter()->unique();
            $description = $payout->label() . ' · ' . count($ids) . ' fill-ups, ' . number_format($payout->litres, 2) . ' L'
                . ($vehicles->count() <= 4 ? ' · ' . $vehicles->implode(', ') : ' · ' . $vehicles->count() . ' vehicles');

            $expense = Expense::create([
                'description' => mb_substr($description, 0, 255),
                'amount' => $total,
                'expense_date' => $to->toDateString(),
                'field_id' => $field->id,
                'expense_type_id' => self::fuelType()->id,
                // Each fill-up went through the approval chain; the pay-out itself is raised by finance.
                'user_1' => Auth::id(), 'status_1' => 'approved', 'date_1' => now(),
                'user_2' => Auth::id(), 'status_2' => 'approved', 'date_2' => now(),
                'user_3' => Auth::id(), 'status_3' => 'approved',
            ]);

            $payment = $this->book->pay([
                'bank_id' => $bank->id,
                'payee' => $payout->payee,
                'method' => $payMethod,
                'reference' => $input['reference'] ?? null,
                'payment_date' => $input['payment_date'] ?? now()->toDateString(),
                'category' => 'fuel',
                'description' => $description,
                'expense_ids' => [$expense->id],
            ]);

            $payout->payment_id = $payment->id;
            $payout->expense_id = $expense->id;
            $payout->save();

            return $payout->fresh();
        });
    }

    /** Undo a pay-out entered in error: the payment is reversed and the fuel is owed again. */
    public function cancel(FuelPayout $payout, string $reason): FuelPayout
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages(['cancel_reason' => 'Give a reason (at least 5 characters).']);
        }

        return DB::transaction(function () use ($payout, $reason) {
            $payout = FuelPayout::whereKey($payout->id)->lockForUpdate()->firstOrFail();
            if ($payout->status === 'cancelled') {
                throw ValidationException::withMessages(['cancel_reason' => 'Already cancelled.']);
            }
            if ($payout->payment_id) {
                $payment = AccountPayment::findOrFail($payout->payment_id);
                if ($payment->status !== 'cancelled') {
                    $this->book->cancelPayment($payment, 'Fuel pay-out #' . $payout->id . ' cancelled: ' . $reason);
                }
            }
            if ($payout->expense_id) {
                Expense::whereKey($payout->expense_id)->delete();
            }
            FuelEntry::where('payout_id', $payout->id)->update(['payout_id' => null]);

            $payout->status = 'cancelled';
            $payout->cancelled_at = now();
            $payout->cancelled_by = Auth::id();
            $payout->cancel_reason = mb_substr($reason, 0, 500);
            $payout->save();

            return $payout;
        });
    }

    public static function fuelType(): ExpenseType
    {
        return ExpenseType::whereRaw('LOWER(TRIM(name)) = ?', ['fuel'])->orderByRaw("CASE WHEN scope = 'field' THEN 0 ELSE 1 END")->first()
            ?? ExpenseType::create(['name' => 'Fuel', 'scope' => 'field', 'created_by' => Auth::id()]);
    }
}
