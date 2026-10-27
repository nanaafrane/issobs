<?php

namespace App\Services\Banking;

use App\Models\AccountPayment;
use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * The analysed cash book: the Main Cash sheet (DATE | Payment details | CHEQ NO | PAYMENT |
 * RECEIPT | BALANCE | one column per item) built from the ledger, so the month-end total per
 * item is never typed or summed by hand.
 *
 * Every money-out line is spread over its items:
 *  - a payment of approved expenses -> each expense's type (Fuel, Rent, Vehicle Repairs &
 *    Maintenance…) and office, in proportion to the expense amounts;
 *  - any other payment -> its payment category (Salaries & wages, Bank charges…);
 *  - money moved to another of our accounts -> "Bank deposit / transfer out";
 *  - creditor repayments -> "Loan repayment"; MoMo charges, adjustments -> their own item.
 * Money coming back on a payment (cancelled, returned) is a negative on the same item.
 * Other money in (funding, receipts, loans received) goes in RECEIPT only, as on the sheet.
 *
 * For "all accounts", moves between our own accounts are left out (they are not spending).
 */
class CashBook
{
    public const TRANSFER_OUT = 'Bank deposit / transfer out';

    /**
     * @param  Bank|null  $bank  null = every account
     * @return array{bank: ?Bank, from: Carbon, to: Carbon, opening: ?float, closing: ?float, lines: Collection,
     *               items: Collection, offices: Collection, matrix: array, receipts: float, payments: float, analysed: float}
     */
    public function analysis(?Bank $bank, Carbon $from, Carbon $to): array
    {
        $range = [$from->toDateString(), $to->toDateString()];
        $opening = null;
        if ($bank) {
            $before = BankTransaction::where('bank_id', $bank->id)->where('entry_date', '<', $range[0])
                ->selectRaw('COALESCE(SUM(credit),0) - COALESCE(SUM(debit),0) AS net')->value('net');
            $opening = round((float) $bank->opening_balance + (float) $before, 2);
        }

        $ledger = BankTransaction::with('bank')
            ->when($bank, fn ($q) => $q->where('bank_id', $bank->id))
            ->when(! $bank, fn ($q) => $q->whereNotIn('channel', ['transfer_in', 'transfer_out']))
            ->whereBetween('entry_date', $range)
            ->orderBy('entry_date')->orderBy('id')->get();

        $payments = AccountPayment::whereIn('id', $ledger->pluck('payment_id')->filter()->unique())->get()->keyBy('id');
        $expenses = Expense::withTrashed()->with(['type', 'field'])->whereIn('payment_id', $payments->keys())->get()->groupBy('payment_id');

        $running = $opening;
        $lines = $ledger->map(function (BankTransaction $t) use (&$running, $payments, $expenses) {
            $credit = round((float) $t->credit, 2);
            $debit = round((float) $t->debit, 2);
            if ($running !== null) {
                $running = round($running + $credit - $debit, 2);
            }
            $payment = $t->payment_id ? $payments->get($t->payment_id) : null;

            return [
                'line' => $t,
                'date' => $t->entry_date,
                'details' => $t->narration,
                'reference' => $payment?->reference,
                'account' => $t->bank?->name,
                'payment' => $debit,
                'receipt' => $credit,
                'balance' => $running,
                'splits' => $this->splits($t, $payment, $payment ? $expenses->get($payment->id, collect()) : collect()),
            ];
        });

        // Totals per item, and per item and office.
        $items = [];
        $matrix = [];
        $offices = [];
        foreach ($lines as $l) {
            foreach ($l['splits'] as $s) {
                $items[$s['item']] = round(($items[$s['item']] ?? 0) + $s['amount'], 2);
                $office = $s['office'] ?? 'Head office / not set';
                $offices[$office] = true;
                $matrix[$s['item']][$office] = round(($matrix[$s['item']][$office] ?? 0) + $s['amount'], 2);
            }
        }
        $items = collect($items)->filter(fn ($v) => abs($v) > 0.004)->sortDesc();
        $officeList = collect(array_keys($offices))->sort(fn ($a, $b) => str_starts_with($a, 'Head office') <=> str_starts_with($b, 'Head office') ?: strcmp($a, $b))->values();

        return [
            'bank' => $bank,
            'from' => $from,
            'to' => $to,
            'opening' => $opening,
            'closing' => $running,
            'lines' => $lines,
            'items' => $items,
            'offices' => $officeList,
            'matrix' => $matrix,
            'receipts' => round((float) $lines->sum('receipt'), 2),
            'payments' => round((float) $lines->sum('payment'), 2),
            'analysed' => round((float) $items->sum(), 2),
        ];
    }

