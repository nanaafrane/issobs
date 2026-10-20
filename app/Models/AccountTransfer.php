<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Money moved between two of our own accounts (e.g. a cheque from GCB to fund Main Cash). */
class AccountTransfer extends Model
{
    public const METHODS = [
        'cheque' => 'Cheque',
        'transfer' => 'Bank transfer',
        'withdrawal' => 'Cash withdrawal',
        'deposit' => 'Cash paid into bank',
        'handover' => 'Cash handed over',
    ];

    protected $fillable = ['batch', 'from_bank_id', 'to_bank_id', 'amount', 'method', 'reference', 'transfer_date', 'notes', 'user_id'];

    protected $casts = [
        'transfer_date' => 'date',
        'reversed_at' => 'datetime',
    ];

    public function setTransferDateAttribute($value): void
    {
        $this->attributes['transfer_date'] = $value ? \Carbon\Carbon::parse($value)->toDateString() : null;   // plain date in every database
    }

    public function from()
    {
        return $this->belongsTo(Bank::class, 'from_bank_id');
    }

    public function to()
    {
        return $this->belongsTo(Bank::class, 'to_bank_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }
}
