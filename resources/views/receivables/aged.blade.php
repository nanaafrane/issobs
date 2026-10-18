<x-sales-dashboard>

    @section('side_nav')
        @include('partials.receipt_side_nav')
    @endsection

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">
    <style>
        .ag-num { font-variant-numeric: tabular-nums; white-space: nowrap; text-align: right; }
        .ag-hot { color: var(--bs-danger, #ff3e1d); font-weight: 600; }
        .ag-tile { border-left: 3px solid var(--bs-primary, #696cff); }
        .ag-tile.warn { border-left-color: var(--bs-warning, #ffab00); }
        .ag-tile.bad { border-left-color: var(--bs-danger, #ff3e1d); }
    </style>
    @endsection

    @section('content')
    @php $b = \App\Services\Receipts\ReceivablesAging::BUCKETS; $t = $report['totals']; @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <h3 class="text-primary mb-1"><i class="bx bx-time-five me-1"></i> Aged receivables</h3>
                <div class="text-muted small">What each client still owes, by how long it is overdue · as of {{ $report['as_of']->format('d M Y') }}</div>
            </div>
            @if($fields->count() > 1)
            <form method="GET" class="d-flex gap-2 align-items-end">
                <div>
                    <label class="form-label small mb-0" for="field">Office</label>
                    <select class="form-select form-select-sm" name="field" id="field" onchange="this.form.submit()">
                        <option value="">All offices</option>
                        @foreach($fields as $f)
                            <option value="{{ $f->id }}" {{ (int) $fieldId === $f->id ? 'selected' : '' }}>{{ $f->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
            @endif
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-lg"><div class="card ag-tile"><div class="card-body py-3"><div class="small text-muted">Owed in total</div><div class="fs-5 fw-bold ag-num text-start">{{ number_format($t['owed'], 2) }}</div></div></div></div>
            <div class="col-6 col-lg"><div class="card ag-tile"><div class="card-body py-3"><div class="small text-muted">{{ $b['current'] }}</div><div class="fs-5 ag-num text-start">{{ number_format($t['current'], 2) }}</div></div></div></div>
            <div class="col-6 col-lg"><div class="card ag-tile warn"><div class="card-body py-3"><div class="small text-muted">Overdue up to 60 days</div><div class="fs-5 ag-num text-start">{{ number_format($t['d30'] + $t['d60'], 2) }}</div></div></div></div>
            <div class="col-6 col-lg"><div class="card ag-tile bad"><div class="card-body py-3"><div class="small text-muted">Overdue over 60 days</div><div class="fs-5 ag-num text-start ag-hot">{{ number_format($t['d90'] + $t['d90plus'], 2) }}</div></div></div></div>
            <div class="col-6 col-lg"><div class="card ag-tile"><div class="card-body py-3"><div class="small text-muted">Unused client credit</div><div class="fs-5 ag-num text-start">{{ number_format($t['credit'], 2) }}</div></div></div></div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table id="agedTable" class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Office</th>
                            <th class="text-end">Invoices</th>
                            @foreach($b as $label)<th class="text-end">{{ $label }}</th>@endforeach
                            <th class="text-end">Owed</th>
                            <th class="text-end">Credit</th>
                            <th class="text-end">Net</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($report['rows'] as $row)
                        <tr>
                            <td><a href="{{ route('receivables.aged.client', $row['client_id']) }}">{{ $row['client'] }}</a>@if($row['contact'] && $row['contact'] !== $row['client'])<div class="small text-muted">{{ $row['contact'] }}</div>@endif</td>
                            <td>{{ $row['field'] }}</td>
                            <td class="ag-num">{{ $row['invoices'] }}</td>
                            @foreach(array_keys($b) as $k)
                                <td class="ag-num {{ in_array($k, ['d90', 'd90plus']) && $row[$k] > 0 ? 'ag-hot' : '' }}" data-order="{{ $row[$k] }}">{{ $row[$k] > 0 ? number_format($row[$k], 2) : '' }}</td>
                            @endforeach
                            <td class="ag-num" data-order="{{ $row['owed'] }}">{{ number_format($row['owed'], 2) }}</td>
                            <td class="ag-num" data-order="{{ $row['credit'] }}">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '' }}</td>
                            <td class="ag-num fw-bold" data-order="{{ $row['net'] }}">{{ \App\Services\Receipts\ClientStatement::money($row['net']) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="3">Total ({{ $report['rows']->count() }} clients)</td>
                            @foreach(array_keys($b) as $k)<td class="ag-num">{{ number_format($t[$k], 2) }}</td>@endforeach
                            <td class="ag-num">{{ number_format($t['owed'], 2) }}</td>
                            <td class="ag-num">{{ number_format($t['credit'], 2) }}</td>
                            <td class="ag-num">{{ \App\Services\Receipts\ClientStatement::money($t['net']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <p class="small text-muted mt-2">Owed = invoice total less everything paid against it (same figure as the statement of account). Overdue days count from the invoice due date. Click a client to see the invoices they still owe.</p>
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
        new DataTable('#agedTable', {
            order: [[{{ 3 + count($b) + 2 }}, 'desc']],
            pageLength: 50,
            layout: { topStart: { buttons: [{ extend: 'excelHtml5', text: 'Export to Excel', className: 'btn btn-secondary btn-sm', title: 'Aged receivables {{ $report['as_of']->format('Y-m-d') }}', footer: true }] } }
        });
    </script>
    @endsection
</x-sales-dashboard>
