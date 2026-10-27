{{-- Maintenance fields, for add ($r = null) and edit. --}}
<div class="col-md-3 col-6"><label class="form-label small mb-0">Vehicle</label>
    <select name="vehicle_id" class="form-select form-select-sm" required>
        <option value="">Choose…</option>
        @foreach($vehicles as $v)<option value="{{ $v->id }}" @selected((int) old('vehicle_id', $r?->vehicle_id ?? request('vehicle_id')) === $v->id)>{{ $v->reg_no }}{{ $v->name ? ' · ' . $v->name : '' }}</option>@endforeach
    </select></div>
<div class="col-md-3 col-6"><label class="form-label small mb-0">Type <a href="{{ route('fleet.maintenance.types') }}" class="small">(add a type)</a></label>
    <select name="maintenance_type_id" class="form-select form-select-sm mt-type" required>
        <option value="">Choose…</option>
        @foreach($types as $t)<option value="{{ $t->id }}" data-doc="{{ $t->is_document ? 1 : 0 }}" data-months="{{ $t->repeat_months }}" data-km="{{ $t->repeat_km }}" @selected((int) old('maintenance_type_id', $r?->maintenance_type_id) === $t->id)>{{ $t->name }}</option>@endforeach
    </select></div>
<div class="col-md-2 col-6"><label class="form-label small mb-0">Date done / paid</label><input type="date" name="service_date" class="form-control form-control-sm" value="{{ old('service_date', $r?->service_date?->toDateString() ?? now()->toDateString()) }}" max="{{ now()->toDateString() }}" required></div>
<div class="col-md-2 col-6"><label class="form-label small mb-0">Amount (GH₵)</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" value="{{ old('amount', $r?->amount) }}" required></div>
<div class="col-md-2 col-6"><label class="form-label small mb-0">Office</label>
    <select name="field_id" class="form-select form-select-sm"><option value="">(vehicle's office)</option>@foreach($fields as $fl)<option value="{{ $fl->id }}" @selected((int) old('field_id', $r?->field_id) === $fl->id)>{{ $fl->name }}</option>@endforeach</select></div>
<div class="col-md-3 col-6"><label class="form-label small mb-0">Garage / insurer / vendor</label><input name="vendor" class="form-control form-control-sm" value="{{ old('vendor', $r?->vendor) }}" maxlength="150"></div>
<div class="col-md-2 col-6"><label class="form-label small mb-0">Invoice / policy / cert. no.</label><input name="reference" class="form-control form-control-sm" value="{{ old('reference', $r?->reference) }}" maxlength="80"></div>
<div class="col-md-2 col-4"><label class="form-label small mb-0">km reading</label><input type="number" min="0" name="odometer" class="form-control form-control-sm" value="{{ old('odometer', $r?->odometer) }}"></div>
<div class="col-md-2 col-4"><label class="form-label small mb-0">Valid until / next due</label><input type="date" name="valid_until" class="form-control form-control-sm" value="{{ old('valid_until', $r?->valid_until?->toDateString()) }}" title="Roadworthy and insurance: the expiry date. Left empty, it is worked out from the type."></div>
<div class="col-md-1 col-4"><label class="form-label small mb-0">Next due km</label><input type="number" min="0" name="next_due_km" class="form-control form-control-sm" value="{{ old('next_due_km', $r?->next_due_km) }}"></div>
<div class="col-md-{{ $r ? 12 : 10 }} col-12"><label class="form-label small mb-0">What was done</label><input name="description" class="form-control form-control-sm" value="{{ old('description', $r?->description) }}" placeholder="e.g. 4 tyres 265/65 R17, wheel alignment" maxlength="500"></div>
