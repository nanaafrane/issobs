<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    //
    protected $fillable = [
        'user_id',
        'cash_amount',
        'momo_amount',
        'cheque_amount',
        'transfer_amount',
        'total_amount',
        'field_id',
    ];


    public function field()
    {
        return $this->belongsTo(Field::class);
    }
}
