<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'allowances',
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

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }   

    public function paymentInfo()
    {
        return $this->hasOne(PaymentInfo::class);
    }

    public function salary()
    {
        return $this->hasMany(Salary::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // public function clients() : BelongsToMany
    // {
    //     return $this->belongsToMany(Client::class);
    // }
    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }
    




}
