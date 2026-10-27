<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">
    <style>
        .fl-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        table.fl-table td { font-size: .82rem; vertical-align: middle; }
        tr.fl-rejected td { opacity: .5; text-decoration: line-through; }
    </style>
    @endsection

    @section('content')
    @php $m = fn ($v) => number_format((float) $v, 2); @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="mb-2">
            <h3 class="text-primary mb-1"><i class="bx bx-wrench me-1"></i> Vehicle maintenance</h3>
            <div class="text-muted small">Roadworthy, insurance, tyres, servicing, repairs… Each job is reviewed by the branch and approved by finance; it then becomes one <strong>approved expense</strong> under its item, paid from <a href="{{ route('money.payments.create') }}">Payments › Pay approved expenses</a>.</div>
        </div>
        @include('fleet._tabs', ['active' => 'maintenance'])
        @include('flash-messages')
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

        <div class="card mb-3"><div class="card-body">
            <h6 class="mb-2">Record maintenance</h6>
            @if($vehicles->isEmpty())
                <div class="text-muted small">Add your vehicles first under <a href="{{ route('fleet.vehicles') }}">Vehicles</a>.</div>
            @else
            <form method="POST" action="{{ route('fleet.maintenance.store') }}" class="row g-2" autocomplete="off">
                @csrf
                @include('fleet._maintenance_fields', ['r' => null])
                <div class="col-md-2 col-12 d-grid align-items-end"><button class="btn btn-primary btn-sm mt-3">Record</button></div>
            </form>
            @endif
        </div></div>

        @if($due->isNotEmpty())
        <div class="card mb-3 border-warning">
            <div class="card-header py-2"><h6 class="mb-0 text-warning"><i class="bx bx-bell"></i> Due, expired or missing ({{ $due->count() }})</h6></div>
            @include('fleet._due_table', ['due' => $due, 'showVehicle' => true])
        </div>
        @endif

        <form method="GET" class="d-flex flex-wrap align-items-end gap-2 mb-3">
            <div><label class="form-label small mb-0">From</label><input type="date" name="from" class="form-control form-control-sm" value="{{ $from->toDateString() }}"></div>
            <div><label class="form-label small mb-0">To</label><input type="date" name="to" class="form-control form-control-sm" value="{{ $to->toDateString() }}"></div>
            <div><label class="form-label small mb-0">Office</label><select name="field_id" class="form-select form-select-sm"><option value="">All</option>@foreach($fields as $fl)<option value="{{ $fl->id }}" @selected($fieldId === $fl->id)>{{ $fl->name }}</option>@endforeach</select></div>
            <div><label class="form-label small mb-0">Vehicle</label><select name="vehicle_id" class="form-select form-select-sm"><option value="">All</option>@foreach($vehicles as $v)<option value="{{ $v->id }}" @selected($vehicleId === $v->id)>{{ $v->reg_no }}</option>@endforeach</select></div>
            <div><label class="form-label small mb-0">Type</label><select name="type_id" class="form-select form-select-sm"><option value="">All</option>@foreach($types as $t)<option value="{{ $t->id }}" @selected($typeId === $t->id)>{{ $t->name }}</option>@endforeach</select></div>
            <button class="btn btn-sm btn-primary">Show</button>
        </form>

        <div class="row g-3">
            <div class="col-xl-9">
                <div class="card"><div class="table-responsive p-2">
                    <table class="table table-sm fl-table mb-0 w-100" id="mtTable">
                        <thead><tr><th>Date</th><th>Vehicle</th><th>Office</th><th>Type</th><th>Vendor / ref</th><th>Details</th><th class="fl-num">km</th><th>Valid until</th><th class="fl-num">Amount</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @foreach($rows as $r)
                            <tr @class(['fl-rejected' => $r->isRejected()])>
                                <td class="text-nowrap" data-order="{{ $r->service_date->format('Ymd') }}">{{ $r->service_date->format('d M Y') }}</td>
                                <td><a href="{{ route('fleet.vehicles.show', $r->vehicle_id) }}">{{ $r->vehicle?->reg_no }}</a></td>
                                <td>{{ $r->field?->name }}</td>
                                <td>{{ $r->type?->name }}</td>
                                <td>{{ $r->vendor }}@if($r->reference)<div class="text-muted">{{ $r->reference }}</div>@endif</td>
                                <td>{{ $r->description }}</td>
                                <td class="fl-num">{{ $r->odometer ? number_format($r->odometer) : '' }}</td>
                                <td class="text-nowrap">{{ $r->valid_until?->format('d M Y') }}</td>
                                <td class="fl-num fw-semibold">{{ $m($r->amount) }}</td>
                                <td class="text-nowrap">
                                    @if($r->isPaid())<span class="badge bg-label-info">Paid</span>@if($r->expense?->payment_id) <a class="small" href="{{ route('money.payments.show', $r->expense->payment_id) }}">#{{ $r->expense->payment_id }}</a>@endif
                                    @else @include('fleet._approval', ['r' => $r, 'approve' => route('fleet.maintenance.approve', $r), 'reject' => route('fleet.maintenance.reject', $r)])
                                        @if($r->payState() === 'awaiting payment')<div class="small text-muted">awaiting payment</div>@endif
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    @if($r->isEditableBy($user))
                                        <a href="{{ route('fleet.maintenance.edit', $r) }}" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Edit"><i class="bx bx-edit"></i></a>
                                        <form method="POST" action="{{ route('fleet.maintenance.destroy', $r) }}" class="d-inline" onsubmit="return confirm('Delete this maintenance record?');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger py-0 px-1" title="Delete"><i class="bx bx-trash"></i></button></form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    @if($rows->isEmpty())<div class="text-center text-muted py-3">No maintenance in this period.</div>@endif
                </div></div>
            </div>
            <div class="col-xl-3">
                <div class="card"><div class="card-body">
                    <h6>Spent by type</h6>
                    @forelse($byType as $name => $amount)
                        <div class="d-flex justify-content-between small"><span>{{ $name }}</span><span class="fl-num">{{ $m($amount) }}</span></div>
                    @empty
                        <div class="small text-muted">Nothing yet.</div>
                    @endforelse
                    <hr class="my-2"><div class="d-flex justify-content-between fw-bold"><span>Total</span><span class="fl-num">{{ $m($live->sum('amount')) }}</span></div>
                    <div class="small text-muted mt-1">{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}, rejected left out.</div>
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
        if (window.DataTable && document.querySelector('#mtTable tbody tr')) try {
            new DataTable('#mtTable', { order: [[0, 'desc']], pageLength: 50, columnDefs: [{ targets: 10, orderable: false }],
                layout: { topStart: { buttons: [{ extend: 'excelHtml5', text: 'Export to Excel', className: 'btn btn-secondary btn-sm', title: @json('Vehicle maintenance ' . $from->format('d M Y') . ' - ' . $to->format('d M Y')), exportOptions: { columns: ':not(:last-child)' } }] } } });
        } catch (e) { console.warn(e); }
    </script>
    @endsection
</x-sales-dashboard>
