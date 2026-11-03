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
 * MoMo paid into the company MoMo wallet (receipts.momo_to_bank_id) is already in an
 * account: it is credited to the wallet when the receipt is saved. Bank Deposit then
 * withdraws it — by ticking those receipts or by typing the amount cashed out — and the
 * slip shows as:
 *   wallet  debit   MoMo withdrawn                     (channel momo_withdrawal)
 *   bank    credit  cash + MoMo withdrawn              (channel deposit)
 *   bank    debit   the withdrawal charge              (channel momo_charge)
 * The charge is booked once (on the bank, as before) and named on the wallet's line.
 *
 * A wrong deposit is reversed (both lines posted back, receipts waiting again),
 * never deleted.
 */
class DepositBook
{
    /** @var array<int, array{bank:int, net:float}> receipt_id => the wallet its MoMo went into */
    private array $walletCache = [];

    /**
     * Everything still waiting to be deposited, oldest first.
     *
     * @param  array<int>|null  $fieldIds  limit to these offices (null = all)
     * @return Rows<int, object> one row per collection: collection, receipt/client
     *                           details, cash_due, momo_due, days_waiting
     */
    public function waiting(?array $fieldIds = null, bool $approved = true): Rows
    {
        $rows = Collection::query()
            ->leftJoin('receipts', 'receipts.id', '=', 'collections.receipt_id')
            ->leftJoin('clients', 'clients.id', '=', 'receipts.client_id')
            ->leftJoin('fields', 'fields.id', '=', 'collections.field_id')
            ->where(fn ($q) => $q->whereNull('collections.status')->orWhereRaw('LOWER(collections.status) <> ?', ['deposited']))
            ->when($fieldIds !== null, fn ($q) => $q->whereIn('collections.field_id', $fieldIds ?: [0]))
            // Only money the Finance Manager / a Director has approved (head office) can be banked or spent.
            ->where(fn ($q) => $approved
                ? $q->whereNull('collections.receipt_id')->orWhere('receipts.ho_status', 'approved')
                : $q->whereNotNull('collections.receipt_id')->where(fn ($w) => $w->whereNull('receipts.ho_status')->orWhere('receipts.ho_status', '!=', 'approved')))
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
        $this->loadWallets($rows->pluck('receipt_id')->filter()->all());

        $rows = $rows->map(function (Collection $c) use ($today) {
            $c->cash_due = $this->cashDue($c);
            $c->momo_due = $this->momoDue($c);
            $w = $this->walletOf($c);
            $c->wallet_id = $w['bank'] ?? null;
            $c->wallet_due = ($w && ! $c->momo_deposit_id && ! $this->closed($c)) ? $w['net'] : 0.0;
            $c->days_waiting = $c->created_at ? (int) $c->created_at->copy()->startOfDay()->diffInDays($today) : 0;

            return $c;
        });

        if ($approved) {
            $this->capToWallet($rows);
        }

        return $rows->filter(fn ($c) => $c->cash_due > 0 || $c->momo_due > 0 || $c->wallet_due > 0)->values();
    }

    /**
     * Wallet MoMo is listed only while it is still in the wallet: newest receipts first, up to
     * what the wallet can give now (its balance less MoMo awaiting approval). Older receipts
     * beyond that were already withdrawn by amount or spent from the wallet.
     */
    private function capToWallet(Rows $rows): void
    {
        $wallets = Bank::whereIn('id', $rows->pluck('wallet_id')->filter()->unique()->all() ?: [0])->get()->keyBy('id');
        foreach ($rows->where('wallet_due', '>', 0)->groupBy('wallet_id') as $walletId => $group) {
            $left = $wallets[$walletId]?->available() ?? 0.0;
            foreach ($group->sortByDesc(fn ($c) => [$c->created_at?->timestamp ?? 0, $c->id]) as $c) {
                if ($c->wallet_due <= $left + 0.004) {
                    $left = round($left - $c->wallet_due, 2);
                } else {
                    $c->wallet_due = 0.0;
                    $left = 0.0;    // keep it oldest-out: nothing older stays listed either
                }
            }
        }
    }

