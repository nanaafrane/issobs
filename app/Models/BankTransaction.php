<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One line on an account: credit = money in, debit = money out.
 * channel says what moved it: cheque | transfer (receipt) | deposit | momo_charge |
 * transfer_in | transfer_out (between our accounts) | payment | adjustment | expense (old).
 * entry_date is the day the money moved (defaults to the day it is recorded).
 */
class BankTransaction extends Model
{
    protected $fillable = [
        'bank_id',
        'entry_date',
        'credit',
        'deposit_id',
        'transfer_id',
        'payment_id',
        'creditor_entry_id',
        'receipt_id',
        'channel',
        'debit',
        'expense_id',
        'balance',
        'narration',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    public function setEntryDateAttribute($value): void
    {
        $this->attributes['entry_date'] = $value ? \Carbon\Carbon::parse($value)->toDateString() : null;   // plain date in every database
    }

    protected static function booted(): void
    {
        static::creating(function (BankTransaction $t) {
            $t->entry_date ??= now()->toDateString();
        });
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function payment()
    {
        return $this->belongsTo(AccountPayment::class, 'payment_id');
    }

    public function transfer()
    {
        return $this->belongsTo(AccountTransfer::class, 'transfer_id');
    }
}
