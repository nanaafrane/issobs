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
        'counts_as_guards',
        'description',
        'quantity',
        'unit_price',
        'amount',

    ];

    protected $casts = [
        'counts_as_guards' => 'boolean',
    ];

    /** Only lines whose quantity is headcount (excludes deductions such as ABSENT DEDUCTION). */
    public function scopeGuards($query)
    {
        return $query->where('counts_as_guards', true);
    }

    // Each detail belongs to one Invoice
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class); 
    }



}
