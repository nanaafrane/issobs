{{-- Fuel entry fields (the columns of the FUEL REQUEST AND USAGE REPORT), for add ($f = null) and edit. --}}
<div class="col-md-2 col-6"><label class="form-label small mb-0">Date</label><input type="date" name="entry_date" class="form-control form-control-sm" value="{{ old('entry_date', $f?->entry_date?->toDateString() ?? now()->toDateString()) }}" max="{{ now()->toDateString() }}" required></div>
<div class="col-md-3 col-6"><label class="form-label small mb-0">Vehicle no.</label>
    <select name="vehicle_id" class="form-select form-select-sm fl-vehicle" required>
        <option value="">Choose…</option>
        @foreach($vehicles as $v)<option value="{{ $v->id }}" data-fuel="{{ $v->fuel_type }}" @selected((int) old('vehicle_id', $f?->vehicle_id ?? request('vehicle_id')) === $v->id)>{{ $v->reg_no }}{{ $v->name ? ' · ' . $v->name : '' }}</option>@endforeach
    </select></div>
<div class="col-md-3 col-6"><label class="form-label small mb-0">Driver</label>
    <select name="driver_employee_id" class="form-select form-select-sm">
        <option value="">(vehicle's driver / type below)</option>
        @foreach($drivers as $d)<option value="{{ $d->id }}" @selected((int) old('driver_employee_id', $f?->driver_employee_id) === $d->id)>{{ $d->name }}</option>@endforeach
    </select></div>
<div class="col-md-2 col-6"><label class="form-label small mb-0">or driver name</label><input name="driver_name" class="form-control form-control-sm" value="{{ old('driver_name', $f?->driver_name) }}" maxlength="120"></div>
<div class="col-md-2 col-6"><label class="form-label small mb-0">Office</label>
    <select name="field_id" class="form-select form-select-sm"><option value="">(vehicle's office)</option>@foreach($fields as $fl)<option value="{{ $fl->id }}" @selected((int) old('field_id', $f?->field_id) === $fl->id)>{{ $fl->name }}</option>@endforeach</select></div>

<div class="col-md-3 col-6"><label class="form-label small mb-0">Trip / purpose</label><input name="purpose" class="form-control form-control-sm" value="{{ old('purpose', $f?->purpose) }}" placeholder="DEPLOYMENT & PATROL" maxlength="150" list="flPurposes"></div>
<div class="col-md-2 col-6"><label class="form-label small mb-0">Fuel type</label>
    <select name="fuel_type" class="form-select form-select-sm fl-type"><option value="">(vehicle's)</option>@foreach(\App\Models\Vehicle::FUEL_TYPES as $k => $l)<option value="{{ $k }}" @selected(old('fuel_type', $f?->fuel_type) === $k)>{{ $l }}</option>@endforeach</select></div>
<div class="col-md-1 col-4"><label class="form-label small mb-0">Requested</label><input type="number" step="0.01" min="0" name="amount_requested" class="form-control form-control-sm" value="{{ old('amount_requested', $f?->amount_requested) }}"></div>
<div class="col-md-2 col-4"><label class="form-label small mb-0">Issued (GH₵)</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm fl-amount" value="{{ old('amount', $f?->amount) }}" required></div>
<div class="col-md-1 col-4"><label class="form-label small mb-0">GH₵ / L</label><input type="number" step="0.001" min="0.01" name="price_per_litre" class="form-control form-control-sm fl-price" value="{{ old('price_per_litre', $f?->price_per_litre) }}"></div>
<div class="col-md-1 col-4"><label class="form-label small mb-0">Litres</label><input type="number" step="0.01" min="0.01" name="litres" class="form-control form-control-sm fl-litres" value="{{ old('litres', $f?->litres) }}"></div>
<div class="col-md-2 col-4"><label class="form-label small mb-0">Station</label><input name="station" class="form-control form-control-sm text-uppercase" value="{{ old('station', $f?->station) }}" list="flStations" maxlength="100"></div>
<div class="col-md-2 col-4"><label class="form-label small mb-0">km reading</label><input type="number" min="0" name="odometer" class="form-control form-control-sm" value="{{ old('odometer', $f?->odometer) }}"></div>
<div class="col-md-2 col-6"><label class="form-label small mb-0">Receipt / coupon no.</label><input name="receipt_no" class="form-control form-control-sm" value="{{ old('receipt_no', $f?->receipt_no) }}" maxlength="60"></div>
<div class="col-md-8 col-6"><label class="form-label small mb-0">Remark</label><input name="notes" class="form-control form-control-sm" value="{{ old('notes', $f?->notes) }}" maxlength="500"></div>
<datalist id="flStations">@foreach($stations as $s)<option value="{{ $s }}">@endforeach</datalist>
<datalist id="flPurposes"><option value="DEPLOYMENT & PATROL"><option value="SUPERVISION"><option value="RELIEF / GUARD CHANGE"><option value="OFFICE ERRAND"><option value="CLIENT VISIT"></datalist>
<script>
    // Litres = issued ÷ price, as on the sheet (QUANTITY = FUEL ISSUED ÷ UNITS).
    document.addEventListener('input', function (e) {
        var form = e.target.closest('form'); if (!form || !form.querySelector('.fl-amount')) return;
        var a = parseFloat(form.querySelector('.fl-amount').value), p = parseFloat(form.querySelector('.fl-price').value), l = form.querySelector('.fl-litres');
        if (e.target.classList.contains('fl-litres')) return;
        if (a > 0 && p > 0) l.value = (a / p).toFixed(2);
    });
</script>
