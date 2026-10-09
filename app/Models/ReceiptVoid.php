<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A receipt that was voided: the reason, who did it, and a copy of everything it recorded. */
class ReceiptVoid extends Model
{
    protected $fillable = ['receipt_id', 'client_id', 'amount', 'reason', 'voided_by', 'snapshot'];

    protected $casts = [
        'snapshot' => 'array',
        'amount' => 'float',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
