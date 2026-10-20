<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">
    <style>
        .mv-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        .mv-tile { border-left: 3px solid var(--bs-primary, #696cff); }
        .mv-tile.warn { border-left-color: var(--bs-warning, #ffab00); }
        tr.mv-cancelled td { color: #8592a3; text-decoration: line-through; }
        tr.mv-cancelled td.mv-keep { text-decoration: none; }
    </style>
    @endsection

    @section('content')
    @php
        $statusBadge = ['paid' => 'bg-label-success', 'issued' => 'bg-label-warning', 'cleared' => 'bg-label-success', 'cancelled' => 'bg-label-secondary'];
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div>
                <h3 class="text-primary mb-1"><i class="bx bx-money-withdraw me-1"></i> Payments</h3>
                <div class="text-muted small">Money paid out of our bank and cash accounts · {{ $status === 'issued' ? 'all uncleared cheques' : $month->format('F Y') }}</div>
            </div>
            <a href="{{ route('money.payments.create') }}" class="btn btn-primary btn-sm">Make a payment</a>
        </div>

        @include('flash-messages')

        <form method="GET" class="d-flex flex-wrap gap-2 align-items-end mb-3">
            <div><label class="form-label small mb-0" for="month">Month</label><input type="month" id="month" name="month" class="form-control form-control-sm" value="{{ $month->format('Y-m') }}" max="{{ now()->format('Y-m') }}"></div>
            <div><label class="form-label small mb-0" for="bank">Account</label>
                <select id="bank" name="bank" class="form-select form-select-sm"><option value="">All accounts</option>
                    @foreach($banks as $b)<option value="{{ $b->id }}" @selected($bankId === $b->id)>{{ $b->name }}</option>@endforeach</select></div>
            <div><label class="form-label small mb-0" for="category">What for</label>
                <select id="category" name="category" class="form-select form-select-sm"><option value="">All</option>
                    @foreach(\App\Models\AccountPayment::CATEGORIES as $k => $v)<option value="{{ $k }}" @selected($category === $k)>{{ $v }}</option>@endforeach</select></div>
            <div><label class="form-label small mb-0" for="status">Status</label>
                <select id="status" name="status" class="form-select form-select-sm"><option value="">All</option>
                    @foreach(\App\Models\AccountPayment::STATUSES as $k => $v)<option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>@endforeach</select></div>
            <button class="btn btn-sm btn-primary">Show</button>
        </form>

        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3"><div class="card mv-tile"><div class="card-body py-3">
                <div class="small text-muted">Paid out</div><div class="fs-5 fw-bold">GH₵ {{ number_format($live->sum('amount'), 2) }}</div>
                <div class="small text-muted">{{ $live->count() }} {{ \Illuminate\Support\Str::plural('payment', $live->count()) }}</div>
            </div></div></div>
            <div class="col-6 col-lg-3"><div class="card mv-tile"><div class="card-body py-3">
                <div class="small text-muted">From cash</div><div class="fs-5 fw-bold">GH₵ {{ number_format($live->where('method', 'cash')->sum('amount'), 2) }}</div>
                <div class="small text-muted">by cheque / transfer GH₵ {{ number_format($live->where('method', '!=', 'cash')->sum('amount'), 2) }}</div>
            </div></div></div>
            <div class="col-6 col-lg-3"><div class="card mv-tile warn"><div class="card-body py-3">
                <div class="small text-muted">Cheques not yet cleared</div><div class="fs-5 fw-bold">GH₵ {{ number_format($uncleared->sum('amount'), 2) }}</div>
                <div class="small text-muted">{{ $uncleared->count() }} cheques · <a href="{{ route('money.payments', ['status' => 'issued']) }}">list</a></div>
            </div></div></div>
            <div class="col-6 col-lg-3"><div class="card mv-tile warn"><div class="card-body py-3">
                <div class="small text-muted">Approved expenses unpaid</div><div class="fs-5 fw-bold">GH₵ {{ number_format($awaiting->sum('amount'), 2) }}</div>
                <div class="small text-muted">{{ $awaiting->count() }} · <a href="{{ route('money.payments.create', ['for' => 'expenses']) }}">pay them</a></div>
            </div></div></div>
        </div>

        <div class="row g-3">
            <div class="col-lg-9">
                <div class="card">
                    <div class="table-responsive p-2">
                        <table class="table table-sm mb-0 w-100" id="payTable">
                            <thead><tr><th>Date</th><th>Paid to</th><th>What for</th><th>From</th><th>How</th><th class="mv-num">Amount</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                            @foreach($payments as $p)
                                <tr class="{{ $p->status === 'cancelled' ? 'mv-cancelled' : '' }}">
                                    <td data-order="{{ $p->payment_date->format('Ymd') }}{{ str_pad($p->id, 8, '0', STR_PAD_LEFT) }}">{{ $p->payment_date->format('d M Y') }}</td>
                                    <td>{{ $p->payee }}@if($p->description)<div class="small text-muted">{{ \Illuminate\Support\Str::limit($p->description, 50) }}</div>@endif</td>
                                    <td>{{ $p->category_label }}@if($p->expenses_count) <span class="small text-muted">({{ $p->expenses_count }})</span>@endif</td>
                                    <td>{{ $p->bank?->name }}</td>
                                    <td>{{ \App\Models\AccountPayment::METHODS[$p->method] ?? $p->method }}@if($p->reference)<div class="small text-muted">{{ $p->reference }}</div>@endif</td>
                                    <td class="mv-num">{{ number_format($p->amount, 2) }}</td>
                                    <td class="mv-keep"><span class="badge {{ $statusBadge[$p->status] ?? 'bg-label-secondary' }}">{{ \App\Models\AccountPayment::STATUSES[$p->status] ?? $p->status }}</span></td>
                                    <td class="mv-keep text-end"><a href="{{ route('money.payments.show', $p) }}" class="btn btn-sm btn-outline-primary py-0">View</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot><tr class="fw-bold"><td colspan="5">Total (excluding cancelled)</td><td class="mv-num">{{ number_format($live->sum('amount'), 2) }}</td><td colspan="2"></td></tr></tfoot>
                        </table>
                    </div>
                    @if($payments->isEmpty())<div class="card-body pt-0 text-center text-muted">No payments for this selection.</div>@endif
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card"><div class="card-body">
                    <h6>By what for</h6>
                    @forelse($byCategory as $c)
                        <div class="d-flex justify-content-between small"><span>{{ $c['label'] }} <span class="text-muted">· {{ $c['count'] }}</span></span><span class="mv-num">{{ number_format($c['amount'], 2) }}</span></div>
                    @empty
                        <div class="small text-muted">Nothing paid.</div>
                    @endforelse
                </div></div>
            </div>
        </div>
    </div>
    @endsection

    @section('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.dataTables.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.html5.min.js"></script>
    <script>
        if (window.DataTable && document.querySelector('#payTable tbody tr')) new DataTable('#payTable', {
            order: [[0, 'desc']], pageLength: 50, columnDefs: [{ targets: 7, orderable: false }],
            layout: { topStart: { buttons: [{ extend: 'excelHtml5', text: 'Export to Excel', className: 'btn btn-secondary btn-sm', title: 'Payments {{ $month->format('Y-m') }}', footer: true, exportOptions: { columns: [0,1,2,3,4,5,6] } }] } }
        });
    </script>
    @endsection
</x-sales-dashboard>
