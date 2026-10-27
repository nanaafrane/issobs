<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <a href="{{ route('fleet.maintenance') }}" class="small"><i class="bx bx-arrow-back"></i> Maintenance</a>
        <h3 class="text-primary mt-1 mb-1"><i class="bx bx-wrench me-1"></i> Edit maintenance #{{ $maintenance->id }}</h3>
        <div class="text-muted small mb-3">{{ $maintenance->stageLabel() }}@if($maintenance->expense_id) · approved expense #{{ $maintenance->expense_id }} (not yet paid) follows this change @endif</div>
        @include('flash-messages')
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        <div class="card"><div class="card-body">
            <form method="POST" action="{{ route('fleet.maintenance.update', $maintenance) }}" class="row g-2" autocomplete="off">
                @csrf @method('PUT')
                @include('fleet._maintenance_fields', ['r' => $maintenance])
                <div class="col-12 mt-3"><button class="btn btn-primary btn-sm">Save</button></div>
            </form>
        </div></div>
    </div>
    @endsection
</x-sales-dashboard>
