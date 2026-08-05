<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryTopUps extends Model
{
    //
    protected $fillable = [
        'salary_id',
        'salary_month',
        'field_id',
        'user_id',
        'payment_type',
        'top_up_amount',
        'reason',
        'status',
        'status_date',
        'user_id1',
        'status_date2',
        'user_id2',
    ];

    protected $casts = [
        'salary_month' => 'date',
        'status_date' => 'date',
        'status_date2' => 'date',
    ];

    public function salary()
    {
        return $this->belongsTo(Salary::class, 'salary_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user1()
    {
        return $this->belongsTo(User::class, 'user_id1');
    }

    public function user2()
    {
        return $this->belongsTo(User::class, 'user_id2');
    }

    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }




}
