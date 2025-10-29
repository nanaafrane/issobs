<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReceiptRequest extends FormRequest
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
            // 'status' => ['in:completed, uncompleted'],
            'status' => ['required', Rule::in(['completed', 'uncompleted'])],
            'mode' => ['required', Rule::in(['cheque', 'transfer', 'momo', 'cash'])],
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}
