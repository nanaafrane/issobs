<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    //
    protected $fillable = [
        'description',
        'amount',
        'user_1',
        'status_1',
        'date_1',
        'user_2',
        'status_2',
        'date_2',
        'user_3',
        'status_3',
        'image',
    ];

    protected $casts = [
        'date_1' => 'datetime',
        'date_2' => 'datetime',
    ];



}
