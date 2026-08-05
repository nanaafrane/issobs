<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryTopUpsRequest extends FormRequest
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
            // 'payment_type'=> 'nullable|in:Cash,Bank',
            // 'top_up_amount'=> 'nullable|numeric|min:0',
            // 'reason'=> 'nullable|string',

        ];
    }
}
