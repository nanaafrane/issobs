<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankDeposit extends Model
{
    //
    protected $fillable = [
        'bank_id',
        'user_id',
        'dpst_name',
        'reason',
        'cash',
        'momo',
        'cheque',
        'amount'
    ];
}
