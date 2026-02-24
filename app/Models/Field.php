<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Field extends Model
{
    //
    protected $fillable = [
        'name',
        'user_id',
        'status',
    ];

    public function user()
    {
        return $this->hasOne(User::class);
    }

}
