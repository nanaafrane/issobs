<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

/**
 * Validation for the payment-mode part of every receipt form (single
 * invoice create, edit, and multi-invoice). Each mode's details are only
 * required when that mode is ticked.
 */
trait PaymentModeRules
{
    protected function paymentModeRules(): array
    {
        $when = fn (string $mode) => Rule::requiredIf(fn () => in_array($mode, (array) $this->input('mode', []), true));

        return [
            'mode' => ['required', 'array', 'min:1'],
            'mode.*' => ['string', 'in:cheque,transfer,momo,cash,other payments'],

            'cheque_reference' => [$when('cheque')],
            'cheque_amount' => [$when('cheque'), 'nullable', 'numeric', 'min:0'],
            'cheque_bank' => [$when('cheque')],
            // Which of OUR accounts the cheque will be deposited into.
            'cheque_to_bank_id' => [$when('cheque'), 'nullable', 'integer', 'exists:banks,id'],

            'transfer_reference' => [$when('transfer')],
            'transfer_amount' => [$when('transfer'), 'nullable', 'numeric', 'min:0'],
            'transfer_bank' => [$when('transfer')],
            // Which of OUR accounts the transfer landed in.
            'transfer_to_bank_id' => [$when('transfer'), 'nullable', 'integer', 'exists:banks,id'],

            'momo_transactin_id' => [$when('momo')],
            'momo_amount' => [$when('momo'), 'nullable', 'numeric', 'min:0'],

            'other_payment_descri' => [$when('other payments')],
            'other_payment_amnt' => [$when('other payments'), 'nullable', 'numeric', 'min:0'],

            'cash_amount' => [$when('cash'), 'nullable', 'numeric', 'min:0'],
        ];
    }

    protected function paymentModeAttributes(): array
    {
        return [
            'cheque_to_bank_id' => 'bank the cheque will be deposited into',
            'transfer_to_bank_id' => 'bank the transfer was received into',
            'transfer_bank' => "payer's bank (transfer)",
            'cheque_bank' => "payer's bank (cheque)",
        ];
    }
}
