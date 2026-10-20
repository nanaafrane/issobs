<?php

namespace App\Services\Banking;

use App\Models\Bank;
use App\Models\BankDeposit;
use App\Models\BankTransaction;
use App\Models\Collection;
use App\Services\Receipts\BankPosting;
use Carbon\Carbon;
use Illuminate\Support\Collection as Rows;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Bank Deposit: cash and MoMo waiting to go to the bank, and the slips that take them.
 *
 * Each receipt's money waits in its collection in two parts:
 *   cash  — counted notes (plus any cheque received before cheques were posted
 *           straight from the receipt). Can be deposited on its own.
 *   MoMo  — sits in the MoMo wallet. It has to be withdrawn as cash before it can
 *           be deposited, and the withdrawal costs a charge. Deposited on its own
 *           or on the same slip as cash.
 * The two parts can go on different slips/days.
 *
 * One deposit = one bank slip. On the bank account it shows as:
 *   credit  cash + MoMo withdrawn   "Deposit — slip 123 …"            (channel deposit)
 *   debit   the withdrawal charge   "MoMo withdrawal charge — slip 123 …" (channel momo_charge)
 * so the net effect is exactly the amount on the slip, and every cedi of the
 * charge is visible on its own line of the bank's debits.
 *
 * A wrong deposit is reversed (both lines posted back, receipts waiting again),
 * never deleted.
 */
class DepositBook
{
    /**
     * Everything still waiting to be deposited, oldest first.
     *
     * @param  array<int>|null  $fieldIds  limit to these offices (null = all)
     * @return Rows<int, object> one row per collection: collection, receipt/client
     *                           details, cash_due, momo_due, days_waiting
     */
    public function waiting(?array $fieldIds = null): Rows
    {
        $rows = Collection::query()
            ->leftJoin('receipts', 'receipts.id', '=', 'collections.receipt_id')
            ->leftJoin('clients', 'clients.id', '=', 'receipts.client_id')
            ->leftJoin('fields', 'fields.id', '=', 'collections.field_id')
            ->where(fn ($q) => $q->whereNull('collections.status')->orWhereRaw('LOWER(collections.status) <> ?', ['deposited']))
            ->when($fieldIds !== null, fn ($q) => $q->whereIn('collections.field_id', $fieldIds ?: [0]))
            ->orderBy('collections.created_at')
            ->orderBy('collections.id')
            ->get([
                'collections.*',
                'receipts.momo_transactin_id as momo_ref',
                'receipts.receipt_month',
                'receipts.from as paid_by',
                DB::raw('COALESCE(clients.business_name, clients.name) as client_name'),
                'fields.name as office',
            ]);

        $today = now()->startOfDay();

        return $rows->map(function (Collection $c) use ($today) {
            $c->cash_due = $this->cashDue($c);
            $c->momo_due = $this->momoDue($c);
            $c->days_waiting = $c->created_at ? (int) $c->created_at->copy()->startOfDay()->diffInDays($today) : 0;

            return $c;
        })->filter(fn ($c) => $c->cash_due > 0 || $c->momo_due > 0)->values();
    }

    /** Cash (plus an old, never-banked cheque) on this collection not yet deposited. */
    public function cashDue(Collection $c): float
    {
        if ($c->cash_deposit_id || $this->closed($c)) {
            return 0.0;
        }

        return BankPosting::depositable($c)['total'];
    }

    /** MoMo on this collection not yet withdrawn and deposited. */
    public function momoDue(Collection $c): float
    {
        if ($c->momo_deposit_id || $this->closed($c)) {
            return 0.0;
        }

        return round((float) $c->momo_amount, 2);
    }

