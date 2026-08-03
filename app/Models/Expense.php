<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;


class Expense extends Model
{
    //
    use SoftDeletes; // add alongside any existing traits

    protected $casts = [
        'date_1' => 'datetime',
        'date_2' => 'datetime',
    ];

/**
 * Merge into the EXISTING app/Models/Expense.php.
 *
 * Adds: SoftDeletes trait, field_id/expense_type_id relations (from the
 * earlier patch), and two authorization helpers used by the controller
 * to decide whether the logged-in user may edit/delete a given expense.
 *
 * ADJUST the role-check logic (isPrivilegedApprover) to match whatever
 * role/permission system this app actually uses -- written generically
 * here since the exact Gate/role setup wasn't provided.
 */

    protected $fillable = [
        'description', 'amount',
        'field_id', 'expense_type_id',
        'user_1', 'status_1', 'date_1',
        'user_2', 'status_2', 'date_2',
        'user_3', 'status_3', 'image',
    ];

    public function type()
    {
        return $this->belongsTo(\App\Models\ExpenseType::class, 'expense_type_id');
    }

    public function field()
    {
        return $this->belongsTo(\App\Models\Field::class);
    }

    /**
     * An expense is only safely editable while it's still at the very
     * first approval stage -- once status_1 has moved past 'pending' the
     * numbers may already be relied on downstream (approvals, reports).
     */
    public function isEditableBy(\App\Models\User $user): bool
    {
        if ($this->isPrivilegedApprover($user)) {
            return true;
        }

        return $this->user_1 === $user->id
            && in_array($this->status_1, [null, 'pending'], true);
    }

    public function isDeletableBy(\App\Models\User $user): bool
    {
        return $this->isEditableBy($user);
    }

    /**
     * TODO: replace with your real role/permission check
     * (e.g. $user->hasRole('Finance Manager') or a Gate::allows(...) call).
     */
    protected function isPrivilegedApprover(\App\Models\User $user): bool
    {
        return in_array($user->role ?? null, ['Finance Manager', 'Director'], true);
    }



}
