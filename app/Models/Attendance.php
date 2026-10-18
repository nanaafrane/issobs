<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Model;

/**
 * One guard, one date, one shift. A night shift that runs past midnight belongs
 * to the date it started on (see App\Support\Attendance\ShiftClock).
 */
class Attendance extends Model
{
    public const PRESENT = 'present';
    public const ABSENT = 'absent';
    public const OFF = 'off';
    public const SICK = 'sick';
    public const PASS = 'pass';
    public const TERMINATED = 'terminated';   // guard left / was terminated: not paid, not counted as absent
    public const PENDING = 'pending';   // scan held for operations review; not counted until approved

    /** Statuses operations can set by hand, with the code shown in the monthly grid. */
    public const CODES = [
        self::PRESENT => 'P',
        self::ABSENT => 'A',
        self::OFF => 'OFF',
        self::SICK => 'S',
        self::PASS => 'PS',
        self::TERMINATED => 'T',
        self::PENDING => '?',
    ];

    public const LABELS = [
        self::PRESENT => 'Present',
        self::ABSENT => 'Absent',
        self::OFF => 'Off Duty',
        self::SICK => 'Sick',
        self::PASS => 'Pass',
        self::TERMINATED => 'Terminated',
        self::PENDING => 'Awaiting review',
    ];

    /** Days that count as paid (the time sheet's legend gives OFF DAY, SICK and PASS a 1). */
    public const PAID = [self::PRESENT, self::OFF, self::SICK, self::PASS];

    public const SHIFTS = ['day', 'night'];

    protected $fillable = [
        'client_id', 'moved_from_client_id', 'employee_id', 'work_date', 'shift', 'status', 'reason', 'source', 'overtime_id',
        'checked_in_at', 'latitude', 'longitude', 'accuracy_m', 'distance_m',
        'device_id', 'ip', 'flags', 'note', 'marked_by', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'work_date' => DateOnly::class,
        'checked_in_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'flags' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /** The guard's own post, when they were moved here for the shift. */
    public function movedFrom()
    {
        return $this->belongsTo(Client::class, 'moved_from_client_id');
    }

    public function employee()
    {
        return $this->belongsTo(employee::class);
    }

    /** Set while an overtime entry owns this row (see OvertimeAttendanceSync). */
    public function overtime()
    {
        return $this->belongsTo(Overtime::class)->withTrashed();
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPaid(): bool
    {
        return in_array($this->status, self::PAID, true);
    }
}
