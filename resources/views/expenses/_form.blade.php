{{--
    resources/views/expenses/_form.blade.php
    Shared by create.blade.php and edit.blade.php.
    Expects: $fieldOffices, $fieldTypes, $typicalTypesByField, $selectedFieldId,
    and optionally $expense (null on create).
--}}

<div class="mb-3">
    <label for="field_id" class="form-label">Office</label>
    <select name="field_id" id="field_id" class="form-select" required>
        @foreach($fieldOffices as $office)
            <option value="{{ $office->id }}"
                @selected(old('field_id', $selectedFieldId ?? Auth::user()->field_id) == $office->id)>
                {{ $office->name }}
            </option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label for="expense_type_id" class="form-label">Expense Type</label>
    <select name="expense_type_id" id="expense_type_id" class="form-select" required>
        <option value="">-- Select type --</option>
        @foreach($fieldTypes as $type)
            <option value="{{ $type->id }}"
                @selected(old('expense_type_id', $expense->expense_type_id ?? null) == $type->id)>
                {{ $type->name }}
            </option>
        @endforeach
        <option value="new">+ Add new type</option>
    </select>
    <div id="unusualTypeWarning" class="alert alert-warning mt-2 d-none py-2 px-3 small">
        Heads up: this type isn't typically used at the selected office. You can still submit if that's correct.
    </div>
</div>

<div class="mb-3 d-none" id="newTypeWrapper">
    <label for="new_expense_type" class="form-label">New Expense Type Name</label>
    <input type="text" name="new_expense_type" id="new_expense_type" class="form-control"
           placeholder="e.g. Generator Fuel" value="{{ old('new_expense_type') }}">
</div>

<div class="mb-3">
    <label for="description" class="form-label">Description</label>
    <textarea name="description" id="description" class="form-control" rows="3" required>{{ old('description', $expense->description ?? '') }}</textarea>
</div>

<div class="mb-3">
    <label for="amount" class="form-label">Amount</label>
    <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control"
           value="{{ old('amount', $expense->amount ?? '') }}" required>
</div>

<div class="mb-3">
    <label for="image" class="form-label">Receipt / Image {{ isset($expense) ? '(leave blank to keep existing)' : '' }}</label>
    <input type="file" name="image" id="image" class="form-control" accept="image/*">
    @if(isset($expense) && $expense->image)
        <div class="form-text">Current file: {{ $expense->image }}</div>
    @endif
</div>

<button type="submit" class="btn btn-primary">
    {{ isset($expense) ? 'Update Expense' : 'Save Expense' }}
</button>

<script>
const typicalTypesByField = @json($typicalTypesByField);

const fieldSelect = document.getElementById('field_id');
const typeSelect  = document.getElementById('expense_type_id');
const newTypeWrap = document.getElementById('newTypeWrapper');
const warning     = document.getElementById('unusualTypeWarning');

function checkUnusualCombo() {
    const fieldId = fieldSelect.value;
    const typeId  = typeSelect.value;

    if (!fieldId || !typeId || typeId === 'new') {
        warning.classList.add('d-none');
        return;
    }

    const typical = typicalTypesByField[fieldId] || [];
    const isUnusual = !typical.includes(Number(typeId));
    warning.classList.toggle('d-none', !isUnusual);
}

typeSelect.addEventListener('change', () => {
    newTypeWrap.classList.toggle('d-none', typeSelect.value !== 'new');
    checkUnusualCombo();
});
fieldSelect.addEventListener('change', checkUnusualCombo);

// run once on load in case old()/edit values are already unusual
checkUnusualCombo();
</script>
