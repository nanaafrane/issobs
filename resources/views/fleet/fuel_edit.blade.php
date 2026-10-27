<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <a href="{{ route('fleet.fuel', ['month' => $fuel->entry_date->format('Y-m')]) }}" class="small"><i class="bx bx-arrow-back"></i> Fuel</a>
        <h3 class="text-primary mt-1 mb-3"><i class="bx bx-gas-pump me-1"></i> Edit fuel #{{ $fuel->id }} · {{ $fuel->vehicle?->reg_no }}</h3>
        @include('flash-messages')
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        <div class="card"><div class="card-body">
            <form method="POST" action="{{ route('fleet.fuel.update', $fuel) }}" class="row g-2" autocomplete="off">
                @csrf @method('PUT')
                @include('fleet._fuel_fields', ['f' => $fuel])
                <div class="col-12 mt-3"><button class="btn btn-primary btn-sm">Save</button></div>
            </form>
        </div></div>
    </div>
    @endsection
</x-sales-dashboard>
