<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <style>
        .mv-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        tr.mv-reversed td { color: #8592a3; text-decoration: line-through; }
        tr.mv-reversed td.mv-keep { text-decoration: none; }
    </style>
    @endsection

    @section('content')
    @php $live = $transfers->whereNull('reversed_at'); @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div>
                <h3 class="text-primary mb-1"><i class="bx bx-transfer me-1"></i> Transfers between our accounts</h3>
                <div class="text-muted small">Cheques and withdrawals that fund Main Cash, moves between banks, cash paid back in · {{ $month->format('F Y') }}</div>
            </div>
            <div class="d-flex gap-2 align-items-end">
                <form method="GET"><label class="form-label small mb-0" for="month">Month</label>
                    <input type="month" class="form-control form-control-sm" id="month" name="month" value="{{ $month->format('Y-m') }}" max="{{ now()->format('Y-m') }}" onchange="this.form.submit()"></form>
                <a href="{{ route('money.transfers.create') }}" class="btn btn-primary btn-sm">Move money</a>
            </div>
        </div>
        @include('flash-messages')

        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm mb-0" id="trTable">
                    <thead><tr><th>Date</th><th>Out of</th><th>Into</th><th>How</th><th class="mv-num">Amount</th><th>Notes</th><th>By</th><th></th></tr></thead>
                    <tbody>
                    @foreach($moves as $mv)
                        @php $t = $mv['first']; @endphp
                        <tr class="{{ $mv['reversed'] ? 'mv-reversed' : '' }}">
                            <td data-order="{{ $t->transfer_date->format('Ymd') }}{{ str_pad($t->id, 8, '0', STR_PAD_LEFT) }}">{{ $t->transfer_date->format('d M Y') }}</td>
                            <td>@foreach($mv['outs'] as $o)<div>{{ $o['bank']?->name }}@if($mv['outs']->count() > 1) <span class="small text-muted">{{ number_format($o['amount'], 2) }}</span>@endif</div>@endforeach</td>
                            <td>@foreach($mv['ins'] as $i)<div>{{ $i['bank']?->name }}@if($mv['ins']->count() > 1) <span class="small text-muted">{{ number_format($i['amount'], 2) }}</span>@endif</div>@endforeach</td>
                            <td class="small">{{ $mv['how'] }}</td>
                            <td class="mv-num">{{ number_format($mv['total'], 2) }}</td>
                            <td class="small">{{ $t->notes }}</td>
                            <td class="small">{{ $t->user?->name }}</td>
                            <td class="mv-keep text-end">@if($mv['reversed'])<span class="badge bg-label-secondary me-1">reversed</span>@endif<a href="{{ route('money.transfers.show', $t) }}" class="btn btn-sm btn-outline-primary py-0">View</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot><tr class="fw-bold"><td colspan="4">Total moved (excluding reversed)</td><td class="mv-num">{{ number_format($live->sum('amount'), 2) }}</td><td colspan="3"></td></tr></tfoot>
                </table>
            </div>
            @if($transfers->isEmpty())<div class="card-body text-center text-muted">No transfers in {{ $month->format('F Y') }}.</div>@endif
        </div>

        @if($live->isNotEmpty())
        <div class="row g-3 mt-1">
            <div class="col-md-6"><div class="card"><div class="card-body">
                <h6>Into each account</h6>
                @foreach($live->groupBy('to_bank_id') as $g)
                    <div class="d-flex justify-content-between small"><span>{{ $g->first()->to?->name }}</span><span class="mv-num">{{ number_format($g->sum('amount'), 2) }}</span></div>
                @endforeach
            </div></div></div>
            <div class="col-md-6"><div class="card"><div class="card-body">
                <h6>Out of each account</h6>
                @foreach($live->groupBy('from_bank_id') as $g)
                    <div class="d-flex justify-content-between small"><span>{{ $g->first()->from?->name }}</span><span class="mv-num">{{ number_format($g->sum('amount'), 2) }}</span></div>
                @endforeach
            </div></div></div>
        </div>
        @endif
    </div>
    @endsection

    @section('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.js"></script>
    <script>if (window.DataTable && document.querySelector('#trTable tbody tr')) new DataTable('#trTable', { order: [[0, 'desc']], pageLength: 50, columnDefs: [{ targets: 7, orderable: false }] });</script>
    @endsection
</x-sales-dashboard>
