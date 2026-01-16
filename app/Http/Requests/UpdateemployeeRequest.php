<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateemployeeRequest extends FormRequest
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'name' => 'nullable|string|max:255',
            'gender'=> 'nullable|string|in:male,female',
            'phone_number'=> 'nullable|string|min:10',
            'date_of_birth'=> 'nullable|date',
            'nia_number'=> 'nullable|string|max:50',
            'address'=> 'nullable|string|max:500',
            'marital_status'=> 'nullable|string|in:single,married,divorced,widowed',
            'worker_type'=> 'nullable|string|in:employee,contractor',
            'date_of_joining'=> 'nullable|date',
            'department_id'=> 'nullable|exists:departments,id',
            'role_id'=> 'nullable|exists:roles,id',
            'field_id'=> 'nullable|exists:fields,id',
            'client_id'=> 'nullable|exists:clients,id',
            'location'=> 'nullable|string|max:255',
            'basic_salary'=> 'nullable|numeric|min:0',
            'allowances'=> 'nullable|numeric|min:0',
            'payment_type'=> 'nullable|string|in:cash,bank',
            'gurantor_name'=> 'nullable|string|max:255',
            'gurantor_number'=> 'nullable|string|max:20',
            'gurantor_address'=> 'nullable|string|max:500',
            'gurantor_nia_number'=> 'nullable|string|max:50',
            'relationship'=> 'nullable|string|max:100',
        ];
    }
}
