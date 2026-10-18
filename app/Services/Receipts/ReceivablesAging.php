<?php

namespace App\Services\Receipts;

use App\Models\Client;
use App\Models\Receipt;
use App\Models\ReceiptAllocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Aged receivables: what every client still owes, by how overdue it is.
 *
 * "Owed" on an invoice = invoice total − everything settled against it
 * (the same figure as the statement of account's open invoices), so this
 * report and the statements always agree. Overdue days count from the
 * invoice due date (old invoices with no due date: end of the month after
 * the invoice month). Unused client credit is shown next to it and netted off.
 */
class ReceivablesAging
{
    public const BUCKETS = [
        'current' => 'Not yet due',
        'd30' => '1–30 days',
        'd60' => '31–60 days',
        'd90' => '61–90 days',
        'd90plus' => 'Over 90 days',
    ];

    /**
     * @param  array<int>|null  $fieldIds  limit to these field offices (null = all)
     */
    public function build(?array $fieldIds = null, ?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();

        $invoices = $this->owingInvoices()
            ->when($fieldIds !== null, fn ($q) => $q->whereIn('clients.field_id', $fieldIds ?: [0]))
            ->get();

        $credit = Receipt::where('unapplied_amount', '>', 0)
            ->when($fieldIds !== null, fn ($q) => $q->whereHas('client', fn ($c) => $c->whereIn('field_id', $fieldIds ?: [0])))
            ->selectRaw('client_id, SUM(unapplied_amount) as credit')->groupBy('client_id')
            ->pluck('credit', 'client_id');

        $rows = [];
        foreach ($invoices as $inv) {
            ['owed' => $owed, 'days' => $days, 'bucket' => $bucket] = $this->age($inv, $asOf);

            $row = &$rows[$inv->client_id];
            $row ??= array_fill_keys(array_keys(self::BUCKETS), 0.0) + ['client_id' => $inv->client_id, 'owed' => 0.0, 'invoices' => 0, 'oldest_days' => 0];
            $row[$bucket] = round($row[$bucket] + $owed, 2);
            $row['owed'] = round($row['owed'] + $owed, 2);
            $row['invoices']++;
            $row['oldest_days'] = max($row['oldest_days'], $days);
            unset($row);
        }

        // Clients with credit but nothing owed still appear (they are owed money back / ahead).
        foreach ($credit as $clientId => $amount) {
            $rows[$clientId] ??= array_fill_keys(array_keys(self::BUCKETS), 0.0) + ['client_id' => $clientId, 'owed' => 0.0, 'invoices' => 0, 'oldest_days' => 0];
        }

        $clients = Client::with('field')->whereIn('id', array_keys($rows))->get()->keyBy('id');
        foreach ($rows as $id => &$row) {
            $c = $clients[$id] ?? null;
            $row['client'] = $c ? ($c->business_name ?: $c->name) : 'Client #' . $id;
            $row['contact'] = $c?->name;
            $row['field'] = $c?->field?->name;
            $row['credit'] = round((float) ($credit[$id] ?? 0), 2);
            $row['net'] = round($row['owed'] - $row['credit'], 2);
        }
        unset($row);

        $rows = collect($rows)->sortByDesc('net')->values();
        $totals = array_fill_keys(array_merge(array_keys(self::BUCKETS), ['owed', 'credit', 'net']), 0.0);
        foreach ($rows as $row) {
            foreach ($totals as $k => $v) {
                $totals[$k] = round($v + $row[$k], 2);
            }
        }

        return ['as_of' => $asOf, 'rows' => $rows, 'totals' => $totals];
    }

    /**
     * One client's invoices that still have something owing, oldest first,
     * each with its due date, days overdue and age bucket — what you see when
     * you click a client on the aged receivables report.
     */
    public function forClient(Client $client, ?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();

        $invoices = $this->owingInvoices()
            ->where('invoices.client_id', $client->id)
            ->orderByRaw('COALESCE(invoices.due_date, invoices.invoice_month)')
            ->orderBy('invoices.id')
            ->get()
            ->map(function ($inv) use ($asOf) {
                $age = $this->age($inv, $asOf);
                $inv->owed = $age['owed'];
                $inv->paid = round((float) $inv->settled, 2);
                $inv->due = $age['due'];
                $inv->days_overdue = $age['days'];
                $inv->bucket = $age['bucket'];
                $inv->invoice_month = $inv->invoice_month ? Carbon::parse($inv->invoice_month) : null;

                return $inv;
            });

        $credits = Receipt::where('client_id', $client->id)->where('unapplied_amount', '>', 0)
            ->orderBy('id')->get(['id', 'receipt_month', 'unapplied_amount']);

        $totals = array_fill_keys(array_keys(self::BUCKETS), 0.0);
        foreach ($invoices as $inv) {
            $totals[$inv->bucket] = round($totals[$inv->bucket] + $inv->owed, 2);
        }
        $owed = round($invoices->sum('owed'), 2);
        $credit = round((float) $credits->sum('unapplied_amount'), 2);

        return [
            'as_of' => $asOf,
            'invoices' => $invoices,
            'credits' => $credits,
            'totals' => $totals + ['owed' => $owed, 'credit' => $credit, 'net' => round($owed - $credit, 2)],
        ];
    }

    /** Invoices with something still owing: total − everything settled against them. */
    private function owingInvoices()
    {
        $settled = ReceiptAllocation::selectRaw('invoice_id, SUM(settled) as settled')->groupBy('invoice_id');

        return DB::table('invoices')
            ->join('clients', 'clients.id', '=', 'invoices.client_id')
            ->leftJoinSub($settled, 's', 's.invoice_id', '=', 'invoices.id')
            ->whereRaw('invoices.total - COALESCE(s.settled, 0) > 0.004')
            ->select(['invoices.id', 'invoices.client_id', 'invoices.total', 'invoices.due_date', 'invoices.invoice_month', DB::raw('COALESCE(s.settled, 0) as settled')]);
    }

    /** @return array{owed: float, due: Carbon, days: int, bucket: string} */
    private function age(object $inv, Carbon $asOf): array
    {
        $owed = round((float) $inv->total - (float) $inv->settled, 2);
        $due = $inv->due_date
            ? Carbon::parse($inv->due_date)->startOfDay()
            : Carbon::parse($inv->invoice_month)->addMonthNoOverflow()->endOfMonth()->startOfDay();
        $days = $asOf->gt($due) ? (int) $due->diffInDays($asOf) : 0;
        $bucket = match (true) {
            $days <= 0 => 'current',
            $days <= 30 => 'd30',
            $days <= 60 => 'd60',
            $days <= 90 => 'd90',
            default => 'd90plus',
        };

        return ['owed' => $owed, 'due' => $due, 'days' => $days, 'bucket' => $bucket];
    }
}
