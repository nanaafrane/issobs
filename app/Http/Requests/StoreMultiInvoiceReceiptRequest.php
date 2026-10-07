<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\PaymentModeRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * One receipt paying several invoices of the same client (or none, when the
 * whole amount is an advance kept as client credit).
 *
 * invoices[<invoice id>][selected]   "1" when the invoice is ticked
 * invoices[<invoice id>][applied]    money applied (blank = auto, oldest first)
 * invoices[<invoice id>][wht]        WHT credited on this invoice
 * invoices[<invoice id>][vat7]       7% VAT withheld on this invoice
 * invoices[<invoice id>][deduction]  other deductions on this invoice
 */
class StoreMultiInvoiceReceiptRequest extends FormRequest
{
    use PaymentModeRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'receipt_month' => ['required', 'date'],
            'from' => ['required', 'string', 'max:255'],
            'staff' => ['nullable', 'integer', 'exists:users,id'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'keep_credit' => ['nullable', 'in:1'],
            'description' => ['nullable', 'string', 'max:255'],

            'invoices' => ['nullable', 'array'],
            'invoices.*.selected' => ['nullable', 'in:1'],
            'invoices.*.applied' => ['nullable', 'numeric', 'min:0'],
            'invoices.*.wht' => ['nullable', 'numeric', 'min:0'],
            'invoices.*.vat7' => ['nullable', 'numeric', 'min:0'],
            'invoices.*.deduction' => ['nullable', 'numeric', 'min:0'],
        ], $this->paymentModeRules());
    }

    public function attributes(): array
    {
        return array_merge($this->paymentModeAttributes(), [
            'receipt_month' => 'receipt date',
            'keep_credit' => 'keep extra as client credit',
        ]);
    }
}
