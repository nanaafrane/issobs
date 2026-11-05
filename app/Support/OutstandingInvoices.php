<?php

namespace App\Support;

use App\Models\ReceiptAllocation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Outstanding invoices for a period: invoices whose invoice month is one of the months the
 * period touches and that still have something owing today.
 *
 * Owed = invoice total − everything settled against it (receipt_allocations.settled: cash
 * applied + tax withheld + deductions) — the same figure as aged receivables and the
 * statement of account, so all three always agree.
 *
 *  - Unpaid:    nothing settled yet            (invoices.status "unpaid")
 *  - Part paid: something settled, balance left (invoices.status "uncompleted")
 *
 * Invoices are monthly, so a week or a date range shows the invoices of the months it touches
 * (as the Accounts dashboard counts "Invoiced"). Overdue days count from the due date (old
 * invoices without one: end of the month after the invoice month), as aged receivables does.
 */
class OutstandingInvoices
{
    public const STATUSES = [
        'unpaid' => 'Unpaid',
        'part' => 'Part paid',
    ];

    public Carbon $monthFrom;  // first day of the first month
    public Carbon $monthTo;    // last day of the last month

    /** @param int[]|null $fieldIds null = every office */
    public function __construct(public ?array $fieldIds, Carbon $from, Carbon $to)
    {
        $this->monthFrom = $from->copy()->startOfMonth()->startOfDay();
        $this->monthTo = $to->copy()->endOfMonth()->startOfDay();
    }

    /** "Oct 2026" or "Jul – Dec 2026": the invoice months covered. */
    public function monthsLabel(): string
    {
        $a = $this->monthFrom;
        $b = $this->monthTo->copy()->startOfMonth();
        if ($a->equalTo($b)) {
            return $a->format('M Y');
        }

        return $a->year === $b->year ? $a->format('M') . ' – ' . $b->format('M Y') : $a->format('M Y') . ' – ' . $b->format('M Y');
    }

    /** Invoices in the months with something still owing, with paid / owed columns. */
    public function query()
    {
        $settled = ReceiptAllocation::selectRaw('invoice_id, SUM(settled) as settled')->groupBy('invoice_id');

        return DB::table('invoices')
            ->join('clients', 'clients.id', '=', 'invoices.client_id')
            ->leftJoin('fields', 'fields.id', '=', 'clients.field_id')
            ->leftJoinSub($settled, 's', 's.invoice_id', '=', 'invoices.id')
            ->whereBetween('invoices.invoice_month', [$this->monthFrom->toDateString(), $this->monthTo->toDateString()])
            ->whereRaw('invoices.total - COALESCE(s.settled, 0) > 0.004')
            ->when($this->fieldIds !== null, fn ($q) => $q->whereIn('clients.field_id', $this->fieldIds ?: [0]));
    }

    /**
     * Every outstanding invoice, oldest invoice month first.
     *
     * @param  string|null  $status  'unpaid' | 'part' | null (both)
     */
    public function rows(?string $status = null, ?int $clientId = null, ?int $officeId = null): Collection
    {
        $today = Carbon::today();

        return $this->filtered($status, $clientId, $officeId)
            ->select([
                'invoices.id', 'invoices.client_id', 'invoices.invoice_month', 'invoices.due_date', 'invoices.total', 'invoices.status',
                'clients.business_name', 'clients.name as contact', 'clients.field_id', 'fields.name as office',
                DB::raw('COALESCE(s.settled, 0) as paid'),
            ])
            ->orderBy('invoices.invoice_month')->orderBy('clients.business_name')->orderBy('invoices.id')
            ->get()
            ->map(function ($r) use ($today) {
                $r->total = round((float) $r->total, 2);
                $r->paid = round((float) $r->paid, 2);
                $r->owed = round($r->total - $r->paid, 2);
                $r->state = $r->paid > 0.004 ? 'part' : 'unpaid';
                $r->client = $r->business_name ?: ($r->contact ?: 'Client #' . $r->client_id);
                $r->invoice_month = $r->invoice_month ? Carbon::parse($r->invoice_month) : null;
                $r->due = $r->due_date
                    ? Carbon::parse($r->due_date)->startOfDay()
                    : ($r->invoice_month ? $r->invoice_month->copy()->addMonthNoOverflow()->endOfMonth()->startOfDay() : null);
                $r->days_overdue = $r->due && $today->gt($r->due) ? (int) $r->due->diffInDays($today) : 0;

                return $r;
            });
    }

    /** Totals for the summary cards. */
    public function summary(): array
    {
        $row = $this->query()->selectRaw('
                COUNT(*) as cnt,
                COUNT(DISTINCT invoices.client_id) as clients_cnt,
                COALESCE(SUM(invoices.total), 0) as billed,
                COALESCE(SUM(invoices.total - COALESCE(s.settled, 0)), 0) as owed,
                SUM(CASE WHEN COALESCE(s.settled, 0) <= 0.004 THEN 1 ELSE 0 END) as unpaid_cnt,
                COALESCE(SUM(CASE WHEN COALESCE(s.settled, 0) <= 0.004 THEN invoices.total - COALESCE(s.settled, 0) END), 0) as unpaid_owed,
                SUM(CASE WHEN COALESCE(s.settled, 0) > 0.004 THEN 1 ELSE 0 END) as part_cnt,
                COALESCE(SUM(CASE WHEN COALESCE(s.settled, 0) > 0.004 THEN invoices.total - COALESCE(s.settled, 0) END), 0) as part_owed
            ')->first();

        return [
            'count' => (int) $row->cnt,
            'clients' => (int) $row->clients_cnt,
            'billed' => round((float) $row->billed, 2),
            'owed' => round((float) $row->owed, 2),
            'unpaid' => ['count' => (int) $row->unpaid_cnt, 'owed' => round((float) $row->unpaid_owed, 2)],
            'part' => ['count' => (int) $row->part_cnt, 'owed' => round((float) $row->part_owed, 2)],
            'months' => $this->monthsLabel(),
        ];
    }

    /** Owed per office, largest first. */
    public function byOffice(): Collection
    {
        return $this->query()
            ->selectRaw("clients.field_id as office_id, COALESCE(fields.name, 'No office') as office_name, COUNT(*) as cnt,
                SUM(invoices.total - COALESCE(s.settled, 0)) as owed")
            ->groupBy('clients.field_id', 'fields.name')->orderByDesc('owed')->get()
            ->map(fn ($r) => ['id' => $r->office_id ? (int) $r->office_id : null, 'name' => $r->office_name, 'count' => (int) $r->cnt, 'owed' => round((float) $r->owed, 2)]);
    }

    private function filtered(?string $status, ?int $clientId, ?int $officeId)
    {
        return $this->query()
            ->when($status === 'unpaid', fn ($q) => $q->whereRaw('COALESCE(s.settled, 0) <= 0.004'))
            ->when($status === 'part', fn ($q) => $q->whereRaw('COALESCE(s.settled, 0) > 0.004'))
            ->when($clientId, fn ($q) => $q->where('invoices.client_id', $clientId))
            ->when($officeId, fn ($q) => $q->where('clients.field_id', $officeId));
    }
}
