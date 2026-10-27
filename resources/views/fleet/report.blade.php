<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">
    <style>
        .fl-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        table.fl-table td, table.fl-table th { font-size: .8rem; }
        @media print { #layout-menu, .layout-navbar, .no-print, footer, .dt-buttons, .dt-search { display: none !important; } }
    </style>
    @endsection

    @section('content')
    @php
        $m = fn ($v) => (float) $v ? number_format((float) $v, 2) : '';
        $t = $report['totals'];
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="mb-2">
            <h3 class="text-primary mb-1"><i class="bx bx-bar-chart-alt-2 me-1"></i> Fleet cost {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</h3>
            <div class="text-muted small">Per vehicle: fuel drawn and maintenance done in the period (by the day it happened, approved or waiting; rejected left out). km per litre uses the km readings at the pump. For what was <em>paid</em> out of each account, see the <a href="{{ route('money.cashbook') }}">cash book</a>.</div>
        </div>
        @include('fleet._tabs', ['active' => 'report'])

        <form method="GET" class="d-flex flex-wrap align-items-end gap-2 mb-3 no-print">
            <div><label class="form-label small mb-0">Month</label><input type="month" name="month" class="form-control form-control-sm" value="{{ $from->format('Y-m') }}"></div>
            <div><label class="form-label small mb-0">Office</label><select name="field_id" class="form-select form-select-sm"><option value="">All</option>@foreach($fields as $fl)<option value="{{ $fl->id }}" @selected($fieldId === $fl->id)>{{ $fl->name }}</option>@endforeach</select></div>
            <button class="btn btn-sm btn-primary">Show</button>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('fleet.report', ['from' => now()->startOfYear()->toDateString(), 'to' => now()->toDateString(), 'field_id' => $fieldId]) }}">This year</a>
        </form>

        <div class="row g-3 mb-3">
            <div class="col-md-3 col-6"><div class="card"><div class="card-body py-2"><div class="small text-muted">Fuel ({{ number_format($t['litres'], 2) }} L)</div><div class="fs-5 fw-bold">GH₵ {{ number_format($t['fuel'], 2) }}</div></div></div></div>
            <div class="col-md-3 col-6"><div class="card"><div class="card-body py-2"><div class="small text-muted">Maintenance</div><div class="fs-5 fw-bold">GH₵ {{ number_format($t['maintenance'], 2) }}</div></div></div></div>
            <div class="col-md-3 col-6"><div class="card"><div class="card-body py-2"><div class="small text-muted">Total fleet cost</div><div class="fs-5 fw-bold">GH₵ {{ number_format($t['total'], 2) }}</div></div></div></div>
            <div class="col-md-3 col-6"><div class="card"><div class="card-body py-2"><div class="small text-muted">Vehicles</div><div class="fs-5 fw-bold">{{ $report['rows']->count() }}</div></div></div></div>
        </div>

        <div class="card"><div class="table-responsive p-2">
            <table class="table table-sm fl-table mb-0 w-100" id="frTable">
                <thead><tr>
                    <th>Vehicle</th><th>Office</th><th class="fl-num">Fill-ups</th><th class="fl-num">Litres</th><th class="fl-num">Fuel</th>
                    @foreach($report['types'] as $type)<th class="fl-num">{{ $type }}</th>@endforeach
                    <th class="fl-num">Maintenance</th><th class="fl-num">TOTAL</th><th class="fl-num">km</th><th class="fl-num">km / L</th><th class="fl-num">GH₵ / km</th>
                </tr></thead>
                <tbody>
                @foreach($report['rows'] as $r)
                    <tr>
                        <td class="text-nowrap"><a href="{{ route('fleet.vehicles.show', ['vehicle' => $r['vehicle']->id, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">{{ $r['vehicle']->reg_no }}</a> <span class="text-muted">{{ $r['vehicle']->name }}</span></td>
                        <td>{{ $r['vehicle']->field?->name }}</td>
                        <td class="fl-num">{{ $r['fills'] ?: '' }}</td>
                        <td class="fl-num">{{ $m($r['litres']) }}</td>
                        <td class="fl-num">{{ $m($r['fuel']) }}@if($r['fuel_pending'] > 0)<span class="text-warning" title="GH₵ {{ number_format($r['fuel_pending'], 2) }} not yet approved">*</span>@endif</td>
                        @foreach($report['types'] as $type)<td class="fl-num">{{ $m($r['by_type'][$type] ?? 0) }}</td>@endforeach
                        <td class="fl-num">{{ $m($r['maintenance']) }}</td>
                        <td class="fl-num fw-semibold">{{ $m($r['total']) }}</td>
                        <td class="fl-num">{{ $r['km'] ? number_format($r['km']) : '' }}</td>
                        <td class="fl-num">{{ $r['km_per_litre'] ?? '' }}</td>
                        <td class="fl-num">{{ $r['cost_per_km'] ?? '' }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot><tr class="fw-bold">
                    <td>TOTAL</td><td></td><td></td><td class="fl-num">{{ $m($t['litres']) }}</td><td class="fl-num">{{ $m($t['fuel']) }}</td>
                    @foreach($report['types'] as $type)<td class="fl-num">{{ $m($t['by_type'][$type] ?? 0) }}</td>@endforeach
                    <td class="fl-num">{{ $m($t['maintenance']) }}</td><td class="fl-num">{{ $m($t['total']) }}</td><td></td><td></td><td></td>
                </tr></tfoot>
            </table>
            @if($report['rows']->isEmpty())<div class="text-center text-muted py-3">No vehicles or costs for this period.</div>@endif
        </div></div>
        <div class="small text-muted mt-2">* includes fuel still waiting for approval.</div>
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
        if (window.DataTable && document.querySelector('#frTable tbody tr')) try {
            new DataTable('#frTable', { paging: false, info: false, order: [[{{ 6 + $report['types']->count() }}, 'desc']],
                layout: { topStart: { buttons: [{ extend: 'excelHtml5', text: 'Export to Excel', className: 'btn btn-secondary btn-sm', footer: true, title: @json('Fleet cost ' . $from->format('d M Y') . ' - ' . $to->format('d M Y')) }] } } });
        } catch (e) { console.warn(e); }
    </script>
    @endsection
</x-sales-dashboard>
