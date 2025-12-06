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
            'name' => 'required|string|max:255',
            'gender'=> 'required|string|in:male,female',
            'phone_number'=> 'required|string|min:10',
            'date_of_birth'=> 'required|date',
            'nia_number'=> 'required|string|max:50',
            'address'=> 'required|string|max:500',
            'marital_status'=> 'required|string|in:single,married,divorced,widowed',
            'worker_type'=> 'required|string|in:employee,contractor',
            'date_of_joining'=> 'required|date',
            'department_id'=> 'required|exists:departments,id',
            'role_id'=> 'required|exists:roles,id',
            'field_id'=> 'required|exists:fields,id',
            'client_id'=> 'nullable|exists:clients,id',
            'location'=> 'required|string|max:255',
            'basic_salary'=> 'required|numeric|min:0',
            'allowances'=> 'required|numeric|min:0',
            'payment_type'=> 'required|string|in:cash,bank',
            'gurantor_name'=> 'required|string|max:255',
            'gurantor_number'=> 'required|string|max:20',
            'gurantor_address'=> 'required|string|max:500',
            'gurantor_nia_number'=> 'required|string|max:50',
            'relationship'=> 'required|string|max:100',
        ];
    }
}
