<?php

namespace App\Support;

use App\Models\employee;
use App\Models\EmployeeMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * The ONE place that writes employee movements (hires, terminations, re-instatements,
 * office transfers). Every path that creates an employee or changes their status calls
 * this, so the HR movements report has one complete, dated, approved record.
 *
 * Rules
 *  - A request (branch terminates, branch adds a recruit, ...) is PENDING and counts in
 *    nothing until head office approves it. Head office's own actions are approved at once.
 *  - Declining a termination / re-instatement marks it declined and puts the employee's
 *    status back (EmployeeController::employeesAproval).
 *  - An employee has at most one pending movement of each type.
 *  - A re-instatement is never recorded as a new hire.
 *  - The office / client / role at the time are stored on the movement.
 */
class EmployeeMovements
{
    public const HIRE = 'hire';
    public const TERMINATION = 'termination';
    public const REINSTATEMENT = 'reinstatement';
    public const TRANSFER = 'transfer';

    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const DECLINED = 'declined';
    public const CANCELLED = 'cancelled';

    public const LABELS = [
        self::HIRE => 'New recruit',
        self::REINSTATEMENT => 'Re-instated',
        self::TERMINATION => 'Terminated',
        self::TRANSFER => 'Office transfer',
    ];

    /** Termination reasons (value => label). */
    public const REASONS = [
        'resigned' => 'Resigned',
        'dismissed' => 'Dismissed (misconduct)',
        'absconded' => 'Absconded / stopped reporting',
        'contract_ended' => 'Contract / probation ended',
        'post_closed' => 'Client post closed or reduced',
        'medical' => 'Ill health',
        'retired' => 'Retired',
        'deceased' => 'Deceased',
        'other' => 'Other',
    ];

    public static function reasonLabel(?string $reason): string
    {
        return self::REASONS[$reason] ?? ($reason ? ucfirst(str_replace('_', ' ', $reason)) : 'Not recorded');
    }

    /** Office, client, role and department of the employee right now. */
    private static function snapshot(employee $e): array
    {
        return [
            'field_id' => $e->field_id ?: null,
            'client_id' => $e->client_id ?: null,
            'role_id' => $e->role_id ?: null,
            'department_id' => $e->department_id ?: null,
        ];
    }

    /**
     * Record a movement. Approved ones are decided by the current user at once.
     *
     * @param array $extra reason, note, previous_status, from_field_id, source, date_estimated
     */
    public static function record(employee $e, string $type, string $status, $effectiveDate, array $extra = []): EmployeeMovement
    {
        $now = now();
        $userId = $extra['user_id'] ?? Auth::id();
        unset($extra['user_id']);

        return EmployeeMovement::create(array_merge(self::snapshot($e), [
            'employee_id' => $e->id,
            'type' => $type,
            'status' => $status,
            'effective_date' => $effectiveDate ? Carbon::parse($effectiveDate)->toDateString() : null,
            'requested_by' => $userId,
            'requested_at' => $now,
            'decided_by' => $status === self::APPROVED ? $userId : null,
            'decided_at' => $status === self::APPROVED ? $now : null,
        ], $extra));
    }

    public static function pending(int $employeeId, ?string $type = null): ?EmployeeMovement
    {
        return EmployeeMovement::where('employee_id', $employeeId)->where('status', self::PENDING)
            ->when($type, fn ($q) => $q->where('type', $type))->latest('id')->first();
    }

    /** The hire date to use for a new employee: date of joining, else today. */
    public static function hireDate(employee $e): string
    {
        return ($e->date_of_joining ? Carbon::parse($e->date_of_joining) : now())->toDateString();
    }

    /** A new employee was saved (add form or bulk upload). Approved when head office added them as Active. */
    public static function hired(employee $e, string $source = 'form'): EmployeeMovement
    {
        $approved = $e->status === 'Active' && $e->ho_status === 'approved';

        return self::record($e, self::HIRE, $approved ? self::APPROVED : self::PENDING, self::hireDate($e), [
            'source' => $source,
            'date_estimated' => ! $e->date_of_joining,
        ]);
    }

    /**
     * Head office approved this employee. Approves the waiting movement of the matching type,
     * or records an approved one if none was requested (older requests made before movements
     * existed). Returns the approved movement.
     */
    public static function approve(employee $e, string $type): EmployeeMovement
    {
        $m = self::pending($e->id, $type);
        if ($m) {
            // The office etc. may have been corrected while waiting: store them as approved.
            $m->fill(self::snapshot($e))->fill(['status' => self::APPROVED, 'decided_by' => Auth::id(), 'decided_at' => now()]);
            if ($type === self::HIRE && $e->date_of_joining) {
                $m->effective_date = self::hireDate($e);
                $m->date_estimated = false;
            }
            $m->save();

            return $m;
        }

        $date = match ($type) {
            self::HIRE => self::hireDate($e),
            default => ($e->status_date ? Carbon::parse($e->status_date) : now())->toDateString(),
        };

        return self::record($e, $type, self::APPROVED, $date, [
            'source' => 'approval',
            'date_estimated' => $type === self::HIRE ? ! $e->date_of_joining : ! $e->status_date,
        ]);
    }

    /** Head office declined a termination / re-instatement request. Returns it (for previous_status), or null. */
    public static function decline(employee $e): ?EmployeeMovement
    {
        $m = EmployeeMovement::where('employee_id', $e->id)->where('status', self::PENDING)
            ->whereIn('type', [self::TERMINATION, self::REINSTATEMENT])->latest('id')->first();
        if ($m) {
            $m->update(['status' => self::DECLINED, 'decided_by' => Auth::id(), 'decided_at' => now()]);
        }

        return $m;
    }

    /** Office changed on an active employee (edit form or bulk update): approved transfer, today. */
    public static function transferred(employee $e, $fromFieldId): void
    {
        self::record($e, self::TRANSFER, self::APPROVED, now(), [
            'from_field_id' => $fromFieldId ?: null,
            'source' => 'edit',
        ]);
    }
}
