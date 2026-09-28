<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class category extends Model
{
    //
    protected $fillable = [
        'name',
        'user_id',
        'client_id',
        'category_month'
    ];

        /** Client ids ticked for Category A every month (only while Active and not yet categorised). */
    public const DEFAULT_A_CLIENT_IDS = [
        112, 109, 110, 343, 100, 103, 118, 440, 416, 176, 413, 135,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function salaries() 
    {
    return $this->hasMany(Salary::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }


    protected $casts = [
        'category_month' => 'date',
    ];


}
