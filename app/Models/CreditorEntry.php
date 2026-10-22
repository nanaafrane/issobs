<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One movement on a creditor's account. owed_change: + the company owes more, − less. */
class CreditorEntry extends Model
{
    public const KINDS = [
        'received' => 'Money received',
        'on_behalf' => 'Paid on our behalf',
        'repaid' => 'Repaid',
        'reversal' => 'Reversal',
    ];

    protected $fillable = ['creditor_id', 'entry_date', 'kind', 'owed_change', 'bank_id', 'method', 'reference', 'description', 'interest', 'interest_payment_id', 'reverses_id', 'user_id'];

    protected $casts = [
        'entry_date' => 'date',
        'reversed_at' => 'datetime',
    ];

    public function setEntryDateAttribute($v): void
    {
        $this->attributes['entry_date'] = $v ? \Carbon\Carbon::parse($v)->toDateString() : null;
    }

    public function creditor()
    {
        return $this->belongsTo(Creditor::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'creditor_entry_id');
    }

    public function interestPayment()
    {
        return $this->belongsTo(AccountPayment::class, 'interest_payment_id');
    }
}