    /**
     * How much of one ledger line is spending, on which item and office. Spending is positive
     * (money out); money coming back on a payment is negative.
     *
     * @return array<int, array{item: string, office: ?string, amount: float}>
     */
    private function splits(BankTransaction $t, ?AccountPayment $payment, Collection $expenses): array
    {
        $spent = round((float) $t->debit - (float) $t->credit, 2);
        if (abs($spent) < 0.005) {
            return [];
        }

        if ($payment) {
            $live = $expenses->filter(fn ($e) => (float) $e->amount > 0);
            if ($live->isNotEmpty()) {
                return $this->spread($spent, $live->map(fn ($e) => [
                    'item' => $e->type?->name ?? 'Expenses (no type)',
                    'office' => $e->field?->name,
                    'weight' => (float) $e->amount,
                ])->values()->all());
            }

            return [['item' => AccountPayment::CATEGORIES[$payment->category] ?? ucfirst((string) $payment->category), 'office' => null, 'amount' => $spent]];
        }

        if ($spent < 0) {
            return [];   // money in that is not a refund: RECEIPT column only
        }

        $item = match ($t->channel) {
            'transfer_out' => self::TRANSFER_OUT,
            'loan_out' => 'Loan repayment',
            'momo_charge' => 'MoMo Charges',
            'adjustment' => 'Adjustments (short / correction)',
            'expense' => 'Expenses (old entries)',
            default => 'Other',
        };

        return [['item' => $item, 'office' => null, 'amount' => $spent]];
    }

    /** Split an amount over weighted parts, rounding so the parts add up to the amount exactly. */
    private function spread(float $amount, array $parts): array
    {
        $total = array_sum(array_column($parts, 'weight'));
        $out = [];
        $left = $amount;
        foreach ($parts as $i => $p) {
            $share = $i === array_key_last($parts) ? round($left, 2) : round($amount * $p['weight'] / $total, 2);
            $left = round($left - $share, 2);
            $key = $p['item'] . '|' . $p['office'];
            $out[$key] ??= ['item' => $p['item'], 'office' => $p['office'], 'amount' => 0.0];
            $out[$key]['amount'] = round($out[$key]['amount'] + $share, 2);
        }

        return array_values($out);
    }

    /** Lines × items, laid out like the Main Cash sheet (for the Excel export). */
    public function sheet(array $a): array
    {
        $items = $a['items']->keys()->all();
        $title = $a['bank']?->name ?? 'All accounts';
        $rows = [];
        $rows[] = ['Name of Account: ' . strtoupper((string) config('app.name')) . ' — ' . strtoupper($title)];
        $rows[] = ['', 'Period: ' . $a['from']->format('d M Y') . ' to ' . $a['to']->format('d M Y')];
        $rows[] = [];
        $rows[] = ['Currency:', 'GHC'];
        $rows[] = array_merge(['DATE', 'Payment details', 'CHEQ NO:', 'PAYMENT', 'RECEIPT', 'BALANCE'], $a['bank'] ? [] : ['ACCOUNT'], array_map('strtoupper', $items));
        if ($a['opening'] !== null) {
            $rows[] = [$a['from']->format('d.m.y'), 'Opening balance', '', null, null, $a['opening']];
        }
        foreach ($a['lines'] as $l) {
            $cells = [];
            foreach ($l['splits'] as $s) {
                $cells[$s['item']] = round(($cells[$s['item']] ?? 0) + $s['amount'], 2);
            }
            $rows[] = array_merge(
                [$l['date']?->format('d.m.y'), $l['details'], $l['reference'], $l['payment'] ?: null, $l['receipt'] ?: null, $l['balance']],
                $a['bank'] ? [] : [$l['account']],
                array_map(fn ($i) => isset($cells[$i]) && abs($cells[$i]) > 0.004 ? $cells[$i] : null, $items)
            );
        }
        $rows[] = array_merge(['', 'TOTAL', '', $a['payments'], $a['receipts'], $a['closing']], $a['bank'] ? [] : [''], array_values($a['items']->all()));

        return $rows;
    }
}
