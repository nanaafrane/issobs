<?php

namespace App\Http\Requests;

use App\Support\Attendance\ShiftPlans;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
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
                // 'name' => ['required', 'string', 'max:255'],
                'phone_number' => ['required', 'min:10', 'max:10'],
                // 'business_name' => ['required'],
                // 'address' => ['required'],
                'field_id' => 'required',
                'rate' => ['required', 'numeric', 'min:1000'],
                'start_date' => 'required',
                'email' => ['nullable', 'email', 'max:255'],
                'auto_apply_credit' => ['nullable', 'boolean'],
        ] + ShiftPlans::rules();
    }

    /** "Guards" is now the day + night guards of the shift plan. */
    protected function prepareForValidation(): void
    {
        $day = $this->input('day_guards');
        $night = $this->input('night_guards');
        if (is_numeric($day) || is_numeric($night)) {
            $this->merge(['guards' => (string) ((int) $day + (int) $night)]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ((int) $this->input('day_guards') + (int) $this->input('night_guards') < 1) {
                $v->errors()->add('day_guards', 'The client needs at least one day or night guard.');
            }
        });
    }
}
