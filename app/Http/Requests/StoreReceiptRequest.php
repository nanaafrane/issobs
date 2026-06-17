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
            'mode' => ['required', Rule::in(['cheque', 'transfer', 'momo', 'cash', 'other payments'])],
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

        ];
    }
}
