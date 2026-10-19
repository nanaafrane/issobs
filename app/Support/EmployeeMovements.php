<?php

namespace App\Support;

use App\Models\employee;
use App\Models\EmployeeMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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

    /**
     * Who decides terminations / re-instatements at once (and approves other people's
     * requests): head office (role 1, Invoice) and an HR manager - the same people who
     * approve on the pending employees page. Anyone else's request waits for them.
     */
    public static function canDecide(?User $user): bool
    {
        return $user !== null && ((string) $user->role?->id === '1' || $user->hasRole(['Invoice'])
            || ($user->hasRole(['Manager']) && $user->hasPermission('HR')));
    }

    /** The head-office user a request is assigned to when the requester picks nobody. */
    public static function defaultApprover(): ?int
    {
        $id = User::where('department_id', '1')->where('role_id', '1')->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * Terminate or re-instate an employee: the ONE place both happen, whoever asks
     * (employee page, attendance sheet, overtime). A decider's change is approved at once;
     * anyone else's is a request: the employee shows as pending on the pending employees
     * page until head office / HR approves or declines it.
     *
     * @param array $extra reason, note, source, previous_status
     */
    public static function changeStatus(employee $e, string $type, string $date, array $extra, ?User $by, ?int $approverId = null): EmployeeMovement
    {
        $terminate = $type === self::TERMINATION;
        $decides = self::canDecide($by);

        return DB::transaction(function () use ($e, $type, $date, $extra, $by, $approverId, $terminate, $decides) {
            $previous = $e->status;
            $e->status_date = $date;
            $e->user_id1 = $by?->id;
            if ($decides) {
                $e->status = $terminate ? 'Terminated' : 'Active';
                $e->ho_status = 'approved';
                $e->ho_date = now()->toDateString();
                $e->user_id2 = $by->id;
            } else {
                $e->status = $terminate ? 'Terminated' : 'Re-Instate';
                $e->ho_status = 'pending';
                $e->user_id2 = $approverId ?: self::defaultApprover();
                $e->bran_status = 'approved';
                $e->bran_date = now()->toDateString();
            }
            $e->save();

            return self::record($e, $type, $decides ? self::APPROVED : self::PENDING, $date, $extra + [
                'previous_status' => $previous,
                'user_id' => $by?->id,
            ]);
        });
    }

    /**
     * Undo a termination / re-instatement entered by mistake. The movement is kept, marked
     * cancelled with who undid it and why, and counts in nothing - so a wrong termination is
     * NOT reported as a termination plus a re-instatement. Only the employee's latest status
     * change can be undone, and only while their status still reflects it.
     *
     * @return string|null an error message, or null when done
     */
    public static function undo(EmployeeMovement $m, User $by, string $why): ?string
    {
        if (! self::canDecide($by)) {
            return 'Only head office or HR can undo a termination or re-instatement.';
        }
        if (! in_array($m->type, [self::TERMINATION, self::REINSTATEMENT], true)) {
            return 'Only a termination or a re-instatement can be undone.';
        }
        $e = employee::find($m->employee_id);
        if (! $e) {
            return 'The employee no longer exists.';
        }
        if ($m->status === self::PENDING) {
            return self::withdraw($m, $by, $why);
        }
        if ($m->status !== self::APPROVED) {
            return 'This ' . strtolower(self::LABELS[$m->type]) . ' is already ' . $m->status . '.';
        }
        $latest = EmployeeMovement::where('employee_id', $e->id)->whereIn('type', [self::TERMINATION, self::REINSTATEMENT])
            ->whereIn('status', [self::APPROVED, self::PENDING])->latest('id')->first();
        if ($latest?->id !== $m->id) {
            return 'Only the latest termination or re-instatement can be undone; this one has been followed by another.';
        }
        $expected = $m->type === self::TERMINATION ? 'Terminated' : 'Active';
        if ($e->status !== $expected) {
            return $e->name . ' is now ' . ($e->status ?: 'without a status') . ', so this cannot be undone here.';
        }

        DB::transaction(function () use ($m, $e, $by, $why) {
            $m->update(['status' => self::CANCELLED, 'decided_by' => $by->id, 'decided_at' => now(),
                'note' => mb_substr(trim(($m->note ? $m->note . ' · ' : '') . 'Undone: ' . $why), 0, 255)]);
            $e->status = $m->type === self::TERMINATION ? ($m->previous_status && $m->previous_status !== 'Terminated' ? $m->previous_status : 'Active') : 'Terminated';
            if ($e->status === 'Re-Instate' || $e->status === 'Pending') {
                $e->status = 'Active';
            }
            $e->ho_status = 'approved';
            $e->status_date = null;
            $e->save();
        });

        return null;
    }

    /** Withdraw a request still waiting for head office (requester or a decider). Error message or null. */
    public static function withdraw(EmployeeMovement $m, User $by, string $why = ''): ?string
    {
        if ($m->status !== self::PENDING || ! in_array($m->type, [self::TERMINATION, self::REINSTATEMENT], true)) {
            return 'Only a termination or re-instatement request still waiting for approval can be withdrawn.';
        }
        if (! self::canDecide($by) && (int) $m->requested_by !== (int) $by->id) {
            return 'Only the person who asked, head office or HR can withdraw this request.';
        }
        $e = employee::find($m->employee_id);
        DB::transaction(function () use ($m, $e, $by, $why) {
            $m->update(['status' => self::CANCELLED, 'decided_by' => $by->id, 'decided_at' => now(),
                'note' => mb_substr(trim(($m->note ? $m->note . ' · ' : '') . 'Withdrawn' . ($why !== '' ? ': ' . $why : '')), 0, 255)]);
            if ($e) {
                $e->status = $m->previous_status ?: ($m->type === self::TERMINATION ? 'Active' : 'Terminated');
                $e->ho_status = 'approved';
                $e->save();
            }
        });

        return null;
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

        if (in_array($type, [self::TERMINATION, self::REINSTATEMENT], true) && $effectiveDate) {
            self::alignEstimatedHire($e->id, Carbon::parse($effectiveDate)->toDateString());
        }
        if ($type === self::TERMINATION && $effectiveDate) {
            // Status is Active (only Active staff can be terminated): make the history agree.
            self::ensureEmployedBefore($e, Carbon::parse($effectiveDate)->toDateString());
        }

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

    /**
     * A hire whose date was only guessed can't be after a termination / re-instatement:
     * move it back to that date (still marked estimated). A real date of joining is never
     * moved: the report lists the clash for HR to correct instead.
     */
    public static function alignEstimatedHire(int $employeeId, string $date): void
    {
        EmployeeMovement::where('employee_id', $employeeId)->where('type', self::HIRE)->where('date_estimated', true)
            ->whereNotNull('effective_date')->where('effective_date', '>', $date)->update(['effective_date' => $date]);
    }

    /**
     * Before a termination dated $date counts, the history must show the employee employed on
     * that date. Someone taken back on before re-instatement had its own step still ends in
     * an older termination; someone with no hire at all has no start. Fill the gap so the new
     * termination counts: an estimated re-instatement (the old "New Recruit" date when there
     * is one, else the day after the old termination), or an undated hire. A genuine clash
     * (a real joining date after $date) is left for HR to correct.
     *
     * @return EmployeeMovement|null what was added
     */
    public static function ensureEmployedBefore(employee $e, string $date): ?EmployeeMovement
    {
        $last = EmployeeMovement::where('employee_id', $e->id)->where('status', self::APPROVED)
            ->whereIn('type', [self::HIRE, self::REINSTATEMENT, self::TERMINATION])
            // Up to $date - but not the termination(s) on $date itself, which are what we check for.
            ->where(fn ($q) => $q->whereNull('effective_date')->orWhere('effective_date', '<', $date)
                ->orWhere(fn ($w) => $w->where('effective_date', $date)->where('type', '!=', self::TERMINATION)))
            ->orderByRaw('effective_date IS NOT NULL')->orderBy('effective_date')->orderBy('id')->get()->last();

        if (! $last) {
            if (EmployeeMovement::where('employee_id', $e->id)->where('type', self::HIRE)->where('status', self::APPROVED)->exists()) {
                return null;   // hired after $date: a real clash, listed for HR
            }
            // No approved start at all (old record): employed from before any period.
            $waitingHire = EmployeeMovement::where('employee_id', $e->id)->where('type', self::HIRE)->where('status', self::PENDING)->first();
            if ($waitingHire) {
                // Their hire was never approved, yet they are employed: approve it.
                $waitingHire->update(['status' => self::APPROVED, 'decided_at' => now()]);

                return $waitingHire;
            }

            return self::record($e, self::HIRE, self::APPROVED, null, ['source' => 'history_fix', 'date_estimated' => true,
                'note' => 'Joining not on record; added so a later termination counts.']);
        }
        if ($last->type !== self::TERMINATION) {
            return null;   // employed on $date
        }
        $ended = $last->effective_date->toDateString();
        $back = \Illuminate\Support\Facades\Schema::hasTable('nrrit')
            ? DB::table('nrrit')->where('employee_id', $e->id)->where('status1', 'New Recruit')->where('status_month', '>', $ended)
                ->where('status_month', '<=', $date)->orderBy('status_month')->value('status_month') : null;
        $back = $back ? substr((string) $back, 0, 10) : Carbon::parse($ended)->addDay()->toDateString();

        return EmployeeMovement::create(array_merge(self::snapshot($e), [
            'employee_id' => $e->id, 'type' => self::REINSTATEMENT, 'status' => self::APPROVED,
            'effective_date' => $back, 'date_estimated' => true, 'source' => 'history_fix',
            'note' => 'Taken back on after the termination of ' . Carbon::parse($ended)->format('d M Y') . ' with no re-instatement on record (added).',
            'requested_at' => now(), 'decided_at' => now(),
        ]));
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
     * The employee was just approved by head office: approve the request that was waiting,
     * matching what the employee now is (Terminated -> the termination; Active -> the
     * re-instatement or the new recruit). Creates nothing; returns what it approved.
     */
    public static function approveWaiting(employee $e): ?EmployeeMovement
    {
        $type = match (true) {
            $e->status === 'Terminated' => self::TERMINATION,
            $e->status === 'Active' && self::pending($e->id, self::REINSTATEMENT) => self::REINSTATEMENT,
            $e->status === 'Active' && self::pending($e->id, self::HIRE) => self::HIRE,
            default => null,
        };

        return $type && self::pending($e->id, $type) ? self::approve($e, $type) : null;
    }

    /**
     * Head office approved this employee. Approves the waiting movement of the matching type,
     * or records an approved one if none was requested (older requests made before movements
     * existed). Returns the approved movement.
     */
    public static function approve(employee $e, string $type): EmployeeMovement
    {
        // Already approved a moment ago (the employee's own save approves what was waiting):
        // never record it twice.
        $latest = EmployeeMovement::where('employee_id', $e->id)->where('status', self::APPROVED)
            ->whereIn('type', [self::HIRE, self::TERMINATION, self::REINSTATEMENT])->latest('id')->first();
        if (! self::pending($e->id, $type) && $latest?->type === $type) {
            return $latest;
        }

        $m = self::pending($e->id, $type);
        if ($m && $type === self::TERMINATION && $m->effective_date) {
            self::ensureEmployedBefore($e, $m->effective_date->toDateString());
        }
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
