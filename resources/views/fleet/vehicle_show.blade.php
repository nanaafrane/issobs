<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <style>
        .fl-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        .fl-band { display: grid; grid-template-columns: repeat(5, 1fr); background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; }
        .fl-band > div { padding: .9rem 1.1rem; border-right: 1px solid #e5e7eb; }
        .fl-band > div:last-child { border-right: 0; }
        .fl-band .label { font-size: .78rem; color: #6b7280; }
        .fl-band .value { font-size: 1.25rem; font-weight: 700; font-variant-numeric: tabular-nums; }
        @media (max-width: 767px) { .fl-band { grid-template-columns: 1fr 1fr; } .fl-band > div { border-bottom: 1px solid #e5e7eb; } }
    </style>
    @endsection

    @section('content')
    @php $m = fn ($v) => number_format((float) $v, 2); @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <a href="{{ route('fleet.vehicles') }}" class="small"><i class="bx bx-arrow-back"></i> Fleet</a>
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mt-1 mb-3">
            <div>
                <h3 class="mb-1">{{ $vehicle->reg_no }} <span class="badge bg-label-primary align-middle fs-6">{{ \App\Models\Vehicle::STATUSES[$vehicle->status] ?? $vehicle->status }}</span></h3>
                <div class="text-muted small">{{ $vehicle->name }} · {{ \App\Models\Vehicle::KINDS[$vehicle->kind] ?? '' }} · {{ \App\Models\Vehicle::FUEL_TYPES[$vehicle->fuel_type] ?? '' }} · {{ $vehicle->field?->name }} · Driver {{ $vehicle->driverLabel() }}</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('fleet.fuel', ['vehicle_id' => $vehicle->id]) }}" class="btn btn-outline-primary btn-sm"><i class="bx bx-gas-pump"></i> Add fuel</a>
                <a href="{{ route('fleet.maintenance', ['vehicle_id' => $vehicle->id]) }}" class="btn btn-primary btn-sm"><i class="bx bx-wrench"></i> Record maintenance</a>
            </div>
        </div>
        @include('flash-messages')
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

        <form method="GET" class="d-flex flex-wrap align-items-end gap-2 mb-3">
            <div><label class="form-label small mb-0">From</label><input type="date" name="from" class="form-control form-control-sm" value="{{ $from->toDateString() }}"></div>
            <div><label class="form-label small mb-0">To</label><input type="date" name="to" class="form-control form-control-sm" value="{{ $to->toDateString() }}"></div>
            <button class="btn btn-sm btn-primary">Show</button>
        </form>

        <div class="fl-band mb-3">
            <div><div class="label">Fuel ({{ $m($summary['litres'] ?? 0) }} L)</div><div class="value">{{ $m($summary['fuel'] ?? 0) }}</div></div>
            <div><div class="label">Maintenance</div><div class="value">{{ $m($summary['maintenance'] ?? 0) }}</div></div>
            <div><div class="label">Total cost</div><div class="value">{{ $m($summary['total'] ?? 0) }}</div></div>
            <div><div class="label">km driven · km per litre</div><div class="value">{{ ($summary['km'] ?? null) ? number_format($summary['km']) : '—' }} · {{ $summary['km_per_litre'] ?? '—' }}</div></div>
            <div><div class="label">Cost per km</div><div class="value">{{ ($summary['cost_per_km'] ?? null) ? $m($summary['cost_per_km']) : '—' }}</div></div>
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card mb-3">
                    <div class="card-header py-2"><h6 class="mb-0">Due and expiry</h6></div>
                    @include('fleet._due_table', ['due' => $due, 'showVehicle' => false])
                </div>

                <div class="card mb-3">
                    <div class="card-header py-2"><h6 class="mb-0">Maintenance ({{ $work->count() }})</h6></div>
                    <div class="table-responsive"><table class="table table-sm mb-0">
                        <thead><tr><th>Date</th><th>What</th><th>Vendor / ref</th><th class="fl-num">km</th><th class="fl-num">Amount</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse($work as $w)
                            <tr>
                                <td class="text-nowrap">{{ $w->service_date->format('d M Y') }}</td>
                                <td>{{ $w->type?->name }}@if($w->description)<div class="small text-muted">{{ $w->description }}</div>@endif</td>
                                <td class="small">{{ $w->vendor }} {{ $w->reference }}@if($w->valid_until)<div class="text-muted">valid until {{ $w->valid_until->format('d M Y') }}</div>@endif</td>
                                <td class="fl-num small">{{ $w->odometer ? number_format($w->odometer) : '' }}</td>
                                <td class="fl-num">{{ $m($w->amount) }}</td>
                                <td class="small">{{ $w->stageLabel() }}@if($w->payState())<div class="text-muted">{{ $w->payState() }}</div>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">No maintenance in this period.</td></tr>
                        @endforelse
                        </tbody>
                    </table></div>
                </div>

                <div class="card">
                    <div class="card-header py-2"><h6 class="mb-0">Fuel ({{ $fuel->count() }} fill-ups)</h6></div>
                    <div class="table-responsive"><table class="table table-sm mb-0">
                        <thead><tr><th>Date</th><th>Driver</th><th>Trip</th><th class="fl-num">km</th><th class="fl-num">Litres</th><th class="fl-num">GH₵/L</th><th class="fl-num">Amount</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse($fuel as $f)
                            <tr>
                                <td class="text-nowrap">{{ $f->entry_date->format('d M Y') }}</td>
                                <td class="small">{{ $f->driverLabel() }}</td>
                                <td class="small">{{ $f->purpose }} <span class="text-muted">{{ $f->station }}</span></td>
                                <td class="fl-num small">{{ $f->odometer ? number_format($f->odometer) : '' }}</td>
                                <td class="fl-num">{{ $m($f->litres) }}</td>
                                <td class="fl-num small">{{ number_format((float) $f->price_per_litre, 2) }}</td>
                                <td class="fl-num">{{ $m($f->amount) }}</td>
                                <td class="small">{{ $f->payout_id ? 'Paid (#' . $f->payout_id . ')' : $f->stageLabel() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-3">No fuel in this period.</td></tr>
                        @endforelse
                        </tbody>
                    </table></div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card"><div class="card-body">
                    <h6>Vehicle details</h6>
                    <form method="POST" action="{{ route('fleet.vehicles.update', $vehicle) }}" class="row g-2">
                        @csrf @method('PUT')
                        @include('fleet._vehicle_fields', ['v' => $vehicle])
                        <div class="col-12"><button class="btn btn-sm btn-outline-dark">Save details</button></div>
                    </form>
                    @if($fuel->isEmpty() && $work->isEmpty())
                    <form method="POST" action="{{ route('fleet.vehicles.destroy', $vehicle) }}" class="mt-2" onsubmit="return confirm('Remove {{ $vehicle->reg_no }}?');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Remove (never used)</button></form>
                    @endif
                </div></div>
            </div>
        </div>
    </div>
    @endsection
</x-sales-dashboard>
