<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One hire, termination, re-instatement or office transfer. See App\Support\EmployeeMovements. */
class EmployeeMovement extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'effective_date' => 'date',
        'date_estimated' => 'boolean',
        'requested_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class);
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function fromField()
    {
        return $this->belongsTo(Field::class, 'from_field_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
