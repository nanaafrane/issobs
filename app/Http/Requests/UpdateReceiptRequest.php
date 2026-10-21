<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\PaymentModeRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReceiptRequest extends FormRequest
{
    use PaymentModeRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge([
            'from' => ['required', 'string', 'max:255'],
            'receipt_month' => ['required', 'date'],
            'keep_credit' => ['nullable', 'in:1,on'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'description' => ['nullable', 'string', 'max:255'],
            // The edit screen (same as a new receipt): one row per invoice.
            'invoices' => ['nullable', 'array'],
            'invoices.*.selected' => ['nullable', 'in:1'],
            'invoices.*.applied' => ['nullable', 'numeric', 'min:0'],
            'invoices.*.wht' => ['nullable', 'numeric', 'min:0'],
            'invoices.*.vat7' => ['nullable', 'numeric', 'min:0'],
            'invoices.*.deduction' => ['nullable', 'numeric', 'min:0'],
        ], $this->paymentModeRules(), $this->adjustmentRules());
    }

    public function attributes(): array
    {
        return $this->paymentModeAttributes();
    }
}
