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
        'employee_id',
        'tin_number',
        'ssnit_number',
    ];
}
