{{--
    "Which of our bank accounts does this money go to?"
    @include('partials.receipt_bank_select', [
        'name' => 'transfer_to_bank_id', 'label' => 'RECEIVED INTO (OUR BANK)',
        'banks' => $banks, 'selected' => $receipt->transfer_to_bank_id ?? null,
    ])
--}}
@php
    $fieldId = $name;
    $current = old($name, $selected ?? null);
@endphp
<label for="{{ $fieldId }}" class="form-label">{{ $label }}</label>
<select name="{{ $name }}" id="{{ $fieldId }}" class="form-select @error($name) is-invalid @enderror">
    <option value="" {{ $current ? '' : 'selected' }}>Select bank account…</option>
    @foreach($banks as $bank)
        <option value="{{ $bank->id }}" {{ (string) $current === (string) $bank->id ? 'selected' : '' }}>
            {{ $bank->name }}@if($bank->acc_number) — {{ $bank->acc_number }}@endif @if($bank->branch) ({{ $bank->branch }})@endif
        </option>
    @endforeach
</select>
@if($banks->isEmpty())
    <small class="text-danger d-block">No bank accounts set up yet — add one under Accounts › Banks.</small>
@endif
@error($name)
    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
@enderror
