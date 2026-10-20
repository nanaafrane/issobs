<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole(\App\Http\Controllers\BankDepositController::DEPOSIT_ROLES);
    }

    protected function prepareForValidation(): void
    {
        // An empty charge box is "not entered", not zero.
        if ($this->input('momo_charge') === '') {
            $this->merge(['momo_charge' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'bank_id' => ['required', 'integer', \Illuminate\Validation\Rule::exists('banks', 'id')->where('kind', 'bank')],
            'deposit_date' => ['required', 'date', 'before_or_equal:today', 'after:' . now()->subYear()->toDateString()],
            'slip_number' => ['nullable', 'string', 'max:60'],
            'depositor' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
            'cash' => ['nullable', 'array'],
            'cash.*' => ['integer'],
            'momo' => ['nullable', 'array'],
            'momo.*' => ['integer'],
            'momo_charge' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'bank_id' => 'bank account',
            'deposit_date' => 'date on the slip',
            'slip_number' => 'slip number',
            'momo_charge' => 'MoMo withdrawal charge',
        ];
    }
}
