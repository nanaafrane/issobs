<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceData extends Model
{
    //
        protected $fillable = [
        'invoice_id',
        'service_name',
        'description',
        'quantity',
        'unit_price',
        'amount',

    ];

    // Each detail belongs to one Invoice
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class); 
    }



}
