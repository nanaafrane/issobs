<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentInfo extends Model
{
    //
    protected $fillable = [
        'bank_id',
        'acc_number',
        'branch',
        'branch_code',
        'employee_id',
        'tin_number',
        'ssnit_number',
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
    public function employee()
    {
        return $this->belongsTo(employee::class);
    }

}
