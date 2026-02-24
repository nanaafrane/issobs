<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProformaRequest extends FormRequest
{
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
        return [
            //
            'service' => 'nullable',
            // 'due_date'  => 'nullable',
            'invoice_month' => 'nullable',
            'quantity' => 'nullable',
            'client_id' => 'nullable',
            'unit_price' => 'nullable',
            'amount'  => 'nullable',
        ];
    }
}
