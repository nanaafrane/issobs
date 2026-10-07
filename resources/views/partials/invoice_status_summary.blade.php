{{--
    Client invoice status summary for a payroll screen (bank / cash month).
    $rows           : salaries shown in the table
    $clientPayments : client_id => ['status' => ..., 'record' => ...]  (SalaryController::clientPayments)
    $month          : the payroll month
    Chips filter the table's CLIENT INVOICE column (see partials.invoice_status_script).
--}}
@php
    use App\Support\ClientInvoiceStatus as InvStatus;
    $groups = [
        'inv-paid'      => ['Client paid', 'success'],
        'inv-paid_late' => ['Client paid late', 'info'],
        'inv-part'      => ['Client part paid', 'warning'],
        'inv-overdue'   => ['Client unpaid, overdue', 'danger'],
        'inv-unpaid'    => ['Client unpaid, not due yet', 'secondary'],
        'inv-none'      => ['No invoice', 'dark'],
        'inv-noclient'  => ['No client on salary', 'dark'],
    ];
    $count = array_fill_keys(array_keys($groups), ['staff' => 0, 'net' => 0.0, 'clients' => []]);
    foreach ($rows as $row) {
        $p = $clientPayments[$row->client_id] ?? null;
        $key = $p ? InvStatus::searchKey($p['status']) : 'inv-noclient';
        $count[$key]['staff']++;
        $count[$key]['net'] += (float) $row->net_salary;
        if ($row->client_id) {
            $count[$key]['clients'][$row->client_id] = true;
        }
    }
    $notPaid = collect(['inv-part', 'inv-overdue', 'inv-unpaid', 'inv-none'])->sum(fn ($k) => $count[$k]['staff']);
    $notPaidNet = collect(['inv-part', 'inv-overdue', 'inv-unpaid', 'inv-none'])->sum(fn ($k) => $count[$k]['net']);
@endphp
@if($rows->isNotEmpty())
<div class="card mb-3">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="fw-semibold me-1">Client invoices for {{ \Carbon\Carbon::parse($month)->format('F Y') }}:</span>
            @foreach($groups as $key => [$label, $color])
                @if($count[$key]['staff'])
                    <button type="button" class="btn btn-sm btn-outline-{{ $color }} js-inv-chip" data-inv="{{ $key }}" aria-pressed="false">
                        {{ $label }}: <strong>{{ $count[$key]['staff'] }}</strong> staff
                        @if(count($count[$key]['clients'])) / {{ count($count[$key]['clients']) }} {{ \Illuminate\Support\Str::plural('client', count($count[$key]['clients'])) }} @endif
                        (GH&#x20B5; {{ number_format($count[$key]['net'], 2) }})
                    </button>
                @endif
            @endforeach
            <button type="button" class="btn btn-sm btn-link js-inv-chip-clear d-none">Show everyone</button>
        </div>
        @if($notPaid)
            <div class="small text-muted mt-2">
                {{ $notPaid }} {{ \Illuminate\Support\Str::plural('salary', $notPaid) }} (GH&#x20B5; {{ number_format($notPaidNet, 2) }})
                {{ $notPaid === 1 ? 'is' : 'are' }} for clients whose {{ \Carbon\Carbon::parse($month)->format('F') }} invoice is not fully paid.
                Paid on time means within {{ InvStatus::GRACE_DAYS }} days of the invoice due date; hover a status for the dates.
            </div>
        @endif
    </div>
</div>
@endif
