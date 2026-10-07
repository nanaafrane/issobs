<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * How much of a receipt went to one invoice.
 *
 * settled = amount_applied + wht_amount + vat7_amount + deduction_amount
 * and an invoice's outstanding amount is invoice.total - SUM(settled).
 */
class ReceiptAllocation extends Model
{
    protected $fillable = [
        'receipt_id',
        'invoice_id',
        'client_id',
        'amount_applied',
        'wht_amount',
        'vat7_amount',
        'deduction_amount',
        'settled',
        'source',
        'user_id',
    ];

    protected $casts = [
        'amount_applied' => 'float',
        'wht_amount' => 'float',
        'vat7_amount' => 'float',
        'deduction_amount' => 'float',
        'settled' => 'float',
    ];

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
