<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Model;

class OvertimeAttendanceLink extends Model
{
    protected $fillable = ['overtime_id', 'role', 'employee_id', 'client_id', 'work_date', 'shift', 'attendance_id', 'state', 'previous', 'message'];

    protected $casts = ['work_date' => DateOnly::class, 'previous' => 'array'];

    public function overtime()
    {
        return $this->belongsTo(Overtime::class)->withTrashed();
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function employee()
    {
        return $this->belongsTo(employee::class);
    }
}
