<?php

namespace App\Http\Requests;

use App\Support\Attendance\ShiftPlans;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
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
        // Shift plan fields are optional here: leaving both guard counts empty keeps the current plan.
        return [
            'email' => ['nullable', 'email', 'max:255'],
            'auto_apply_credit' => ['nullable', 'boolean'],
        ] + ShiftPlans::rules(false) + ['effective_month' => ['nullable', 'date_format:Y-m']];
    }
}
