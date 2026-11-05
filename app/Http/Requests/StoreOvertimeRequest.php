<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOvertimeRequest extends FormRequest
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

            // Same list as attendance; required when a guard is named, because it decides their attendance.
            'reason' => ['nullable', 'required_with:absent_employee_id', \Illuminate\Validation\Rule::in(\App\Support\Attendance\ShiftReasons::ALL)],
            'amount' => 'required|numeric|min:0',
            'phone_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',

            // What kind of overtime, and how it is paid (App\Support\OvertimePay).
            'type' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(\App\Support\OvertimePay::TYPES))],
            'pay_by' => ['required', \Illuminate\Validation\Rule::in(array_keys(\App\Support\OvertimePay::PAY_BY))],
            'pay_note' => 'nullable|string|max:255',
        ];
    }

    /** Off-day work and shortage name nobody absent: give them their note and reason. */
    protected function prepareForValidation(): void
    {
        $type = $this->input('type');
        if (in_array($type, ['off_day', 'shortage'], true)) {
            $this->merge([
                'absent_employee_id' => null,
                'absent_employee_note' => $this->input('absent_employee_note') ?: ($type === 'off_day' ? 'OFF-DAY WORK' : 'SHORTAGE'),
                'reason' => $type === 'off_day' ? 'Off-day work' : 'Shortage',
            ]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $type = $this->input('type');
            if ($type === 'replacement' && ! $this->input('absent_employee_id')) {
                $v->errors()->add('absent_employee_id', 'Replacement: choose the guard who was absent (or pick Shortage if nobody was).');
            }
            if ($type === 'shortage' && $this->input('pay_by') === 'cash' && ! trim((string) $this->input('pay_note'))) {
                $v->errors()->add('pay_note', 'A shortage is paid with the salary. To pay it in cash, say why (exception).');
            }
        });
    }

    public function messages(): array
    {
        return [
            'absent_employee_note.required_without' => 'Select the absent guard, or type a short note (e.g. "Shortage").',
            'reason.required_with' => 'Pick a reason for the absent guard (it goes on their attendance).',
            'pay_by.required' => 'Choose how this overtime is paid: cash today, or with the salary.',
            'client_site_note.required_without' => 'Select a client, or type the guard post / site name.',
        ];
    }
}
