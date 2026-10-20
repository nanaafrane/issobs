<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <style>
        .dp-num { font-variant-numeric: tabular-nums; white-space: nowrap; text-align: right; }
        .dp-line { display: flex; justify-content: space-between; gap: 1rem; }
        .dp-ref { font-size: .75rem; color: var(--bs-secondary-color, #8592a3); }
    </style>
    @endsection

    @section('content')
    @php
        $gh = fn ($v) => number_format((float) $v, 2);
        $cash = (float) $deposit->cash_amount + (float) $deposit->cheque_amount;
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <a href="{{ route('deposit.index', ['month' => $deposit->day?->format('Y-m')]) }}" class="small"><i class="bx bx-arrow-back"></i> Deposits made</a>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3 mt-1">
            <div>
                <h3 class="text-primary mb-1">Deposit {{ $deposit->slip_number ? 'slip ' . $deposit->slip_number : '#' . $deposit->id }}
                    @if($deposit->reversed_at)<span class="badge bg-label-secondary align-middle fs-6">reversed</span>@endif
                </h3>
                <div class="text-muted small">{{ $deposit->bank?->name }}@if($deposit->bank?->acc_number) · {{ $deposit->bank->acc_number }}@endif · {{ $deposit->day?->format('l d M Y') }}</div>
            </div>
            <a href="{{ route('deposit.create') }}" class="btn btn-outline-primary btn-sm">New deposit</a>
        </div>

        @include('flash-messages')
        @if($errors->any())
            <div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif
        @if($deposit->reversed_at)
            <div class="alert alert-secondary">Reversed on {{ $deposit->reversed_at->format('d M Y H:i') }} by {{ $deposit->reversedBy?->name ?? 'user #' . $deposit->reversed_by }}: {{ $deposit->reversal_reason }}</div>
        @endif

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card h-100"><div class="card-body">
                    <h6>The slip</h6>
                    @if($cash > 0)<div class="dp-line"><span>Cash{{ (float) $deposit->cheque_amount > 0 ? ' (incl. old cheque ' . $gh($deposit->cheque_amount) . ')' : '' }}</span><span class="dp-num">{{ $gh($cash) }}</span></div>@endif
                    @if((float) $deposit->momo_amount > 0)
                        <div class="dp-line"><span>MoMo withdrawn</span><span class="dp-num">{{ $gh($deposit->momo_amount) }}</span></div>
                        <div class="dp-line text-danger"><span>MoMo withdrawal charge</span><span class="dp-num">({{ $gh($deposit->momo_charge) }})</span></div>
                    @endif
                    <hr class="my-2">
                    <div class="dp-line fw-bold fs-5"><span>Amount on slip</span><span class="dp-num">GH&#8373; {{ $gh($deposit->total) }}</span></div>
                    <dl class="row small mt-3 mb-0">
                        <dt class="col-5 text-muted fw-normal">Deposited by</dt><dd class="col-7">{{ $deposit->depositor ?: $deposit->user?->name }}</dd>
                        <dt class="col-5 text-muted fw-normal">Recorded by</dt><dd class="col-7">{{ $deposit->user?->name }} · {{ $deposit->created_at?->format('d M Y H:i') }}</dd>
                        @if($deposit->notes)<dt class="col-5 text-muted fw-normal">Notes</dt><dd class="col-7">{{ $deposit->notes }}</dd>@endif
                    </dl>
                </div></div>
            </div>

            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-body pb-0"><h6>On the bank account</h6></div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Recorded</th><th>Details</th><th class="text-end">Money in</th><th class="text-end">Money out</th><th class="text-end">Balance</th></tr></thead>
                            <tbody>
                            @forelse($deposit->bankLines as $line)
                                <tr>
                                    <td class="text-nowrap">{{ $line->created_at?->format('d M Y H:i') }}</td>
                                    <td>
                                        <span class="badge {{ $line->channel === 'momo_charge' ? 'bg-label-danger' : 'bg-label-primary' }} me-1">{{ $line->channel === 'momo_charge' ? 'MoMo charge' : $line->channel }}</span>
                                        {{ $line->narration }}
                                    </td>
                                    <td class="dp-num text-success">{{ $line->credit > 0 ? $gh($line->credit) : '' }}</td>
                                    <td class="dp-num text-danger">{{ $line->debit > 0 ? $gh($line->debit) : '' }}</td>
                                    <td class="dp-num">{{ $gh($line->balance) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">No bank lines.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body pb-0"><h6>Receipts on this slip</h6></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Receipt</th><th>Client</th><th>Office</th><th class="text-end">Cash</th><th class="text-end">MoMo</th></tr></thead>
                    <tbody>
                    @forelse($collections as $c)
                        @php
                            $cashHere = (int) $c->cash_deposit_id === $deposit->id;
                            $momoHere = (int) $c->momo_deposit_id === $deposit->id;
                            $cashAmt = \App\Services\Receipts\BankPosting::depositable($c)['total'];
                        @endphp
                        <tr>
                            <td>@if($c->receipt_id)<a href="{{ url('receipt', $c->receipt_id) }}">FWSSR{{ $c->receipt_id }}</a>@else #{{ $c->id }} @endif</td>
                            <td>{{ $c->client_name }}</td>
                            <td>{{ $c->office }}</td>
                            <td class="dp-num">{{ $cashHere ? $gh($cashAmt) : '' }}</td>
                            <td class="dp-num">{{ $momoHere ? $gh($c->momo_amount) : '' }}@if($momoHere && $c->momo_ref)<div class="dp-ref">ID {{ $c->momo_ref }}</div>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">
                            @if($deposit->reversed_at) The receipts were released when this deposit was reversed and are waiting to be deposited again.
                            @else Deposited before slips were recorded — see the bank line for the collection. @endif
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($canCorrect && ! $deposit->reversed_at && $deposit->gross_amount !== null)
        <div class="card mt-3 border-danger">
            <div class="card-body">
                <h6 class="text-danger mb-1">Reverse this deposit</h6>
                <p class="small text-muted mb-2">Wrong bank, wrong amount or wrong receipts? Reversing posts both bank lines back (nothing is deleted) and puts its cash and MoMo back on Bank Deposit so it can be entered again correctly.</p>
                <form method="POST" action="{{ route('deposit.reverse', $deposit) }}" class="d-flex flex-wrap gap-2" onsubmit="return confirm('Reverse this deposit? Both bank lines will be posted back.');">
                    @csrf
                    {!! \App\Support\SubmissionGuard::field() !!}
                    <input type="text" name="reversal_reason" class="form-control form-control-sm" style="max-width: 28rem" maxlength="300" placeholder="Reason, e.g. posted to the wrong bank" required>
                    <button class="btn btn-sm btn-danger" data-loading>Reverse deposit</button>
                </form>
            </div>
        </div>
        @endif
    </div>
    @endsection
</x-sales-dashboard>
