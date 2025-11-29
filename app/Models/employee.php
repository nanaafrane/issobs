<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class employee extends Model
{
    //

    protected $fillable = [
        'image',  
        'name',
        'gender',
        'phone_number',
        'date_of_birth',
        'nia_number',
        'address',
        'marital_status',
        'worker_type',
        'date_of_joining',

        'department_id',
        'role_id',
        'field_id',
        'client_id',
        'location', 
        'basic_salary',
        'user_id',

        'status',
        'payment_type',
        'payment_infos_id',
        'salary_id',

        'gurantor_name',
        'gurantor_number',
        'gurantor_address',
        'gurantor_nia_number',
        // 'gurantor_nia_image',
        'relationship',
        
    ];


    protected $casts = [
        'date_of_birth' => 'date',
        'date_of_joining' => 'date',    
    ];
}
