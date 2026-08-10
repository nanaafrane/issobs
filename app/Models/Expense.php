<?php
/**
 * Full replacement for app/Models/Expense.php.
 *
 * Approval chain, clarified:
 *   Stage 1 (status_1) = the creator's own submission. Set to 'approved'
 *                         automatically at creation -- it's a confirmation
 *                         that the record was submitted, not a review by
 *                         someone else. user_1 always stays the creator.
 *   Stage 2 (status_2) = Branch/Field Manager review.
 *   Stage 3 (status_3) = Head Office / Director final approval.
 *
 * This avoids the earlier bug where "approving stage 1" would have had to
 * overwrite user_1 (the creator) with the approver's id.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    public const STAGE_LABELS = [
        1 => 'Submitted',
        2 => 'Branch Approval',
        3 => 'Head Office Approval',
    ];

    protected $fillable = [
        'description', 'amount', 'expense_date',
        'field_id', 'expense_type_id',
        'user_1', 'status_1', 'date_1',
        'user_2', 'status_2', 'date_2',
        'user_3', 'status_3', 'image',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'date_1' => 'datetime',
        'date_2' => 'datetime',
        'date_3' => 'datetime',
    ];

    public function type()
    {
        return $this->belongsTo(ExpenseType::class, 'expense_type_id');
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    /**
     * Which stage (2 or 3) is currently awaiting action, or null if
     * fully approved, rejected, or (defensively) stage 1 isn't done yet.
     */
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

        return null; // stage 3 already decided either way
    }

    public function isFullyApproved(): bool
    {
        return $this->status_3 === 'approved';
    }

    public function isRejected(): bool
    {
        return in_array('rejected', [$this->status_1, $this->status_2, $this->status_3], true);
    }

    /**
     * Ordered timeline data for rendering the 3-stage pipeline in the UI.
     * Each entry: ['label' => ..., 'status' => approved|rejected|pending|waiting, 'date' => ...]
     */
    public function approvalTimeline(): array
    {
        $stages = [];
        foreach ([1, 2, 3] as $n) {
            $status = $this->{"status_{$n}"};
            $stages[] = [
                'stage'  => $n,
                'label'  => self::STAGE_LABELS[$n],
                'status' => $status ?? ($n === 1 ? 'pending' : 'waiting'),
                'date'   => $this->{"date_{$n}"},
            ];
        }

        return $stages;
    }

    /**
     * Editable only by the creator while no one has acted on stage 2 yet,
     * or by a privileged approver at any time.
     */
    public function isEditableBy(User $user): bool
    {
        if ($this->isPrivilegedApprover($user)) {
            return true;
        }

        return $this->user_1 === $user->id && is_null($this->status_2);
    }

    public function isDeletableBy(User $user): bool
    {
        return $this->isEditableBy($user);
    }


    public function canActOnStage(User $user, int $stage): bool
    {
        $role = $user->role?->name ?? null;

        if ($stage === 2) {
            if ($role === 'Field Manager') {
                return $this->sameOfficeGroupAs($user);
            }

            return in_array($role, ['Finance Manager', 'Director'], true);
        }

        if ($stage === 3) {
            return in_array($role, ['Finance Manager', 'Director'], true);
        }

        return false;
    }

    /**
     * True if this expense's office and the user's own office belong to
     * the same top-level group -- e.g. an expense on Shai Hills and a
     * Field Manager based at Tema match, since Shai Hills is managed
     * under Tema.
     */
    protected function sameOfficeGroupAs(User $user): bool
    {
        if (! $this->field || ! $user->field) {
            return false;
        }

        return $this->field->groupId() === $user->field->groupId();
    }

    protected function isPrivilegedApprover(User $user): bool
    {
        return in_array($user->role?->name ?? null, ['Finance Manager', 'Director'], true);
    }
}