<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'name',
        'phone_number',
        'business_name',
        'branch',
        'address',
        'gps',
        'map',
        'field_id',
        'user_id'
    ];


    public function field(){
        return $this->belongsTo(Field::class);
    }




}
