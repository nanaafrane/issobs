{{-- Tabs across the fleet pages. @include('fleet._tabs', ['active' => 'fuel']) --}}
@php
    $tabs = [
        'vehicles' => ['Vehicles', route('fleet.vehicles'), 'bx-car'],
        'fuel' => ['Fuel', route('fleet.fuel'), 'bx-gas-pump'],
        'payouts' => ['Fuel pay-outs', route('fleet.fuel.payouts'), 'bx-money-withdraw'],
        'maintenance' => ['Maintenance', route('fleet.maintenance'), 'bx-wrench'],
        'types' => ['Maintenance types', route('fleet.maintenance.types'), 'bx-list-ul'],
        'report' => ['Monthly cost', route('fleet.report'), 'bx-bar-chart-alt-2'],
    ];
@endphp
<ul class="nav nav-pills flex-wrap gap-1 mb-3 no-print">
    @foreach($tabs as $key => [$label, $href, $icon])
        <li class="nav-item"><a href="{{ $href }}" @class(['nav-link py-1 px-3', 'active' => ($active ?? '') === $key])><i class="bx {{ $icon }} me-1"></i>{{ $label }}</a></li>
    @endforeach
</ul>
