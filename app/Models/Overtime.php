<?php

namespace App\Models;

use App\Support\Attendance\ShiftReasons;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Overtime extends Model
{
    use SoftDeletes;

    /** Same list as attendance (see ShiftReasons): Absent / Sick / Off Duty / Pass also mark the named guard. */
    public const REASONS = ShiftReasons::ALL;

    public const STAGE_LABELS = [
        1 => 'Submitted',
        2 => 'Branch Review',
        3 => 'Finance / H.O Approval',
    ];

    protected $fillable = [
        'entry_date', 'shift', 'field_id',
        'absent_employee_id', 'absent_employee_note',
        'client_id', 'client_site_note',
        'ot_employee_id', 'officer_id',
        'reason', 'amount', 'phone_number', 'notes',
        'user_1', 'status_1', 'date_1',
        'user_2', 'status_2', 'date_2',
        'user_3', 'status_3', 'date_3',
        'expense_id',
    ];

    public const DISBURSE_METHODS = [
        'cash' => 'Cash',
        'momo' => 'MoMo',
        'payroll' => 'With salary',
        'payment' => 'Paid from Bank & cash (before pay-outs)',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount' => 'decimal:2',
        'date_1' => 'datetime',
        'date_2' => 'datetime',
        'date_3' => 'datetime',
        'disbursed_at' => 'date',
    ];

    public function setDisbursedAtAttribute($v): void
    {
        $this->attributes['disbursed_at'] = $v ? \Carbon\Carbon::parse($v)->toDateString() : null;
    }

    public function payout()
    {
        return $this->belongsTo(OvertimePayout::class, 'payout_id');
    }

    /** Approved, not on a pay-out and not paid: money the company owes the guard. */
    public function scopeOwed($q)
    {
        return $q->where('status_3', 'approved')->whereNull('payout_id')->whereNull('disbursed_at');
    }

    /** On a pay-out or already paid: amount, guard and date can no longer change. */
    public function isLockedForPay(): bool
    {
        return (bool) $this->payout_id || (bool) $this->disbursed_at;
    }

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /** The regular/absent guard whose post is being covered (may be null - "SHORTAGE") */
    public function absentEmployee()
    {
        return $this->belongsTo(employee::class, 'absent_employee_id');
    }

    /** The guard who actually worked and is being paid the overtime */
    public function otEmployee()
    {
        return $this->belongsTo(employee::class, 'ot_employee_id');
    }

    /** Supervisor / officer on duty who authorised the entry */
    public function officer()
    {
        return $this->belongsTo(employee::class, 'officer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_1');
    }

    public function branchReviewer()
    {
        return $this->belongsTo(User::class, 'user_2');
    }

    public function financeApprover()
    {
        return $this->belongsTo(User::class, 'user_3');
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    /** What this entry wrote to the attendance sheet (see OvertimeAttendanceSync). */
    public function attendanceLinks()
    {
        return $this->hasMany(OvertimeAttendanceLink::class);
    }

    // ---------------------------------------------------------------
    // Approval helpers (mirrors Expense::currentStage / canActOnStage)
    // ---------------------------------------------------------------

    public function currentStage(): ?int
    {
        if ($this->status_1 !== 'approved') {
            return null;
        }
        if (is_null($this->status_2)) {
            return 2;
        }
        if ($this->status_2 === 'rejected') {
            return null;
        }
        if (is_null($this->status_3)) {
            return 3;
        }

        return null;
    }

    public function isFullyApproved(): bool
    {
        return $this->status_3 === 'approved';
    }

    public function isRejected(): bool
    {
        return in_array('rejected', [$this->status_1, $this->status_2, $this->status_3], true);
    }

    public function approvalTimeline(): array
    {
        $stages = [];
        foreach ([1, 2, 3] as $n) {
            $status = $this->{"status_{$n}"};
            $stages[] = [
                'stage' => $n,
                'label' => self::STAGE_LABELS[$n],
                'status' => $status ?? ($n === 1 ? 'pending' : 'waiting'),
                'date' => $this->{"date_{$n}"},
            ];
        }

        return $stages;
    }

    public function canActOnStage($user, int $stage): bool
    {
        $role = $user->role?->name ?? null;

        if ($stage === 2) {
            if ($role === 'Field Manager' || $role === 'Manager') {
                return $this->field_id && $user->field_id
                    && ($this->field?->groupId() === $user->field?->groupId());
            }

            return in_array($role, ['Finance Manager', 'Director'], true);
        }

        if ($stage === 3) {
            return in_array($role, ['Finance Manager', 'Director'], true);
        }

        return false;
    }

    public function isEditableBy($user): bool
    {
        if ($this->isLockedForPay()) {
            return false;   // take it off its pay-out first (only before it is paid)
        }
        if (in_array($user->role?->name ?? null, ['Finance Manager', 'Director'], true)) {
            return true;
        }

        return $this->user_1 === $user->id && is_null($this->status_2);
    }

    // ---------------------------------------------------------------
    // Display helpers
    // ---------------------------------------------------------------

    public function absentLabel(): string
    {
        return $this->absentEmployee?->name ?: ($this->absent_employee_note ?: '-');
    }

    public function clientLabel(): string
    {
        return $this->client?->business_name ?: ($this->client?->name ?: ($this->client_site_note ?: '-'));
    }
}
