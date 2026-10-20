<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One bank slip. total = amount on the slip; gross_amount = cash + old cheque +
 * MoMo before the MoMo withdrawal charge. See App\Services\Banking\DepositBook.
 */
class BankDeposit extends Model
{
    protected $fillable = [
        'bank_id',
        'user_id',
        'cash_amount',
        'cheque_amount',
        'momo_amount',
        'momo_charge',
        'gross_amount',
        'total',
        'deposit_date',
        'slip_number',
        'depositor',
        'notes',
    ];

    protected $casts = [
        'deposit_date' => 'date',
        'reversed_at' => 'datetime',
    ];

    public function setDepositDateAttribute($value): void
    {
        $this->attributes['deposit_date'] = $value ? \Carbon\Carbon::parse($value)->toDateString() : null;   // plain date in every database
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function bankLines()
    {
        return $this->hasMany(BankTransaction::class, 'deposit_id')->orderBy('id');
    }

    /** Collections whose cash or MoMo went on this slip. */
    public function collections()
    {
        return Collection::where('cash_deposit_id', $this->id)->orWhere('momo_deposit_id', $this->id);
    }

    /** The day it went to the bank (old deposits: the day it was entered). */
    public function getDayAttribute()
    {
        return $this->deposit_date ?? $this->created_at;
    }
}
