<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProformaClientRequest extends FormRequest
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
        'name' => 'nullable',
        'phone_number' => 'nullable',
        'phone_number1'=> 'nullable',
        'business_name'=> 'nullable',
        'branch'=> 'nullable',
        'address'=> 'nullable',
        'gps'=> 'nullable',
        'map'=> 'nullable',
        'field_id'=> 'nullable',
        ];
    }
}
