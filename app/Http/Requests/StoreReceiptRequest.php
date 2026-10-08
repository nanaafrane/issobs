<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Concerns\PaymentModeRules;
use Illuminate\Validation\Rule;

class StoreReceiptRequest extends FormRequest
{
    use PaymentModeRules;

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
        return array_merge([
            // Status is worked out from the payment; anything sent is ignored.
            'status' => ['nullable', Rule::in(['completed', 'uncompleted'])],
            'from' => ['required', 'string', 'max:255'],
            'receipt_month' => ['required', 'date'],
            'keep_credit' => ['nullable', 'in:1,on'],
            // 'mode' => ['required', Rule::in(['cheque', 'transfer', 'momo', 'cash', 'other payments'])],
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'invoice_id' => ['required',
                              Rule::unique('invoices', 'id')->where(function ($query) {
                              return $query->where('status', 'completed');
                           }),
                         ],
        //     'invoice_id' => [ 'required',  function (string $attribute, mixed $value, Closure $fail) 
        //                 {
        //                     $count = DB::table('invoices')
        //                         ->where('id', $value)
        //                         ->where('status', 'completed')
        //                         ->count();

        //                     if ($count > 0) {
        //                         $fail("The user has already completed the action and cannot be added again.");
        //                     }
        //                 },
        // ],
        ], $this->paymentModeRules(), $this->adjustmentRules());
    }

    public function attributes(): array
    {
        return $this->paymentModeAttributes();
    }
}