    /**
     * Make one deposit (one bank slip).
     *
     * @param  array{bank_id:int, deposit_date:string, slip_number?:?string, depositor?:?string, notes?:?string,
     *               cash?:array<int>, momo?:array<int>, momo_charge?:float|string|null}  $input
     */
    public function deposit(array $input): BankDeposit
    {
        $cashIds = array_values(array_unique(array_map('intval', array_filter((array) ($input['cash'] ?? [])))));
        $momoIds = array_values(array_unique(array_map('intval', array_filter((array) ($input['momo'] ?? [])))));
        if (! $cashIds && ! $momoIds) {
            throw ValidationException::withMessages(['cash' => 'Tick the cash and/or MoMo you are depositing.']);
        }

        return DB::transaction(function () use ($input, $cashIds, $momoIds) {
            $collections = Collection::whereIn('id', array_merge($cashIds, $momoIds))->lockForUpdate()->get()->keyBy('id');

            $cash = 0.0;
            $cheque = 0.0;
            $momo = 0.0;
            $taken = [];
            foreach ($cashIds as $id) {
                $c = $collections[$id] ?? null;
                if (! $c || $this->cashDue($c) <= 0) {
                    $taken[] = $id;
                    continue;
                }
                $parts = BankPosting::depositable($c);
                $cash += $parts['cash'];
                $cheque += $parts['cheque'];
            }
            foreach ($momoIds as $id) {
                $c = $collections[$id] ?? null;
                if (! $c || $this->momoDue($c) <= 0) {
                    $taken[] = $id;
                    continue;
                }
                $momo += $this->momoDue($c);
            }
            if ($taken) {
                throw ValidationException::withMessages(['cash' => 'Some of the ticked money has already been deposited (collection #'
                    . implode(', #', array_unique($taken)) . '). The list has been refreshed — check it and try again.']);
            }

            $cash = round($cash, 2);
            $cheque = round($cheque, 2);
            $momo = round($momo, 2);
            $charge = round((float) ($input['momo_charge'] ?? 0), 2);
            if ($momo <= 0 && $charge > 0) {
                throw ValidationException::withMessages(['momo_charge' => 'There is no MoMo on this deposit, so there is no withdrawal charge.']);
            }
            if ($momo > 0 && ($input['momo_charge'] ?? null) === null) {
                throw ValidationException::withMessages(['momo_charge' => 'Enter the MoMo withdrawal charge (0 if there was none).']);
            }
            if ($charge < 0 || ($momo > 0 && $charge >= $momo)) {
                throw ValidationException::withMessages(['momo_charge' => 'The withdrawal charge must be less than the MoMo withdrawn (GH₵ ' . number_format($momo, 2) . ').']);
            }

            $gross = round($cash + $cheque + $momo, 2);
            $slip = round($gross - $charge, 2);
            $bank = Bank::whereKey($input['bank_id'])->lockForUpdate()->firstOrFail();
            $date = Carbon::parse($input['deposit_date'])->startOfDay();

            $deposit = BankDeposit::create([
                'bank_id' => $bank->id,
                'user_id' => Auth::id(),
                'cash_amount' => $cash,
                'cheque_amount' => $cheque,
                'momo_amount' => $momo,
                'momo_charge' => $momo > 0 ? $charge : null,
                'gross_amount' => $gross,
                'total' => $slip,
                'deposit_date' => $date->toDateString(),
                'slip_number' => $this->clean($input['slip_number'] ?? null, 60),
                'depositor' => $this->clean($input['depositor'] ?? null, 120) ?? Auth::user()?->name,
                'notes' => $this->clean($input['notes'] ?? null, 500),
            ]);

            foreach ($cashIds as $id) {
                $collections[$id]->cash_deposit_id = $deposit->id;
            }
            foreach ($momoIds as $id) {
                $collections[$id]->momo_deposit_id = $deposit->id;
            }
            foreach ($collections as $c) {
                $this->refreshStatus($c);
                $c->save();
            }

            $receipts = fn (array $ids) => $this->receiptList($collections->only($ids));
            $what = collect([
                $cash > 0 ? 'cash ' . number_format($cash, 2) : null,
                $cheque > 0 ? 'old cheque ' . number_format($cheque, 2) : null,
                $momo > 0 ? 'MoMo ' . number_format($momo, 2) : null,
            ])->filter()->implode(' + ');

            $this->post($bank, $deposit, 'deposit', $gross,
                'Deposit' . $this->slipLabel($deposit) . ' — ' . $what
                . ($charge > 0 ? ' (slip ' . number_format($slip, 2) . ' after MoMo charge)' : '')
                . ' · ' . $receipts(array_merge($cashIds, $momoIds)));

            if ($charge > 0) {
                $this->post($bank, $deposit, 'momo_charge', -$charge,
                    'MoMo withdrawal charge' . $this->slipLabel($deposit) . ' — on MoMo ' . number_format($momo, 2)
                    . ' withdrawn for deposit #' . $deposit->id . ' · ' . $receipts($momoIds));
            }

            return $deposit;
        });
    }

