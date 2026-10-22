<?php

namespace App\Services\Banking;

use App\Models\AccountPayment;
use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Figures for the bank & cash pages, all from the ledger (bank_transactions), by the
 * day the money moved (entry_date).
 *
 *  statement()  one account: opening balance, every line with running balance, closing.
 *  flow()       where money came from and where it went, by source / payment category.
 *               Transfers between our own accounts are shown apart and are NOT money
 *               in or out of the business.
 *  checks()     accounts whose stored balance no longer equals opening + ledger.
 */
class AccountReports
{
    /** Ledger channels → the line in "where money came from / went". */
    public const SOURCES = [
        'receipt_cheque' => 'Cheques received from clients',
        'receipt_transfer' => 'Transfers received from clients',
        'deposit' => 'Cash & MoMo deposited',
        'momo_charge' => 'MoMo withdrawal charges',
        'adjustment' => 'Adjustments',
        'expense' => 'Expenses (old entries)',
        'other' => 'Other',
    ];

    /**
     * @return array{bank: Bank, from: Carbon, to: Carbon, opening: float, closing: float,
     *               lines: Collection, in: float, out: float, uncleared: Collection}
     */
    public function statement(Bank $bank, Carbon $from, Carbon $to): array
    {
        $before = BankTransaction::where('bank_id', $bank->id)->where('entry_date', '<', $from->toDateString())
            ->selectRaw('COALESCE(SUM(credit),0) - COALESCE(SUM(debit),0) AS net')->value('net');
        $opening = round((float) $bank->opening_balance + (float) $before, 2);

        $running = $opening;
        $lines = BankTransaction::with(['payment', 'transfer'])
            ->where('bank_id', $bank->id)
            ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('entry_date')->orderBy('id')
            ->get()
            ->map(function (BankTransaction $t) use (&$running) {
                $running = round($running + (float) $t->credit - (float) $t->debit, 2);
                $t->running = $running;

                return $t;
            });

        return [
            'bank' => $bank,
            'from' => $from,
            'to' => $to,
            'opening' => $opening,
            'closing' => $running,
            'lines' => $lines,
            'in' => round((float) $lines->sum('credit'), 2),
            'out' => round((float) $lines->sum('debit'), 2),
            'uncleared' => AccountPayment::where('bank_id', $bank->id)->where('status', 'issued')->orderBy('payment_date')->get(),
        ];
    }

    /**
     * @param  array<int>|null  $bankIds  null = every account
     * @return array{in: Collection, out: Collection, total_in: float, total_out: float, net: float,
     *               internal: float, internal_count: int}
     */
    public function flow(Carbon $from, Carbon $to, ?array $bankIds = null): array
    {
        $rows = BankTransaction::query()
            ->leftJoin('account_payments', 'account_payments.id', '=', 'bank_transactions.payment_id')
            ->whereBetween('bank_transactions.entry_date', [$from->toDateString(), $to->toDateString()])
            ->when($bankIds !== null, fn ($q) => $q->whereIn('bank_transactions.bank_id', $bankIds ?: [0]))
            ->get(['bank_transactions.channel', 'bank_transactions.receipt_id', 'bank_transactions.credit', 'bank_transactions.debit', 'account_payments.category']);

        $in = [];
        $out = [];
        $internal = 0.0;
        $internalCount = 0;
        // Borrowed money (creditors / director's loan) and paying it back: financing, not income or spending.
        $financing = ['received' => 0.0, 'repaid' => 0.0];
        foreach ($rows as $r) {
            if (in_array($r->channel, ['transfer_in', 'transfer_out'], true)) {
                $internal += (float) $r->credit;
                $internalCount += $r->credit > 0 ? 1 : 0;
                continue;
            }
            if ($r->channel === 'loan_in') {
                $financing['received'] += (float) $r->credit - (float) $r->debit;
                continue;
            }
            if ($r->channel === 'loan_out') {
                $financing['repaid'] += (float) $r->debit - (float) $r->credit;
                continue;
            }
            [$key, $label] = $this->classify($r);
            $net = (float) $r->credit - (float) $r->debit;
            // A reversal / cancellation reduces the line it undoes instead of counting as new money.
            $side = $this->isOutflow($r) ? 'out' : 'in';
            if ($side === 'in') {
                $in[$key] ??= ['label' => $label, 'amount' => 0.0, 'count' => 0];
                $in[$key]['amount'] += $net;
                $in[$key]['count']++;
            } else {
                $out[$key] ??= ['label' => $label, 'amount' => 0.0, 'count' => 0];
                $out[$key]['amount'] -= $net;
                $out[$key]['count']++;
            }
        }

        $tidy = fn (array $list) => collect($list)
            ->map(fn ($v) => ['label' => $v['label'], 'amount' => round($v['amount'], 2), 'count' => $v['count']])
            ->filter(fn ($v) => abs($v['amount']) > 0.004)
            ->sortByDesc('amount');
        $in = $tidy($in);
        $out = $tidy($out);

        return [
            'in' => $in,
            'out' => $out,
            'total_in' => round($in->sum('amount'), 2),
            'total_out' => round($out->sum('amount'), 2),
            'net' => round($in->sum('amount') - $out->sum('amount'), 2),
            'internal' => round($internal, 2),
            'internal_count' => $internalCount,
            'financing' => ['received' => round($financing['received'], 2), 'repaid' => round($financing['repaid'], 2)],
        ];
    }

    /** Accounts whose balance ≠ opening + ledger (should never happen; flagged for review). */
    public function checks(Collection $banks): array
    {
        $net = BankTransaction::selectRaw('bank_id, COALESCE(SUM(credit),0) - COALESCE(SUM(debit),0) AS net')
            ->groupBy('bank_id')->pluck('net', 'bank_id');
        $out = [];
        foreach ($banks as $b) {
            $expected = round((float) $b->opening_balance + (float) ($net[$b->id] ?? 0), 2);
            if (abs($expected - (float) $b->total) > 0.004) {
                $out[$b->id] = $expected;
            }
        }

        return $out;
    }

    /** Approved expenses not yet paid. */
    public function awaitingPayment(?array $fieldIds = null): Collection
    {
        return Expense::with(['type', 'field'])->awaitingPayment()
            ->when($fieldIds !== null, fn ($q) => $q->whereIn('field_id', $fieldIds ?: [0]))
            ->orderBy('expense_date')->orderBy('id')->get();
    }

    /** @return array{0: string, 1: string} key and label for a ledger line */
    private function classify(object $r): array
    {
        if ($r->channel === 'payment') {
            $cat = $r->category ?: 'other';

            return ['pay_' . $cat, AccountPayment::CATEGORIES[$cat] ?? ucfirst($cat)];
        }
        if (in_array($r->channel, ['cheque', 'transfer'], true) && $r->receipt_id) {
            $k = 'receipt_' . $r->channel;

            return [$k, self::SOURCES[$k]];
        }
        if ($r->channel === 'adjustment') {
            return [((float) $r->credit > 0 ? 'adjust_in' : 'adjust_out'), self::SOURCES['adjustment']];
        }
        $k = array_key_exists((string) $r->channel, self::SOURCES) ? $r->channel : 'other';

        return [$k, self::SOURCES[$k]];
    }

    private function isOutflow(object $r): bool
    {
        return match ($r->channel) {
            'payment', 'momo_charge', 'expense' => true,
            'adjustment' => (float) $r->debit > 0,
            default => false,   // receipts and deposits (their reversals reduce money in)
        };
    }
}
