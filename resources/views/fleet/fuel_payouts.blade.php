<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <style>.fl-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; } .fl-tile { border-left: 3px solid var(--bs-warning, #ffab00); }</style>
    @endsection

    @section('content')
    @php $m = fn ($v) => number_format((float) $v, 2); @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-2">
            <div>
                <h3 class="text-primary mb-1"><i class="bx bx-money-withdraw me-1"></i> Fuel pay-outs</h3>
                <div class="text-muted small">Approved fuel is paid per office and period (or per station bill) in one go: one expense under <strong>Fuel</strong> and one payment, so the cash book shows one line.</div>
            </div>
            @if($canFund)<a href="{{ route('fleet.fuel.payouts.create') }}" class="btn btn-primary btn-sm">New pay-out</a>@endif
        </div>
        @include('fleet._tabs', ['active' => 'payouts'])
        @include('flash-messages')

        <div class="row g-3 mb-3">
            <div class="col-md-4"><div class="card fl-tile"><div class="card-body py-3">
                <div class="small text-muted">Fuel owed (approved, not yet paid)</div>
                <div class="fs-5 fw-bold">GH₵ {{ $m($owed->sum('amount')) }}</div>
                <div class="small text-muted">{{ $owed->count() }} fill-ups · {{ $m($owed->sum('litres')) }} L</div>
            </div></div></div>
        </div>

        <div class="card mb-3">
            <div class="card-header py-2"><h6 class="mb-0">Owed by office and station</h6></div>
            <div class="table-responsive"><table class="table table-sm mb-0">
                <thead><tr><th>Office</th><th>Station</th><th class="fl-num">Fill-ups</th><th class="fl-num">Litres</th><th>Dates</th><th class="fl-num">Amount</th><th></th></tr></thead>
                <tbody>
                @forelse($owedByOffice as $o)
                    <tr>
                        <td>{{ $o['field']?->name }}</td>
                        <td>{{ $o['station'] ?: '—' }}</td>
                        <td class="fl-num">{{ $o['count'] }}</td>
                        <td class="fl-num">{{ $m($o['litres']) }}</td>
                        <td>{{ $o['from']?->format('d M') }} – {{ $o['to']?->format('d M Y') }}</td>
                        <td class="fl-num fw-semibold">{{ $m($o['amount']) }}</td>
                        <td class="text-end">@if($canFund)<a class="btn btn-sm btn-outline-primary py-0" href="{{ route('fleet.fuel.payouts.create', ['field' => $o['field']?->id, 'station' => $o['station'], 'from' => $o['from']?->toDateString(), 'to' => $o['to']?->toDateString()]) }}">Pay these</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted text-center py-3">No approved fuel is waiting to be paid.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>

        <div class="card">
            <div class="card-header py-2"><h6 class="mb-0">Pay-outs</h6></div>
            <div class="table-responsive"><table class="table table-sm mb-0">
                <thead><tr><th>#</th><th>Office</th><th>Period</th><th>Paid to</th><th>From</th><th class="fl-num">Fill-ups</th><th class="fl-num">Litres</th><th class="fl-num">Total</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($payouts as $p)
                    <tr @class(['text-muted' => $p->status === 'cancelled'])>
                        <td>{{ $p->id }}</td>
                        <td>{{ $p->field?->name }}</td>
                        <td>{{ $p->period_from?->format('d M') }} – {{ $p->period_to?->format('d M Y') }}</td>
                        <td>{{ $p->payee }}</td>
                        <td class="small">{{ $p->bank?->name }}</td>
                        <td class="fl-num">{{ $p->entries_count }}</td>
                        <td class="fl-num">{{ $m($p->litres) }}</td>
                        <td class="fl-num">{{ $m($p->total) }}</td>
                        <td><span class="badge {{ $p->status === 'cancelled' ? 'bg-label-secondary' : 'bg-label-success' }}">{{ $p->status }}</span></td>
                        <td class="text-end"><a href="{{ route('fleet.fuel.payouts.show', $p) }}" class="btn btn-sm btn-outline-primary py-0">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-muted text-center py-3">No pay-outs yet.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
    </div>
    @endsection
</x-sales-dashboard>
