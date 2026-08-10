{{--
    resources/views/expenses/_form.blade.php
    Shared by create.blade.php and edit.blade.php.
--}}

<style>
    .expense-form-section { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; }
    .expense-form-section h6 { font-size: .75rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #6b7280; margin-bottom: 1rem; }
    .expense-form-section .form-label { font-weight: 500; font-size: .875rem; color: #374151; }
    .expense-form-section .form-text { color: #9ca3af; }
</style>

<div class="expense-form-section">
    <h6>Where &amp; What</h6>

    <div class="row g-3">
        <div class="col-md-6">
            <label for="field_id" class="form-label">Office</label>
            <select name="field_id" id="field_id" class="form-select" required>
                @foreach($fieldOffices as $office)
                    <option value="{{ $office->id }}"
                        data-scope="{{ strtolower(trim($office->name)) === 'accra' ? 'corporate' : 'field' }}"
                        @selected(old('field_id', $selectedFieldId ?? Auth::user()->field_id) == $office->id)>
                        {{ $office->name }}{{ $office->parent_field_id ? ' (sub-office)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label for="field_expense_type_id" class="form-label">Expense Type</label>

            <select id="field_expense_type_id" name="expense_type_id" class="form-select">
                <option value="">-- Select type --</option>
                @foreach($fieldTypes as $type)
                    <option value="{{ $type->id }}"
                        @selected(old('expense_type_id', $expense->expense_type_id ?? null) == $type->id)>
                        {{ $type->name }}
                    </option>
                @endforeach
                <option value="new">+ Add new type</option>
            </select>

            <select id="corporate_expense_type_id" name="expense_type_id" class="form-select d-none" disabled>
                <option value="">-- Select type --</option>
                @foreach($corporateTypes as $type)
                    <option value="{{ $type->id }}"
                        @selected(old('expense_type_id', $expense->expense_type_id ?? null) == $type->id)>
                        {{ $type->name }}
                    </option>
                @endforeach
                <option value="new">+ Add new type</option>
            </select>

            <div id="unusualTypeWarning" class="alert alert-warning mt-2 d-none py-2 px-3 small mb-0">
                Heads up: this type isn't typically used at the selected office. You can still submit if that's correct.
            </div>
        </div>
    </div>

    <div class="mb-0 mt-3 d-none" id="newTypeWrapper">
        <label for="new_expense_type" class="form-label">New Expense Type Name</label>
        <input type="text" name="new_expense_type" id="new_expense_type" class="form-control"
               placeholder="e.g. Generator Fuel" value="{{ old('new_expense_type') }}">
    </div>
</div>

<div class="expense-form-section">
    <h6>Expense Details</h6>

    <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea name="description" id="description" class="form-control" rows="3" required>{{ old('description', $expense->description ?? '') }}</textarea>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <label for="amount" class="form-label">Amount</label>
            <div class="input-group">
                <span class="input-group-text">GHS</span>
                <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control"
                       value="{{ old('amount', $expense->amount ?? '') }}" required>
            </div>
        </div>
        <div class="col-md-6">
            <label for="expense_date" class="form-label">Expense Date</label>
            <input type="date" name="expense_date" id="expense_date" class="form-control"
                   max="{{ now()->format('Y-m-d') }}"
                   value="{{ old('expense_date', isset($expense) ? $expense->expense_date?->format('Y-m-d') : now()->format('Y-m-d')) }}"
                   required>
            <div class="form-text">When it happened -- not necessarily today.</div>
        </div>
    </div>
</div>

<div class="expense-form-section">
    <h6>Receipt</h6>
    <label for="image" class="form-label">Image {{ isset($expense) ? '(leave blank to keep existing)' : '' }}</label>
    <input type="file" name="image" id="image" class="form-control" accept="image/*">
    @if(isset($expense) && $expense->image)
        <div class="form-text">Current file: {{ $expense->image }}</div>
    @endif
</div>

<button type="submit" class="btn btn-primary px-4">
    {{ isset($expense) ? 'Update Expense' : 'Save Expense' }}
</button>
<a href="{{ route('expense.index') }}" class="btn btn-link text-secondary">Cancel</a>

<script>
const typicalTypesByField = @json($typicalTypesByField);

const fieldSelect      = document.getElementById('field_id');
const fieldTypeSelect  = document.getElementById('field_expense_type_id');
const corpTypeSelect   = document.getElementById('corporate_expense_type_id');
const newTypeWrap      = document.getElementById('newTypeWrapper');
const warning          = document.getElementById('unusualTypeWarning');

function activeTypeSelect() {
    return corpTypeSelect.disabled ? fieldTypeSelect : corpTypeSelect;
}

function syncOfficeScope() {
    const selectedOption = fieldSelect.options[fieldSelect.selectedIndex];
    const scope = selectedOption?.dataset.scope || 'field';

    if (scope === 'corporate') {
        fieldTypeSelect.classList.add('d-none');
        fieldTypeSelect.disabled = true;
        corpTypeSelect.classList.remove('d-none');
        corpTypeSelect.disabled = false;
    } else {
        corpTypeSelect.classList.add('d-none');
        corpTypeSelect.disabled = true;
        fieldTypeSelect.classList.remove('d-none');
        fieldTypeSelect.disabled = false;
    }

    checkUnusualCombo();
}

function checkUnusualCombo() {
    const fieldId = fieldSelect.value;
    const select  = activeTypeSelect();
    const typeId  = select.value;

    if (select === corpTypeSelect || !fieldId || !typeId || typeId === 'new') {
        warning.classList.add('d-none');
        return;
    }

    const typical = typicalTypesByField[fieldId] || [];
    warning.classList.toggle('d-none', typical.includes(Number(typeId)));
}

[fieldTypeSelect, corpTypeSelect].forEach(sel => {
    sel.addEventListener('change', () => {
        newTypeWrap.classList.toggle('d-none', sel.value !== 'new');
        checkUnusualCombo();
    });
});
fieldSelect.addEventListener('change', syncOfficeScope);

syncOfficeScope(); // run once on load (handles edit-form pre-fill too)
</script>