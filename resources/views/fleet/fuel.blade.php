<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">
    <style>
        .fl-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        .fl-tile { border-left: 3px solid var(--bs-primary, #696cff); }
        .fl-tile.warn { border-left-color: var(--bs-warning, #ffab00); }
        table.fl-table td { font-size: .82rem; vertical-align: middle; }
        table.fl-table th { font-size: .75rem; white-space: nowrap; }
        tr.fl-rejected td { opacity: .5; text-decoration: line-through; }
    </style>
    @endsection

    @section('content')
    @php $m = fn ($v) => number_format((float) $v, 2); @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="mb-2">
            <h3 class="text-primary mb-1"><i class="bx bx-gas-pump me-1"></i> Fuel request &amp; usage</h3>
            <div class="text-muted small">Each fill-up is reviewed by the branch, approved by finance, then paid on a <a href="{{ route('fleet.fuel.payouts') }}">fuel pay-out</a> — one expense and one payment per office and period, like overtime.</div>
        </div>
        @include('fleet._tabs', ['active' => 'fuel'])
        @include('flash-messages')
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

        <div class="card mb-3"><div class="card-body">
            <h6 class="mb-2">Add fuel</h6>
            @if($vehicles->isEmpty())
                <div class="text-muted small">Add your vehicles first under <a href="{{ route('fleet.vehicles') }}">Vehicles</a>.</div>
            @else
            <form method="POST" action="{{ route('fleet.fuel.store') }}" class="row g-2" autocomplete="off">
                @csrf
                @include('fleet._fuel_fields', ['f' => null])
                <div class="col-md-2 col-6 d-grid align-items-end"><button class="btn btn-primary btn-sm mt-3">Add</button></div>
            </form>
            @endif
        </div></div>

        <form method="GET" class="d-flex flex-wrap align-items-end gap-2 mb-3">
            <div><label class="form-label small mb-0">Month</label><input type="month" name="month" class="form-control form-control-sm" value="{{ $from->format('Y-m') }}"></div>
            <div><label class="form-label small mb-0">Office</label><select name="field_id" class="form-select form-select-sm"><option value="">All</option>@foreach($fields as $fl)<option value="{{ $fl->id }}" @selected($fieldId === $fl->id)>{{ $fl->name }}</option>@endforeach</select></div>
            <div><label class="form-label small mb-0">Vehicle</label><select name="vehicle_id" class="form-select form-select-sm"><option value="">All</option>@foreach($vehicles as $v)<option value="{{ $v->id }}" @selected($vehicleId === $v->id)>{{ $v->reg_no }}</option>@endforeach</select></div>
            <div><label class="form-label small mb-0">Status</label><select name="state" class="form-select form-select-sm"><option value="">All</option>@foreach(['pending' => 'Waiting for approval', 'approved' => 'Approved', 'owed' => 'Approved, not paid', 'paid' => 'Paid', 'rejected' => 'Rejected'] as $k => $l)<option value="{{ $k }}" @selected($state === $k)>{{ $l }}</option>@endforeach</select></div>
            <button class="btn btn-sm btn-primary">Show</button>
        </form>

        <div class="row g-3 mb-3">
            <div class="col-md-3 col-6"><div class="card fl-tile"><div class="card-body py-2"><div class="small text-muted">Fuel issued · {{ $from->format('M Y') }}</div><div class="fs-5 fw-bold">GH₵ {{ $m($totals['amount']) }}</div></div></div></div>
            <div class="col-md-3 col-6"><div class="card fl-tile"><div class="card-body py-2"><div class="small text-muted">Litres (diesel / super)</div><div class="fs-5 fw-bold">{{ $m($totals['litres']) }}</div><div class="small text-muted">{{ $m($totals['diesel']) }} / {{ $m($totals['super']) }}</div></div></div></div>
            <div class="col-md-3 col-6"><div class="card fl-tile warn"><div class="card-body py-2"><div class="small text-muted">Waiting for approval</div><div class="fs-5 fw-bold">GH₵ {{ $m($totals['pending']) }}</div></div></div></div>
            <div class="col-md-3 col-6"><div class="card fl-tile warn"><div class="card-body py-2"><div class="small text-muted">Approved, not yet paid</div><div class="fs-5 fw-bold">GH₵ {{ $m($totals['owed']) }}</div></div></div></div>
        </div>

        <form method="POST" action="{{ route('fleet.fuel.bulk') }}" id="flBulk">@csrf</form>
        <div class="card">
            <div class="card-header py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h6 class="mb-0">{{ $rows->count() }} fill-ups</h6>
                <div class="d-flex gap-1">
                    <button form="flBulk" name="decision" value="approved" class="btn btn-sm btn-outline-success">Approve ticked</button>
                    <button form="flBulk" name="decision" value="rejected" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject the ticked entries?');">Reject ticked</button>
                </div>
            </div>
            <div class="table-responsive p-2">
                <table class="table table-sm fl-table mb-0 w-100" id="flTable">
                    <thead><tr><th><input type="checkbox" class="form-check-input" id="flAll"></th><th>Date</th><th>Driver</th><th>Vehicle no.</th><th>Office</th><th>Trip</th><th class="fl-num">Litres</th><th class="fl-num">GH₵/L</th><th>Fuel</th><th class="fl-num">Requested</th><th class="fl-num">Issued</th><th>Station</th><th class="fl-num">km</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @foreach($rows as $r)
                        <tr @class(['fl-rejected' => $r->isRejected()])>
                            <td>@if($r->currentStage() && ! $r->payout_id)<input type="checkbox" class="form-check-input fl-pick" name="ids[]" value="{{ $r->id }}" form="flBulk">@endif</td>
                            <td class="text-nowrap" data-order="{{ $r->entry_date->format('Ymd') }}{{ str_pad($r->id, 8, '0', STR_PAD_LEFT) }}">{{ $r->entry_date->format('d M Y') }}</td>
                            <td>{{ $r->driverLabel() }}</td>
                            <td><a href="{{ route('fleet.vehicles.show', $r->vehicle_id) }}">{{ $r->vehicle?->reg_no }}</a></td>
                            <td>{{ $r->field?->name }}</td>
                            <td>{{ $r->purpose }}</td>
                            <td class="fl-num">{{ $m($r->litres) }}</td>
                            <td class="fl-num">{{ number_format((float) $r->price_per_litre, 2) }}</td>
                            <td>{{ strtoupper($r->fuel_type) }}</td>
                            <td class="fl-num">{{ $m($r->amount_requested) }}</td>
                            <td class="fl-num fw-semibold">{{ $m($r->amount) }}</td>
                            <td>{{ $r->station }}</td>
                            <td class="fl-num">{{ $r->odometer ? number_format($r->odometer) : '' }}</td>
                            <td class="text-nowrap">
                                @if($r->payout_id)<a class="badge bg-label-info" href="{{ route('fleet.fuel.payouts.show', $r->payout_id) }}">Paid · pay-out #{{ $r->payout_id }}</a>
                                @else @include('fleet._approval', ['r' => $r, 'approve' => route('fleet.fuel.approve', $r), 'reject' => route('fleet.fuel.reject', $r)]) @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if($r->isEditableBy($user))
                                    <a href="{{ route('fleet.fuel.edit', $r) }}" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Edit"><i class="bx bx-edit"></i></a>
                                    <form method="POST" action="{{ route('fleet.fuel.destroy', $r) }}" class="d-inline" onsubmit="return confirm('Delete this fuel entry?');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger py-0 px-1" title="Delete"><i class="bx bx-trash"></i></button></form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot><tr class="fw-bold"><td colspan="6">TOTAL (not rejected)</td><td class="fl-num">{{ $m($totals['litres']) }}</td><td></td><td></td><td></td><td class="fl-num">{{ $m($totals['amount']) }}</td><td colspan="4"></td></tr></tfoot>
                </table>
            </div>
            @if($rows->isEmpty())<div class="card-body pt-0 text-center text-muted">No fuel entries for this month.</div>@endif
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
        document.getElementById('flAll')?.addEventListener('change', function () { document.querySelectorAll('.fl-pick').forEach(c => c.checked = this.checked); });
        if (window.DataTable && document.querySelector('#flTable tbody tr')) try {
            new DataTable('#flTable', { order: [[1, 'desc']], pageLength: 50, columnDefs: [{ targets: [0, 14], orderable: false }],
                layout: { topStart: { buttons: [{ extend: 'excelHtml5', text: 'Export to Excel', className: 'btn btn-secondary btn-sm', title: @json('Fuel request and usage ' . $from->format('M Y')), exportOptions: { columns: ':not(:first-child):not(:last-child)' } }] } } });
        } catch (e) { console.warn(e); }
    </script>
    @endsection
</x-sales-dashboard>
