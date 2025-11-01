<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Bank;

class BankTransaction extends Model
{
    //
    protected $fillable = [
        'bank_id',
        'credit',
        'deposit_id',
        'receipt_id',
        'debit',
        'expense_id',
        'balance'
    ];


    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
