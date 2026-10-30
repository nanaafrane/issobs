{{-- Vehicle form fields, for add ($v = null) and edit. --}}
<div class="col-6"><label class="form-label small mb-0">Registration no.</label><input name="reg_no" class="form-control form-control-sm text-uppercase" value="{{ old('reg_no', $v?->reg_no) }}" placeholder="e.g. GE-1234-24" required maxlength="30"></div>
<div class="col-6"><label class="form-label small mb-0">Make / model</label><input name="name" class="form-control form-control-sm" value="{{ old('name', $v?->name) }}" placeholder="Toyota Land Cruiser" maxlength="120"></div>
<div class="col-6"><label class="form-label small mb-0">Kind</label>
    <select name="kind" class="form-select form-select-sm">@foreach(\App\Models\Vehicle::KINDS as $k => $l)<option value="{{ $k }}" @selected(old('kind', $v?->kind ?? 'car') === $k)>{{ $l }}</option>@endforeach</select></div>
<div class="col-6"><label class="form-label small mb-0">Fuel</label>
    <select name="fuel_type" class="form-select form-select-sm">@foreach(\App\Models\Vehicle::FUEL_TYPES as $k => $l)<option value="{{ $k }}" @selected(old('fuel_type', $v?->fuel_type ?? 'diesel') === $k)>{{ $l }}</option>@endforeach</select></div>
<div class="col-12"><label class="form-label small mb-0">Office</label>
    <select name="field_id" class="form-select form-select-sm" required>
        <option value="">Choose…</option>
        @foreach($fields as $f)<option value="{{ $f->id }}" @selected((int) old('field_id', $v?->field_id ?? (count($fields) === 1 ? $f->id : 0)) === $f->id)>{{ $f->name }}</option>@endforeach
    </select></div>
<div class="col-12"><label class="form-label small mb-0">Driver <span class="text-muted">(Drivers &amp; Riders)</span></label>
    <select name="driver_employee_id" class="form-select form-select-sm">
        <option value="">{{ $drivers->isEmpty() ? 'No Driver or Rider in this office yet' : ($v?->driver_name && ! $v?->driver_employee_id ? $v->driver_name . ' (as recorded — pick from the list)' : '—') }}</option>
        @foreach($drivers as $d)<option value="{{ $d->id }}" @selected((int) old('driver_employee_id', $v?->driver_employee_id) === $d->id)>{{ $d->name }}{{ $d->role ? ' · ' . trim($d->role->name) : '' }}</option>@endforeach
    </select></div>
<div class="col-6"><label class="form-label small mb-0">Tank (litres)</label><input type="number" step="0.01" min="0" name="tank_litres" class="form-control form-control-sm" value="{{ old('tank_litres', $v?->tank_litres) }}"></div>
<div class="col-6"><label class="form-label small mb-0">km reading at start</label><input type="number" min="0" name="opening_odometer" class="form-control form-control-sm" value="{{ old('opening_odometer', $v?->opening_odometer) }}"></div>
@if($v)
<div class="col-12"><label class="form-label small mb-0">Status</label>
    <select name="status" class="form-select form-select-sm">@foreach(\App\Models\Vehicle::STATUSES as $k => $l)<option value="{{ $k }}" @selected(old('status', $v->status) === $k)>{{ $l }}</option>@endforeach</select></div>
@endif
<div class="col-12"><label class="form-label small mb-0">Notes</label><input name="notes" class="form-control form-control-sm" value="{{ old('notes', $v?->notes) }}" maxlength="500"></div>
