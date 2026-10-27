<?php

namespace App\Models\Concerns;

/**
 * The 3-stage approval chain used by overtime and expenses, shared by fuel and maintenance:
 *
 *   1 Submitted        the creator's own confirmation, set when the entry is saved
 *   2 Branch Review    Field Manager / Manager of the same office group, or Finance Manager / Director
 *   3 Finance / H.O    Finance Manager / Director
 *
 * Columns: user_n, status_n (approved | rejected | null), date_n for n = 1..3, and field_id.
 * The model decides when it is locked for pay (isLockedForPay()).
 */
trait HasApprovalStages
{
    public static array $STAGE_LABELS = [
        1 => 'Submitted',
        2 => 'Branch Review',
        3 => 'Finance / H.O Approval',
    ];

    public const FINANCE_ROLES = ['Finance Manager', 'Director'];

    abstract public function isLockedForPay(): bool;

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

    /** pending | approved | rejected — one word for lists and filters. */
    public function approvalState(): string
    {
        return $this->isRejected() ? 'rejected' : ($this->isFullyApproved() ? 'approved' : 'pending');
    }

    public function stageLabel(): string
    {
        if ($this->isRejected()) {
            return 'Rejected';
        }
        if ($this->isFullyApproved()) {
            return 'Approved';
        }
        $stage = $this->currentStage();

        return $stage ? 'Waiting: ' . self::$STAGE_LABELS[$stage] : 'Pending';
    }

    public function approvalTimeline(): array
    {
        $stages = [];
        foreach ([1, 2, 3] as $n) {
            $status = $this->{"status_{$n}"};
            $stages[] = [
                'stage' => $n,
                'label' => self::$STAGE_LABELS[$n],
                'status' => $status ?? ($n === 1 ? 'pending' : 'waiting'),
                'date' => $this->{"date_{$n}"},
            ];
        }

        return $stages;
    }

    public function canActOnStage($user, int $stage): bool
    {
        if (! $user) {
            return false;
        }
        $role = $user->role?->name ?? null;

        if ($stage === 2) {
            if ($role === 'Field Manager' || $role === 'Manager') {
                return $this->field_id && $user->field_id
                    && ($this->field?->groupId() === $user->field?->groupId());
            }

            return in_array($role, self::FINANCE_ROLES, true);
        }

        if ($stage === 3) {
            return in_array($role, self::FINANCE_ROLES, true);
        }

        return false;
    }

    /** Creator until branch review starts; finance until it is paid. */
    public function isEditableBy($user): bool
    {
        if (! $user || $this->isLockedForPay()) {
            return false;
        }
        if (in_array($user->role?->name ?? null, self::FINANCE_ROLES, true)) {
            return true;
        }

        return (int) $this->user_1 === (int) $user->id && is_null($this->status_2);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_1');
    }

    public function branchReviewer()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_2');
    }

    public function financeApprover()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_3');
    }
}
