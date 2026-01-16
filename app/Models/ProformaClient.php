<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProformaClient extends Model
{
    //
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
        'user_id'
    ];
    
    public function field(){
        return $this->belongsTo(Field::class);
    }

}
