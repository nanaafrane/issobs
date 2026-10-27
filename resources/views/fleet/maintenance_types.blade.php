<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <style>.fl-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }</style>
    @endsection

    @section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="mb-2">
            <h3 class="text-primary mb-1"><i class="bx bx-list-ul me-1"></i> Maintenance types</h3>
            <div class="text-muted small">What can be recorded against a vehicle. Each type is paid under an <strong>expense item</strong> (the Main Cash column it lands in), and can repeat every N months or N km — the fleet page then warns when it falls due. Tick <em>has expiry</em> for documents like roadworthy and insurance.</div>
        </div>
        @include('fleet._tabs', ['active' => 'types'])
        @include('flash-messages')
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

        <div class="row g-3">
            <div class="col-xl-8">
                @foreach($types as $t)<form method="POST" action="{{ route('fleet.maintenance.types.update', $t) }}" id="mt{{ $t->id }}">@csrf @method('PUT')</form>@endforeach
                <div class="card"><div class="table-responsive"><table class="table table-sm mb-0 align-middle">
                    <thead><tr><th>Type</th><th>Expense item</th><th>Has expiry</th><th>Every (months)</th><th>Every (km)</th><th>In use</th><th class="fl-num">Records</th><th class="fl-num">Spent</th><th></th></tr></thead>
                    <tbody>
                    @foreach($types as $t)
                        <tr>
                            <td><input form="mt{{ $t->id }}" name="name" class="form-control form-control-sm" value="{{ $t->name }}" maxlength="80" required @disabled(! $canManage)></td>
                            <td><select form="mt{{ $t->id }}" name="expense_type_id" class="form-select form-select-sm" @disabled(! $canManage)>
                                <option value="">Vehicle Repairs &amp; Maintenance</option>
                                @foreach($expenseTypes as $e)<option value="{{ $e->id }}" @selected($t->expense_type_id === $e->id)>{{ $e->name }}{{ $e->scope === 'corporate' ? ' (H.O)' : '' }}</option>@endforeach
                            </select></td>
                            <td class="text-center"><input form="mt{{ $t->id }}" type="checkbox" name="is_document" value="1" class="form-check-input" @checked($t->is_document) @disabled(! $canManage)></td>
                            <td><input form="mt{{ $t->id }}" type="number" name="repeat_months" class="form-control form-control-sm" style="width:5.5rem" value="{{ $t->repeat_months }}" min="1" @disabled(! $canManage)></td>
                            <td><input form="mt{{ $t->id }}" type="number" name="repeat_km" class="form-control form-control-sm" style="width:7rem" value="{{ $t->repeat_km }}" min="100" @disabled(! $canManage)></td>
                            <td class="text-center"><input form="mt{{ $t->id }}" type="checkbox" name="is_active" value="1" class="form-check-input" @checked($t->is_active) @disabled(! $canManage)></td>
                            <td class="fl-num">{{ $t->records_count }}</td>
                            <td class="fl-num">{{ number_format((float) $t->records_sum_amount, 2) }}</td>
                            <td>@if($canManage)<button form="mt{{ $t->id }}" class="btn btn-sm btn-outline-primary py-0">Save</button>@endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div></div>
            </div>
            @if($canManage)
            <div class="col-xl-4">
                <div class="card"><div class="card-body">
                    <h6>Add a type</h6>
                    <form method="POST" action="{{ route('fleet.maintenance.types.store') }}" class="row g-2">
                        @csrf
                        <div class="col-12"><label class="form-label small mb-0">Name</label><input name="name" class="form-control form-control-sm" value="{{ old('name') }}" placeholder="e.g. Fire extinguisher, Wheel alignment" required maxlength="80"></div>
                        <div class="col-12"><label class="form-label small mb-0">Paid under expense item</label>
                            <select name="expense_type_id" class="form-select form-select-sm"><option value="">Vehicle Repairs &amp; Maintenance</option>@foreach($expenseTypes as $e)<option value="{{ $e->id }}" @selected((int) old('expense_type_id') === $e->id)>{{ $e->name }}{{ $e->scope === 'corporate' ? ' (H.O)' : '' }}</option>@endforeach</select></div>
                        <div class="col-6"><label class="form-label small mb-0">Repeat every (months)</label><input type="number" name="repeat_months" min="1" class="form-control form-control-sm" value="{{ old('repeat_months') }}"></div>
                        <div class="col-6"><label class="form-label small mb-0">Repeat every (km)</label><input type="number" name="repeat_km" min="100" class="form-control form-control-sm" value="{{ old('repeat_km') }}"></div>
                        <div class="col-12"><label class="small"><input type="checkbox" name="is_document" value="1" class="form-check-input me-1" @checked(old('is_document'))> Has an expiry date (certificate / policy)</label></div>
                        <div class="col-12"><button class="btn btn-primary btn-sm">Add type</button></div>
                    </form>
                </div></div>
            </div>
            @endif
        </div>
    </div>
    @endsection
</x-sales-dashboard>
