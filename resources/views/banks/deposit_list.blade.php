<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">
    <style>
        .dp-num { font-variant-numeric: tabular-nums; white-space: nowrap; text-align: right; }
        .dp-tile { border-left: 3px solid var(--bs-primary, #696cff); }
        .dp-tile.momo { border-left-color: var(--bs-warning, #ffab00); }
        .dp-tile.charge { border-left-color: var(--bs-danger, #ff3e1d); }
        tr.dp-reversed td { color: var(--bs-secondary-color, #8592a3); text-decoration: line-through; }
        tr.dp-reversed td.dp-keep { text-decoration: none; }
    </style>
    @endsection

    @section('content')
    @php $gh = fn ($v) => number_format((float) $v, 2); @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
            <div>
                <h3 class="text-primary mb-1"><i class="bx bx-arrow-from-left me-1"></i> Deposits made</h3>
                <div class="text-muted small">One row per bank slip · {{ $month->format('F Y') }}</div>
            </div>
            <div class="d-flex gap-2 align-items-end">
                <form method="GET">
                    <label class="form-label small mb-0" for="month">Month</label>
                    <input type="month" class="form-control form-control-sm" id="month" name="month" value="{{ $month->format('Y-m') }}" max="{{ now()->format('Y-m') }}" onchange="this.form.submit()">
                </form>
                <a href="{{ route('deposit.create') }}" class="btn btn-primary btn-sm">
                    New deposit
                    @if($waitingTotals['count'])<span class="badge bg-white text-primary ms-1">{{ $waitingTotals['count'] }} waiting</span>@endif
                </a>
            </div>
        </div>

        @include('flash-messages')

        <div class="row g-3 mb-3">
            <div class="col-6 col-lg"><div class="card dp-tile"><div class="card-body py-3">
                <div class="small text-muted">Banked this month</div>
                <div class="fs-5 fw-bold">GH&#8373; {{ $gh($summary['banked']) }}</div>
                <div class="small text-muted">{{ $summary['slips'] }} {{ \Illuminate\Support\Str::plural('slip', $summary['slips']) }}</div>
            </div></div></div>
            <div class="col-6 col-lg"><div class="card dp-tile"><div class="card-body py-3">
                <div class="small text-muted">Cash deposited</div>
                <div class="fs-5 fw-bold">GH&#8373; {{ $gh($summary['cash']) }}</div>
            </div></div></div>
            <div class="col-6 col-lg"><div class="card dp-tile momo"><div class="card-body py-3">
                <div class="small text-muted">MoMo withdrawn &amp; deposited</div>
                <div class="fs-5 fw-bold">GH&#8373; {{ $gh($summary['momo']) }}</div>
            </div></div></div>
            <div class="col-6 col-lg"><div class="card dp-tile charge"><div class="card-body py-3">
                <div class="small text-muted">MoMo withdrawal charges</div>
                <div class="fs-5 fw-bold text-danger">GH&#8373; {{ $gh($summary['charges']) }}</div>
                <div class="small text-muted">@if($summary['charge_rate'] !== null){{ $summary['charge_rate'] }}% of MoMo withdrawn @else &nbsp; @endif</div>
            </div></div></div>
            <div class="col-12 col-lg"><div class="card dp-tile"><div class="card-body py-3">
                <div class="small text-muted">Still waiting to deposit</div>
                <div class="fs-6 fw-bold">Cash GH&#8373; {{ $gh($waitingTotals['cash']) }}</div>
                <div class="fs-6 fw-bold">MoMo GH&#8373; {{ $gh($waitingTotals['momo']) }}</div>
            </div></div></div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table id="depositsTable" class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Slip</th>
                            <th>Bank</th>
                            <th class="text-end">Cash</th>
                            <th class="text-end">MoMo</th>
                            <th class="text-end">MoMo charge</th>
                            <th class="text-end">On slip</th>
                            <th>Deposited by</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($deposits as $d)
                        <tr class="{{ $d->reversed_at ? 'dp-reversed' : '' }}">
                            <td data-order="{{ $d->day?->format('Y-m-d') }}-{{ str_pad($d->id, 8, '0', STR_PAD_LEFT) }}">{{ $d->day?->format('d M Y') }}</td>
                            <td>{{ $d->slip_number ?: '#' . $d->id }}</td>
                            <td>{{ $d->bank?->name }}</td>
                            <td class="dp-num">{{ (float) $d->cash_amount + (float) $d->cheque_amount > 0 ? $gh((float) $d->cash_amount + (float) $d->cheque_amount) : '' }}</td>
                            <td class="dp-num">{{ (float) $d->momo_amount > 0 ? $gh($d->momo_amount) : '' }}</td>
                            <td class="dp-num text-danger">{{ (float) $d->momo_charge > 0 ? '(' . $gh($d->momo_charge) . ')' : '' }}</td>
                            <td class="dp-num fw-semibold">{{ $gh($d->total) }}</td>
                            <td>{{ $d->depositor ?: $d->user?->name }}</td>
                            <td class="dp-keep text-end">
                                @if($d->reversed_at)<span class="badge bg-label-secondary me-1">reversed</span>@endif
                                <a href="{{ route('deposit.show', $d) }}" class="btn btn-sm btn-outline-primary py-0">View</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="3">Total (excluding reversed)</td>
                            <td class="dp-num">{{ $gh($summary['cash']) }}</td>
                            <td class="dp-num">{{ $gh($summary['momo']) }}</td>
                            <td class="dp-num text-danger">({{ $gh($summary['charges']) }})</td>
                            <td class="dp-num">{{ $gh($summary['banked']) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @if($deposits->isEmpty())<div class="card-body text-center text-muted">No deposits in {{ $month->format('F Y') }}.</div>@endif
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
        @if($deposits->isNotEmpty())
        new DataTable('#depositsTable', {
            order: [[0, 'desc']], pageLength: 50,
            columnDefs: [{ targets: 8, orderable: false }],
            layout: { topStart: { buttons: [{ extend: 'excelHtml5', text: 'Export to Excel', className: 'btn btn-secondary btn-sm', title: 'Deposits {{ $month->format('Y-m') }}', footer: true, exportOptions: { columns: [0,1,2,3,4,5,6,7] } }] } }
        });
        @endif
    </script>
    @endsection
</x-sales-dashboard>
