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
            'status' => ['required', Rule::in(['completed', 'uncompleted'])],
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
                    // Mode is now an array of one or more values.
            'mode'           => ['required', 'array', 'min:1'],
            'mode.*'         => ['string', 'in:cheque,transfer,momo,cash,other payments'],

            // Each detail field is only required if its matching mode
            // was actually selected. Rule::requiredIf covers this cleanly.
            'cheque_reference'   => [\Illuminate\Validation\Rule::requiredIf(fn () => in_array('cheque', $this->input('mode', [])))],
            'cheque_amount'      => [\Illuminate\Validation\Rule::requiredIf(fn () => in_array('cheque', $this->input('mode', []))), 'nullable', 'numeric'],
            'cheque_bank'        => [\Illuminate\Validation\Rule::requiredIf(fn () => in_array('cheque', $this->input('mode', [])))],

            'transfer_reference' => [\Illuminate\Validation\Rule::requiredIf(fn () => in_array('transfer', $this->input('mode', [])))],
            'transfer_amount'    => [\Illuminate\Validation\Rule::requiredIf(fn () => in_array('transfer', $this->input('mode', []))), 'nullable', 'numeric'],
            'transfer_bank'      => [\Illuminate\Validation\Rule::requiredIf(fn () => in_array('transfer', $this->input('mode', [])))],

            'momo_transactin_id' => [\Illuminate\Validation\Rule::requiredIf(fn () => in_array('momo', $this->input('mode', [])))],
            'momo_amount'        => [\Illuminate\Validation\Rule::requiredIf(fn () => in_array('momo', $this->input('mode', []))), 'nullable', 'numeric'],

            'other_payment_descri' => [\Illuminate\Validation\Rule::requiredIf(fn () => in_array('other payments', $this->input('mode', [])))],
            'other_payment_amnt'   => [\Illuminate\Validation\Rule::requiredIf(fn () => in_array('other payments', $this->input('mode', []))), 'nullable', 'numeric'],

            'cash_amount'        => [\Illuminate\Validation\Rule::requiredIf(fn () => in_array('cash', $this->input('mode', []))), 'nullable', 'numeric'],

        ];
    }
}
