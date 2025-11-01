<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Bank;
class BankDeposit extends Model
{
    //
    protected $fillable = [
        'bank_id',
        'user_id',
        // 'dpst_name',
        // 'reason',
        'cash_amount',
        'cheque_amount',
        'total'
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
