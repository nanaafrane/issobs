<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentInfoRequest extends FormRequest
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
            'bank_id' => 'nullable|exists:banks,id',
            'acc_number' => 'nullable|string|max:50|unique:payment_infos,acc_number',
            'branch' => 'nullable|string|max:255',
            'tin_number' => 'nullable|string|max:50|unique:payment_infos,tin_number',
            'ssnit_number' => 'nullable|string|max:50|unique:payment_infos,ssnit_number',   
        ];
    }
}
