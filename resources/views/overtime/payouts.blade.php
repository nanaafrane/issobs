<x-sales-dashboard>

    @section('side_nav')
        @include('partials.payroll_side_nav')
    @endsection

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <style>
        .op-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        .op-tile { border-left: 3px solid var(--bs-primary, #696cff); }
        .op-tile.warn { border-left-color: var(--bs-warning, #ffab00); }
        .op-tile.held { border-left-color: var(--bs-danger, #ff3e1d); }
    </style>
    @endsection

    @section('content')
    @php $badge = ['open' => 'bg-label-warning', 'closed' => 'bg-label-success', 'cancelled' => 'bg-label-secondary']; @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div>
                <h3 class="text-primary mb-1"><i class="bx bx-money-withdraw me-1"></i> Overtime pay-outs</h3>
                <div class="text-muted small">Approved overtime is paid per office and period — one expense and one payment — and the office ticks each guard as paid.</div>
            </div>
            <div class="d-flex gap-2">
                @if($canFund)
                <a href="{{ route('overtime.payouts.payrollCheck') }}" class="btn btn-outline-secondary btn-sm">Payroll check</a>
                <a href="{{ route('overtime.payouts.create') }}" class="btn btn-primary btn-sm">New pay-out</a>
                @endif
            </div>
        </div>

        @include('flash-messages')

        <div class="row g-3 mb-3">
            <div class="col-md-4"><div class="card op-tile warn"><div class="card-body py-3">
                <div class="small text-muted">Overtime owed (approved, not yet paid out)</div>
                <div class="fs-5 fw-bold">GH₵ {{ number_format($owed->sum('amount'), 2) }}</div>
                <div class="small text-muted">{{ $owed->count() }} {{ \Illuminate\Support\Str::plural('entry', $owed->count()) }} · {{ $owedByOffice->count() }} {{ \Illuminate\Support\Str::plural('office', $owedByOffice->count()) }}</div>
            </div></div></div>
            <div class="col-md-4"><div class="card op-tile held"><div class="card-body py-3">
                <div class="small text-muted">Cash held by officers, not yet paid to guards</div>
                <div class="fs-5 fw-bold">GH₵ {{ number_format($held->sum('unpaid_amount'), 2) }}</div>
                <div class="small text-muted">{{ $held->count() }} open cash {{ \Illuminate\Support\Str::plural('pay-out', $held->count()) }}</div>
            </div></div></div>
            <div class="col-md-4"><div class="card op-tile"><div class="card-body py-3">
                <div class="small text-muted">Paid out (open and closed pay-outs listed)</div>
                <div class="fs-5 fw-bold">GH₵ {{ number_format($payouts->where('status', '!=', 'cancelled')->sum(fn ($p) => (float) $p->total - (float) $p->returned), 2) }}</div>
                <div class="small text-muted">after money returned</div>
            </div></div></div>
        </div>

        <div class="card mb-3">
            <div class="card-header py-2"><h6 class="mb-0">Owed by office</h6></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Office</th><th class="op-num">Entries</th><th>Dates</th><th class="op-num">Amount</th><th></th></tr></thead>
                    <tbody>
                    @forelse($owedByOffice as $o)
                        <tr>
                            <td>{{ $o['field']?->name }}</td>
                            <td class="op-num">{{ $o['count'] }}</td>
                            <td>{{ $o['from']?->format('d M') }} – {{ $o['to']?->format('d M Y') }}</td>
                            <td class="op-num fw-semibold">{{ number_format($o['amount'], 2) }}</td>
                            <td class="text-end">@if($canFund)<a class="btn btn-sm btn-outline-primary py-0" href="{{ route('overtime.payouts.create', ['field' => $o['field']?->id, 'from' => $o['from']?->toDateString(), 'to' => $o['to']?->toDateString()]) }}">Pay these</a>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted text-center py-3">No approved overtime is waiting to be paid.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Pay-outs</h6>
                <form method="GET" class="d-flex gap-2">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All</option>
                        @foreach(['open' => 'Open (being paid)', 'closed' => 'Closed', 'cancelled' => 'Cancelled'] as $k => $v)<option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>@endforeach
                    </select>
                </form>
            </div>
            <div class="table-responsive p-2">
                <table class="table table-sm mb-0 w-100" id="poTable">
                    <thead><tr><th>#</th><th>Office</th><th>Period</th><th>Paid how</th><th class="op-num">Total</th><th class="op-num">Guards paid</th><th class="op-num">Still to pay</th><th class="op-num">Returned</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @foreach($payouts as $p)
                        <tr>
                            <td>{{ $p->id }}</td>
                            <td>{{ $p->field?->name }}</td>
                            <td data-order="{{ $p->period_from?->format('Ymd') }}">{{ $p->period_from?->format('d M') }} – {{ $p->period_to?->format('d M Y') }}</td>
                            <td>{{ \App\Models\OvertimePayout::METHODS[$p->method] ?? $p->method }}@if($p->held_by)<div class="small text-muted">{{ $p->held_by }}</div>@endif</td>
                            <td class="op-num">{{ number_format($p->total, 2) }}</td>
                            <td class="op-num">{{ $p->paid_count }} / {{ $p->entries_count }}</td>
                            <td class="op-num {{ (float) $p->unpaid_amount > 0 && $p->status === 'open' ? 'text-danger fw-semibold' : '' }}">{{ $p->status === 'open' && (float) $p->unpaid_amount > 0 ? number_format($p->unpaid_amount, 2) : '' }}</td>
                            <td class="op-num">{{ (float) $p->returned > 0 ? number_format($p->returned, 2) : '' }}</td>
                            <td><span class="badge {{ $badge[$p->status] ?? 'bg-label-secondary' }}">{{ $p->status }}</span></td>
                            <td class="text-end"><a href="{{ route('overtime.payouts.show', $p) }}" class="btn btn-sm btn-outline-primary py-0">Open</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($payouts->isEmpty())<div class="card-body pt-0 text-center text-muted">No pay-outs yet.</div>@endif
        </div>
    </div>
    @endsection

    @section('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.js"></script>
    <script>if (window.DataTable && document.querySelector('#poTable tbody tr')) new DataTable('#poTable', { order: [[0, 'desc']], pageLength: 25, columnDefs: [{ targets: 9, orderable: false }] });</script>
    @endsection
</x-sales-dashboard>
