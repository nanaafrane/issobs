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
        'client_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function salaries() 
    {
    return $this->hasMany(Salary::class);
    }


}
