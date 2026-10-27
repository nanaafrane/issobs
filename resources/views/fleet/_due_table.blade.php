{{-- Renewals and services due. @include('fleet._due_table', ['due' => $due, 'showVehicle' => true]) --}}
@php
    $dueBadge = ['overdue' => ['bg-danger', 'Overdue / expired'], 'soon' => ['bg-warning', 'Due soon'], 'missing' => ['bg-secondary', 'No record'], 'ok' => ['bg-success', 'OK']];
@endphp
<div class="table-responsive">
    <table class="table table-sm mb-0">
        <thead><tr>@if($showVehicle ?? true)<th>Vehicle</th><th>Office</th>@endif<th>What</th><th>Last done</th><th>Due / expires</th><th class="text-end">km left</th><th>State</th></tr></thead>
        <tbody>
        @forelse($due as $d)
            <tr>
                @if($showVehicle ?? true)
                <td><a href="{{ route('fleet.vehicles.show', $d['vehicle']) }}">{{ $d['vehicle']->reg_no }}</a> <span class="small text-muted">{{ $d['vehicle']->name }}</span></td>
                <td class="small">{{ $d['vehicle']->field?->name }}</td>
                @endif
                <td>{{ $d['type']->name }}</td>
                <td class="small">{{ $d['last']?->service_date?->format('d M Y') ?? '—' }}@if($d['last']?->vendor)<div class="text-muted">{{ $d['last']->vendor }}</div>@endif</td>
                <td class="small">
                    @if($d['due_date']){{ $d['due_date']->format('d M Y') }} <span class="text-muted">({{ $d['days'] < 0 ? abs($d['days']) . ' days ago' : 'in ' . $d['days'] . ' days' }})</span>@endif
                    @if($d['due_km'])<div>at {{ number_format($d['due_km']) }} km</div>@endif
                    @if(! $d['due_date'] && ! $d['due_km'])—@endif
                </td>
                <td class="text-end small">{{ $d['km_left'] !== null ? number_format($d['km_left']) : '' }}</td>
                <td><span class="badge {{ $dueBadge[$d['state']][0] }}">{{ $dueBadge[$d['state']][1] }}</span></td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-3">Nothing is due.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
