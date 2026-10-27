<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <style>.fl-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }</style>
    @endsection

    @section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-2">
            <div>
                <h3 class="text-primary mb-1"><i class="bx bx-car me-1"></i> Fleet</h3>
                <div class="text-muted small">Every vehicle and motorbike, with its office and driver. Fuel and maintenance are recorded against a vehicle.</div>
            </div>
        </div>
        @include('fleet._tabs', ['active' => 'vehicles'])
        @include('flash-messages')
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card mb-3">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Vehicles ({{ $vehicles->count() }})</h6>
                        <form method="GET"><select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach(\App\Models\Vehicle::STATUSES + ['all' => 'All'] as $k => $v)<option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>@endforeach
                        </select></form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Reg. no.</th><th>Vehicle</th><th>Office</th><th>Driver</th><th>Fuel</th><th class="fl-num">Last km</th><th></th></tr></thead>
                            <tbody>
                            @forelse($vehicles as $v)
                                <tr>
                                    <td class="fw-semibold"><a href="{{ route('fleet.vehicles.show', $v) }}">{{ $v->reg_no }}</a></td>
                                    <td>{{ $v->name }} <span class="small text-muted">{{ \App\Models\Vehicle::KINDS[$v->kind] ?? $v->kind }}</span></td>
                                    <td>{{ $v->field?->name }}</td>
                                    <td class="small">{{ $v->driverLabel() }}</td>
                                    <td class="small">{{ \App\Models\Vehicle::FUEL_TYPES[$v->fuel_type] ?? $v->fuel_type }}</td>
                                    <td class="fl-num small">{{ ($km = $v->lastOdometer()) ? number_format($km) : '' }}</td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('fleet.fuel', ['vehicle_id' => $v->id]) }}" class="btn btn-sm btn-outline-secondary py-0" title="Fuel"><i class="bx bx-gas-pump"></i></a>
                                        <a href="{{ route('fleet.vehicles.show', $v) }}" class="btn btn-sm btn-outline-primary py-0">Open</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">No vehicles yet. Add the first one on the right.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header py-2 d-flex justify-content-between"><h6 class="mb-0">Due, expired or missing (roadworthy, insurance, servicing…)</h6><a class="small" href="{{ route('fleet.maintenance') }}">Record maintenance</a></div>
                    @include('fleet._due_table', ['due' => $due, 'showVehicle' => true])
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card"><div class="card-body">
                    <h6>Add a vehicle</h6>
                    <form method="POST" action="{{ route('fleet.vehicles.store') }}" class="row g-2">
                        @csrf
                        @include('fleet._vehicle_fields', ['v' => null])
                        <div class="col-12"><button class="btn btn-primary btn-sm">Add vehicle</button></div>
                    </form>
                </div></div>
            </div>
        </div>
    </div>
    @endsection
</x-sales-dashboard>
