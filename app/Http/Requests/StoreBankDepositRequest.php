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
        if ($this->input('wallet_amount') === '') {
            $this->merge(['wallet_amount' => null]);
        }
    }

    public function rules(): array
    {
        return [
            // A bank, an office cash box or the MoMo wallet (collections can be kept and spent there).
            'bank_id' => ['required', 'integer', 'exists:banks,id'],
            'deposit_date' => ['required', 'date', 'before_or_equal:today', 'after:' . now()->subYear()->toDateString()],
            'slip_number' => ['nullable', 'string', 'max:60'],
            'depositor' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
            'cash' => ['nullable', 'array'],
            'cash.*' => ['integer'],
            'momo' => ['nullable', 'array'],
            'momo.*' => ['integer'],
            'momo_charge' => ['nullable', 'numeric', 'min:0'],
            // MoMo withdrawn from the company wallet: tick its receipts, or type the amount cashed out.
            'wallet' => ['nullable', 'array'],
            'wallet.*' => ['integer'],
            'wallet_id' => ['nullable', 'integer', 'exists:banks,id'],
            'wallet_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'bank_id' => 'bank account',
            'deposit_date' => 'date on the slip',
            'slip_number' => 'slip number',
            'momo_charge' => 'MoMo withdrawal charge',
            'wallet_amount' => 'MoMo withdrawn from the wallet',
        ];
    }
}
