<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Service extends Model
{
    //
    protected $fillable = [
        'name',
        'counts_as_guards',
        'user_id',
    ];

    protected $casts = [
        'counts_as_guards' => 'boolean',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
