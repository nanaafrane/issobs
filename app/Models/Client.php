<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'name',
        'phone_number',
        'phone_number1',
        'business_name',
        'branch',
        'address',
        'gps',
        'map',
        'field_id',
        'status',
        'user_id',
        'category_name',
        'category_month',
    ];


    public function field(){
        return $this->belongsTo(Field::class);
    }

    public function employees() : BelongsToMany
    {
        return $this->belongsToMany(employee::class);
    }


    public function invoices () : HasMany
    {
        return $this->hasMany(Invoice::class);
    }


}