    /** MoMo still in this wallet, by collection, that can be ticked on a slip now. */
    public function inWallet(Bank $wallet): Rows
    {
        return $this->waiting(null)->where('wallet_id', $wallet->id)->where('wallet_due', '>', 0)->values();
    }

    /** @param array<int> $receiptIds */
    private function loadWallets(array $receiptIds): void
    {
        $receiptIds = array_values(array_diff(array_unique(array_map('intval', $receiptIds)), array_keys($this->walletCache)));
        if (! $receiptIds) {
            return;
        }
        foreach (array_chunk($receiptIds, 500) as $chunk) {
            $found = BankTransaction::whereIn('receipt_id', $chunk)->where('channel', 'momo')->whereNull('deposit_id')
                ->selectRaw('receipt_id, bank_id, COALESCE(SUM(credit),0) - COALESCE(SUM(debit),0) AS net')
                ->groupBy('receipt_id', 'bank_id')->get();
            foreach ($chunk as $id) {
                $this->walletCache[$id] = [];
            }
            foreach ($found as $f) {
                if ((float) $f->net > 0.004) {
                    $this->walletCache[(int) $f->receipt_id] = ['bank' => (int) $f->bank_id, 'net' => round((float) $f->net, 2)];
                }
            }
        }
    }

    /** @return array{bank:int, net:float}|array{} the wallet this collection's MoMo went into */
    private function walletOf(Collection $c): array
    {
        if (! $c->receipt_id) {
            return [];
        }
        if (! array_key_exists((int) $c->receipt_id, $this->walletCache)) {
            $this->loadWallets([(int) $c->receipt_id]);
        }

        return $this->walletCache[(int) $c->receipt_id] ?? [];
    }

    /** Cash / MoMo from receipts not yet approved at head office: shown, but not available yet. */
    public function awaitingApproval(?array $fieldIds = null): Rows
    {
        return $this->waiting($fieldIds, false);
    }

    /** Cash (plus an old, never-banked cheque) on this collection not yet deposited. */
    public function cashDue(Collection $c): float
    {
        if ($c->cash_deposit_id || $this->closed($c)) {
            return 0.0;
        }

        return BankPosting::depositable($c)['total'];
    }