    /** Undo a deposit made in error: bank lines posted back, its money waiting again. */
    public function reverse(BankDeposit $deposit, string $reason): BankDeposit
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages(['reversal_reason' => 'Give a reason for reversing this deposit (at least 5 characters).']);
        }

        return DB::transaction(function () use ($deposit, $reason) {
            $deposit = BankDeposit::whereKey($deposit->id)->lockForUpdate()->firstOrFail();
            if ($deposit->reversed_at) {
                throw ValidationException::withMessages(['reversal_reason' => 'This deposit has already been reversed.']);
            }
            if ($deposit->gross_amount === null) {
                throw ValidationException::withMessages(['reversal_reason' => 'Deposits made before slips were recorded cannot be reversed here.']);
            }

            $bank = Bank::whereKey($deposit->bank_id)->lockForUpdate()->firstOrFail();
            $why = ' (' . mb_substr($reason, 0, 120) . ')';
            $this->post($bank, $deposit, 'deposit', -(float) $deposit->gross_amount, 'Reversal of deposit #' . $deposit->id . $this->slipLabel($deposit) . $why);
            if ((float) $deposit->momo_charge > 0) {
                $this->post($bank, $deposit, 'momo_charge', (float) $deposit->momo_charge, 'Reversal of MoMo withdrawal charge, deposit #' . $deposit->id . $this->slipLabel($deposit) . $why);
            }

            $collections = Collection::where('cash_deposit_id', $deposit->id)->orWhere('momo_deposit_id', $deposit->id)->lockForUpdate()->get();
            foreach ($collections as $c) {
                if ((int) $c->cash_deposit_id === $deposit->id) {
                    $c->cash_deposit_id = null;
                }
                if ((int) $c->momo_deposit_id === $deposit->id) {
                    $c->momo_deposit_id = null;
                }
                $this->refreshStatus($c);
                $c->save();
            }

            $deposit->reversed_at = now();
            $deposit->reversed_by = Auth::id();
            $deposit->reversal_reason = mb_substr($reason, 0, 500);
            $deposit->save();

            return $deposit;
        });
    }

    /**
     * Close collections whose money was banked some other way before this screen
     * existed (e.g. old MoMo withdrawn and deposited by hand). Nothing is posted to
     * any bank; the reason is kept on each collection.
     *
     * @param  array<int>  $ids
     */
    public function markAlreadyBanked(array $ids, string $reason): int
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages(['banked_reason' => 'Say how this money was banked (at least 5 characters).']);
        }
        $ids = array_values(array_unique(array_map('intval', array_filter($ids))));
        if (! $ids) {
            throw ValidationException::withMessages(['banked_reason' => 'Tick the collections to mark as already banked.']);
        }

        return DB::transaction(function () use ($ids, $reason) {
            $n = 0;
            foreach (Collection::whereIn('id', $ids)->lockForUpdate()->get() as $c) {
                if ($this->cashDue($c) <= 0 && $this->momoDue($c) <= 0) {
                    continue;
                }
                $c->status = 'Deposited';
                $c->deposit_note = mb_substr('Marked already banked by ' . (Auth::user()?->name ?? 'user #' . Auth::id())
                    . ' on ' . now()->format('d M Y') . ': ' . $reason, 0, 500);
                $c->save();
                $n++;
            }

            return $n;
        });
    }

    /**
     * Status from what is still waiting: undeposited → Part deposited → Deposited.
     * "Banked" (cheque/transfer only, posted from the receipt) is left alone.
     */
    public function refreshStatus(Collection $c): void
    {
        $cashOwed = BankPosting::depositable($c)['total'] > 0;
        $momoOwed = (float) $c->momo_amount > 0;
        $cashLeft = $cashOwed && ! $c->cash_deposit_id;
        $momoLeft = $momoOwed && ! $c->momo_deposit_id;
        $someDone = ($cashOwed && $c->cash_deposit_id) || ($momoOwed && $c->momo_deposit_id);

        if (! $cashLeft && ! $momoLeft) {
            $c->status = $someDone ? 'Deposited' : ($c->status ?: 'undeposited');
        } else {
            $c->status = $someDone ? 'Part deposited' : 'undeposited';
        }
    }

    /** True when any part of this receipt's money has been taken to the bank. */
    public static function anyPartDeposited(?Collection $c): bool
    {
        return $c !== null && ($c->cash_deposit_id || $c->momo_deposit_id || strcasecmp((string) $c->status, 'deposited') === 0);
    }

    /** Deposited before slips existed, or marked already banked: nothing waits on it. */
    private function closed(Collection $c): bool
    {
        return strcasecmp((string) $c->status, 'deposited') === 0 && ! $c->cash_deposit_id && ! $c->momo_deposit_id;
    }

    private function post(Bank $bank, BankDeposit $deposit, string $channel, float $amount, string $narration): void
    {
        $balance = round((float) $bank->total + $amount, 2);
        BankTransaction::create([
            'bank_id' => $bank->id,
            'deposit_id' => $deposit->id,
            'channel' => $channel,
            'credit' => $amount > 0 ? round($amount, 2) : null,
            'debit' => $amount < 0 ? round(-$amount, 2) : null,
            'balance' => $balance,
            'narration' => mb_substr($narration, 0, 255),
        ]);
        $bank->total = $balance;
        $bank->save();
    }

    private function slipLabel(BankDeposit $deposit): string
    {
        return ($deposit->slip_number ? ' slip ' . $deposit->slip_number : ' #' . $deposit->id)
            . ' of ' . Carbon::parse($deposit->deposit_date)->format('d M Y');
    }

    private function receiptList(Rows $collections): string
    {
        $refs = $collections->map(fn ($c) => $c->receipt_id ? 'FWSSR' . $c->receipt_id : 'collection #' . $c->id)->unique()->values();
        $shown = $refs->take(6)->implode(', ');

        return ($refs->count() === 1 ? 'receipt ' : 'receipts ') . $shown . ($refs->count() > 6 ? ' +' . ($refs->count() - 6) . ' more' : '');
    }

    private function clean(?string $value, int $max): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }
}
