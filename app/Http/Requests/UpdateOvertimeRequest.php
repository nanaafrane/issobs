<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOvertimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entry_date' => 'required|date|before_or_equal:today',
            'shift' => 'required|in:day,night',
            'field_id' => 'required|exists:fields,id',

            'absent_employee_id' => 'nullable|exists:employees,id',
            'absent_employee_note' => 'nullable|string|max:255|required_without:absent_employee_id',

            'client_id' => 'nullable|exists:clients,id',
            'client_site_note' => 'nullable|string|max:255|required_without:client_id',

            'ot_employee_id' => 'required|exists:employees,id',
            'officer_id' => 'nullable|exists:employees,id',

            'reason' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'phone_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'absent_employee_note.required_without' => 'Select the absent guard, or type a short note (e.g. "Shortage").',
            'client_site_note.required_without' => 'Select a client, or type the guard post / site name.',
        ];
    }
}
