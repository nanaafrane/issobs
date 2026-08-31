<?php

namespace App\Observers;

use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\Overtime;
use Illuminate\Support\Facades\Auth;

/**
 * Keeps `expenses` in sync with `overtimes`.
 *
 * Rule: an Overtime row only counts toward Expense totals once it has
 * cleared its own 3-stage approval chain (isFullyApproved()). This stops
 * pending/rejected overtime from inflating expense dashboards, while
 * still letting Overtime have its own lightweight submit → branch review
 * → finance approval flow that mirrors how Expense already works.
 *
 * Registered in AppServiceProvider::boot():
 *     Overtime::observe(OvertimeObserver::class);
 */
class OvertimeObserver
{
    public function saved(Overtime $overtime): void
    {
        if ($overtime->isFullyApproved()) {
            $this->syncExpense($overtime);
        } elseif ($overtime->expense_id) {
            $this->removeExpense($overtime);
        }
    }

    public function deleted(Overtime $overtime): void
    {
        $this->removeExpense($overtime);
    }

    protected function syncExpense(Overtime $overtime): void
    {
        $type = ExpenseType::firstOrCreate(
            ['name' => 'Overtime', 'scope' => 'field'],
            ['created_by' => Auth::id()]
        );

        $description = sprintf(
            'Overtime - %s (%s shift) - %s covering for %s at %s',
            optional($overtime->entry_date)->format('d M Y'),
            ucfirst($overtime->shift),
            $overtime->otEmployee?->name ?? 'Unassigned',
            $overtime->absentLabel(),
            $overtime->clientLabel()
        );

        $payload = [
            'description' => $description,
            'amount' => $overtime->amount,
            'expense_date' => $overtime->entry_date,
            'field_id' => $overtime->field_id,
            'expense_type_id' => $type->id,
            'overtime_id' => $overtime->id,
            // The expense inherits Overtime's own completed approval chain -
            // it has already been through submit/branch/finance on the
            // Overtime side, so it lands in Expense pre-approved.
            'user_1' => $overtime->user_1,
            'status_1' => 'approved',
            'date_1' => $overtime->date_1,
            'user_2' => $overtime->user_2,
            'status_2' => 'approved',
            'date_2' => $overtime->date_2,
            'user_3' => $overtime->user_3,
            'status_3' => 'approved',
        ];

        if ($overtime->expense_id) {
            Expense::where('id', $overtime->expense_id)->update($payload);
        } else {
            $expense = Expense::updateOrCreate(['overtime_id' => $overtime->id], $payload);
            // avoid re-triggering saved() -> syncExpense() loop
            Overtime::withoutEvents(function () use ($overtime, $expense) {
                $overtime->fresh()?->update(['expense_id' => $expense->id]);
            });
            $overtime->expense_id = $expense->id;
        }
    }

    protected function removeExpense(Overtime $overtime): void
    {
        if (! $overtime->expense_id) {
            return;
        }

        Expense::where('id', $overtime->expense_id)->delete();

        Overtime::withoutEvents(function () use ($overtime) {
            $overtime->fresh()?->update(['expense_id' => null]);
        });
        $overtime->expense_id = null;
    }
}
