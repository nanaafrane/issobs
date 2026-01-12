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
            'service' => 'required',
            'due_date'  => 'required',
            'invoice_month' => 'required',
            'quantity' => 'required',
            'client_id' => 'required|exists:clients,id',
            'unit_price' => 'required',
            'amount'  => 'required',
        ];
    }
}