    /** MoMo on this collection not yet withdrawn and deposited (old receipts, not paid into the wallet). */
    public function momoDue(Collection $c): float
    {
        if ($c->momo_deposit_id || $this->closed($c) || $this->walletOf($c)) {
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
        $walletIds = array_values(array_unique(array_map('intval', array_filter((array) ($input['wallet'] ?? [])))));
        $walletTyped = ($input['wallet_amount'] ?? null) === null || $input['wallet_amount'] === '' ? null : round((float) $input['wallet_amount'], 2);
        if (! $cashIds && ! $momoIds && ! $walletIds && ! ($walletTyped > 0)) {
            throw ValidationException::withMessages(['cash' => 'Tick the cash and/or MoMo you are depositing.']);
        }

        return DB::transaction(function () use ($input, $cashIds, $momoIds, $walletIds, $walletTyped) {
            $collections = Collection::whereIn('id', array_merge($cashIds, $momoIds, $walletIds))->lockForUpdate()->get()->keyBy('id');

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
            // Only receipts approved at head office (Finance Manager / Director) can be banked or spent.
            $receiptIds = $collections->pluck('receipt_id')->filter()->unique();
            $notApproved = $receiptIds->isEmpty() ? collect() : \App\Models\Receipt::whereIn('id', $receiptIds)
                ->where(fn ($q) => $q->whereNull('ho_status')->orWhere('ho_status', '!=', 'approved'))->pluck('id');
            if ($notApproved->isNotEmpty()) {
                throw ValidationException::withMessages(['cash' => 'Receipt FWSSR' . $notApproved->implode(', FWSSR')
                    . ' is not yet approved by the Finance Manager, so its money cannot be banked or spent yet.']);
            }

            // Where the money goes: a bank, an office cash box, or the MoMo wallet.
            $bank = Bank::whereKey($input['bank_id'] ?? 0)->lockForUpdate()->first();
            if (! $bank || $bank->is_active === false) {
                throw ValidationException::withMessages(['bank_id' => 'Choose the account the money goes into.']);
            }

            // MoMo withdrawn from the company wallet for this slip.
            [$wallet, $fromWallet] = $this->walletPart($input, $collections, $walletIds, $walletTyped, $bank);

            $cash = round($cash, 2);
            $cheque = round($cheque, 2);
            $momo = round($momo + $fromWallet, 2);
            $charge = round((float) ($input['momo_charge'] ?? 0), 2);
            if ($bank->isWallet()) {
                // MoMo already in the wallet is not withdrawn: no charge.
                if ($charge > 0) {
                    throw ValidationException::withMessages(['momo_charge' => 'MoMo kept in the wallet is not withdrawn, so there is no withdrawal charge.']);
                }
                $input['momo_charge'] = 0;
            }
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
            $date = Carbon::parse($input['deposit_date'])->startOfDay();

            $deposit = BankDeposit::create([
                'bank_id' => $bank->id,
                'user_id' => Auth::id(),
                'cash_amount' => $cash,
                'cheque_amount' => $cheque,
                'momo_amount' => $momo,
                'momo_charge' => $momo > 0 ? $charge : null,
                'wallet_id' => $wallet?->id,
                'wallet_amount' => $wallet ? $fromWallet : null,
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
            foreach (array_merge($momoIds, $walletIds) as $id) {
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
                $momo - $fromWallet > 0.004 ? 'MoMo ' . number_format($momo - $fromWallet, 2) : null,
                $fromWallet > 0 ? 'MoMo from ' . $wallet->name . ' ' . number_format($fromWallet, 2) : null,
            ])->filter()->implode(' + ');
            $receiptIds = array_merge($cashIds, $momoIds, $walletIds);

            if ($wallet) {
                // Out of the wallet first (it can only give what it holds, less MoMo awaiting approval).
                $this->post($wallet, $deposit, 'momo_withdrawal', -$fromWallet,
                    'MoMo withdrawn for deposit' . $this->slipLabel($deposit) . ' into ' . $bank->name
                    . ($charge > 0 ? ' — withdrawal charge GH₵ ' . number_format($charge, 2) . ' (debited on ' . $bank->name . ')' : '')
                    . ($walletIds ? ' · ' . $receipts($walletIds) : ''), 'wallet_amount');
            }

            $into = match (true) {
                $bank->isCash() => 'Collections kept as cash',
                $bank->isWallet() => 'Collections into the MoMo wallet',
                default => 'Deposit',
            };
            $this->post($bank, $deposit, 'deposit', $gross,
                $into . $this->slipLabel($deposit) . ' — ' . $what
                . ($charge > 0 ? ' (slip ' . number_format($slip, 2) . ' after MoMo charge)' : '')
                . ($receiptIds ? ' · ' . $receipts($receiptIds) : ''));

            if ($charge > 0) {
                $this->post($bank, $deposit, 'momo_charge', -$charge,
                    'MoMo withdrawal charge' . $this->slipLabel($deposit) . ' — on MoMo ' . number_format($momo, 2)
                    . ' withdrawn for deposit #' . $deposit->id . ($wallet ? ' from ' . $wallet->name : '')
                    . (array_merge($momoIds, $walletIds) ? ' · ' . $receipts(array_merge($momoIds, $walletIds)) : ''));
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
            if ($deposit->wallet_id && (float) $deposit->wallet_amount > 0) {
                $wallet = Bank::whereKey($deposit->wallet_id)->lockForUpdate()->firstOrFail();
                $this->post($wallet, $deposit, 'momo_withdrawal', (float) $deposit->wallet_amount, 'MoMo put back — reversal of deposit #' . $deposit->id . $this->slipLabel($deposit) . $why);
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
     * The wallet part of a slip: either the ticked wallet receipts (their total) or a typed amount.
     *
     * @return array{0: ?Bank, 1: float}
     */
    private function walletPart(array $input, Rows $collections, array $walletIds, ?float $typed, Bank $into): array
    {
        if (! $walletIds && ! ($typed > 0)) {
            return [null, 0.0];
        }

        $walletId = (int) ($input['wallet_id'] ?? 0);
        $ticked = 0.0;
        if ($walletIds) {
            $this->loadWallets($collections->only($walletIds)->pluck('receipt_id')->filter()->all());
            $of = collect($walletIds)->map(fn ($id) => $this->walletOf($collections[$id]))->filter();
            if ($of->count() !== count($walletIds) || $of->pluck('bank')->unique()->count() > 1) {
                throw ValidationException::withMessages(['wallet' => 'Tick MoMo from one wallet at a time.']);
            }
            $walletId = (int) $of->first()['bank'];
        }

        $wallet = Bank::whereKey($walletId)->lockForUpdate()->first();
        if (! $wallet || ! $wallet->isWallet()) {
            throw ValidationException::withMessages(['wallet_id' => 'Choose the MoMo wallet the money was withdrawn from.']);
        }
        if ($into->id === $wallet->id) {
            throw ValidationException::withMessages(['bank_id' => 'The MoMo is withdrawn from ' . $wallet->name . ' — choose the bank or cash box it goes into.']);
        }

        if ($walletIds) {
            $still = $this->inWallet($wallet)->keyBy('id');
            $gone = array_values(array_filter($walletIds, fn ($id) => ! isset($still[$id])));
            if ($gone) {
                throw ValidationException::withMessages(['wallet' => 'Some ticked MoMo is no longer in ' . $wallet->name
                    . ' (already withdrawn or spent). The list has been refreshed — check it and try again.']);
            }
            $ticked = round((float) collect($walletIds)->sum(fn ($id) => $still[$id]->wallet_due), 2);
            if ($typed !== null && $typed > 0 && abs($typed - $ticked) > 0.004) {
                throw ValidationException::withMessages(['wallet_amount' => 'The ticked MoMo receipts come to GH₵ ' . number_format($ticked, 2)
                    . ' — untick them to withdraw a different amount.']);
            }
        }
        $amount = $walletIds ? $ticked : (float) $typed;

        if ($amount > $wallet->available() + 0.004) {
            throw ValidationException::withMessages(['wallet_amount' => $wallet->name . ' can give GH₵ ' . number_format(max(0, $wallet->available()), 2) . ' now'
                . ($wallet->awaitingApproval() > 0 ? ' (GH₵ ' . number_format($wallet->awaitingApproval(), 2) . ' more is from receipts not yet approved)' : '')
                . ' — it cannot withdraw GH₵ ' . number_format($amount, 2) . '.']);
        }

        return [$wallet, round($amount, 2)];
    }

    /**
     * Status from what is still waiting: undeposited → Part deposited → Deposited.
     * "Banked" (cheque/transfer only, posted from the receipt) is left alone.
     */
    public function refreshStatus(Collection $c): void
    {
        $cashOwed = BankPosting::depositable($c)['total'] > 0;
        $momoOwed = (float) $c->momo_amount > 0 && ! $this->walletOf($c);   // wallet MoMo is already in an account
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

    private function post(Bank $bank, BankDeposit $deposit, string $channel, float $amount, string $narration, string $errorField = 'reversal_reason'): void
    {
        $balance = round((float) $bank->total + $amount, 2);
        if ($amount < 0 && $bank->cannotOverdraw() && $balance < -0.004) {
            // e.g. reversing collections kept in an office cash box after they were spent.
            throw ValidationException::withMessages([$errorField => $bank->name . ' only holds GH₵ ' . number_format((float) $bank->total, 2)
                . ' — this money has already been spent from it, so it cannot be taken back out.']);
        }
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
