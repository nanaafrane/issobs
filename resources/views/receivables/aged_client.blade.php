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
    </style>
    @endsection

    @section('content')
    @php
        $b = \App\Services\Receipts\ReceivablesAging::BUCKETS;
        $t = $report['totals'];
        $name = $client->business_name ?: $client->name;
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <a href="{{ route('receivables.aged') }}" class="small"><i class="bx bx-arrow-back"></i> Aged receivables</a>
                <h3 class="text-primary mb-1 mt-1">{{ $name }}</h3>
                <div class="text-muted small">
                    @if($client->business_name && $client->name !== $client->business_name){{ $client->name }} · @endif
                    Invoices still owing · as of {{ $report['as_of']->format('d M Y') }}
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ url('clientStatement', $client->id) }}" class="btn btn-outline-secondary btn-sm">Full statement</a>
                @if($report['invoices']->isNotEmpty())
                <a href="{{ route('receipt.multi.create', ['client_id' => $client->id]) }}" class="btn btn-primary btn-sm">New receipt</a>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table id="owingTable" class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Month</th>
                            <th>Due</th>
                            <th class="text-end">Days overdue</th>
                            <th class="text-end">Invoice total</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Owed</th>
                            <th>Age</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($report['invoices'] as $inv)
                        @php $old = in_array($inv->bucket, ['d90', 'd90plus']); @endphp
                        <tr>
                            <td><a href="{{ url('invoice/' . $inv->id) }}">FWSSi{{ $inv->id }}</a></td>
                            <td data-order="{{ $inv->invoice_month?->format('Y-m-d') }}">{{ $inv->invoice_month?->format('M Y') }}</td>
                            <td data-order="{{ $inv->due->format('Y-m-d') }}">{{ $inv->due->format('d M Y') }}</td>
                            <td class="ag-num {{ $old ? 'ag-hot' : '' }}" data-order="{{ $inv->days_overdue }}">{{ $inv->days_overdue ?: '' }}</td>
                            <td class="ag-num" data-order="{{ $inv->total }}">{{ number_format($inv->total, 2) }}</td>
                            <td class="ag-num" data-order="{{ $inv->paid }}">{{ $inv->paid > 0 ? number_format($inv->paid, 2) : '' }}</td>
                            <td class="ag-num fw-bold {{ $old ? 'ag-hot' : '' }}" data-order="{{ $inv->owed }}">{{ number_format($inv->owed, 2) }}</td>
                            <td>{{ $b[$inv->bucket] }}</td>
                            <td class="text-end"><a href="{{ route('receipt.multi.create', ['client_id' => $client->id, 'invoice' => $inv->id]) }}" class="btn btn-xs btn-sm btn-outline-primary py-0">Receive payment</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="6">Total owed ({{ $report['invoices']->count() }} {{ \Illuminate\Support\Str::plural('invoice', $report['invoices']->count()) }})</td>
                            <td class="ag-num">{{ number_format($t['owed'], 2) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if($report['invoices']->isEmpty())
            <p class="text-muted mt-3">{{ $name }} owes nothing at the moment.</p>
        @endif

        <div class="row g-3 mt-1">
            <div class="col-md-7">
                <div class="card"><div class="card-body py-3">
                    <div class="small text-muted mb-1">Owed by age</div>
                    <div class="d-flex flex-wrap gap-4">
                        @foreach($b as $k => $label)
                            <div><div class="small text-muted">{{ $label }}</div><div class="ag-num text-start {{ in_array($k, ['d90', 'd90plus']) && $t[$k] > 0 ? 'ag-hot' : '' }}">{{ number_format($t[$k], 2) }}</div></div>
                        @endforeach
                    </div>
                </div></div>
            </div>
            <div class="col-md-5">
                <div class="card"><div class="card-body py-3">
                    <div class="d-flex justify-content-between"><span>Owed</span><span class="ag-num">{{ number_format($t['owed'], 2) }}</span></div>
                    @if($t['credit'] > 0)
                    <div class="d-flex justify-content-between"><span>Unused credit
                        <span class="small text-muted">(@foreach($report['credits'] as $cr)<a href="{{ url('receipt/' . $cr->id) }}">FWSSR{{ $cr->id }}</a>@if(! $loop->last), @endif @endforeach)</span></span>
                        <span class="ag-num">({{ number_format($t['credit'], 2) }})</span></div>
                    @endif
                    <hr class="my-2">
                    <div class="d-flex justify-content-between fw-bold"><span>Net</span><span class="ag-num">GH&#8373; {{ \App\Services\Receipts\ClientStatement::money($t['net']) }}</span></div>
                </div></div>
            </div>
        </div>
        <p class="small text-muted mt-2">Owed = invoice total less everything paid against it. Paid and fully settled invoices are not listed — see the full statement for the complete history.</p>
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
        @if($report['invoices']->isNotEmpty())
        new DataTable('#owingTable', {
            order: [[2, 'asc']],
            paging: false,
            columnDefs: [{ targets: 8, orderable: false }],
            layout: { topStart: { buttons: [{ extend: 'excelHtml5', text: 'Export to Excel', className: 'btn btn-secondary btn-sm', title: @json($name . ' — owing invoices ' . $report['as_of']->format('Y-m-d')), footer: true, exportOptions: { columns: [0,1,2,3,4,5,6,7] } }] } }
        });
        @endif
    </script>
    @endsection
</x-sales-dashboard>
