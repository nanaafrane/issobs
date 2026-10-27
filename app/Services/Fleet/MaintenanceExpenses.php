<?php

namespace App\Services\Fleet;

use App\Models\Expense;
use App\Models\VehicleMaintenance;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A maintenance job is one bill, so once it is fully approved it becomes ONE expense under its
 * type's expense item (Roadworthy -> License & Registration, Tyres -> Vehicle Repairs &
 * Maintenance…). Finance pays it from Payments › Pay approved expenses with everything else.
 *
 *  sync()    after any save / approval: approved -> the expense exists and matches;
 *            pending or rejected -> no expense. A paid expense is never touched.
 *  remove()  on delete: the unpaid expense goes too.
 */
class MaintenanceExpenses
{
    public function sync(VehicleMaintenance $m): ?Expense
    {
        return DB::transaction(function () use ($m) {
            $m->loadMissing(['vehicle', 'type']);
            $expense = $m->expense_id ? Expense::withTrashed()->find($m->expense_id) : null;
            if ($expense && $expense->isPaid()) {
                return $expense;   // paid: locked, leave it alone
            }

            if (! $m->isFullyApproved() || $m->isRejected()) {
                if ($expense && ! $expense->trashed()) {
                    $expense->delete();
                }
                if ($m->expense_id) {
                    $m->expense_id = null;
                    $m->saveQuietly();
                }

                return null;
            }

            $values = [
                'description' => $this->description($m),
                'amount' => round((float) $m->amount, 2),
                'expense_date' => $m->service_date->toDateString(),
                'field_id' => $m->field_id ?? $m->vehicle?->field_id,
                'expense_type_id' => $m->type->resolveExpenseType()->id,
                'user_1' => $m->user_1, 'status_1' => 'approved', 'date_1' => $m->date_1 ?? now(),
                'user_2' => $m->user_2, 'status_2' => 'approved', 'date_2' => $m->date_2 ?? now(),
                'user_3' => $m->user_3, 'status_3' => 'approved',
            ];
            if ($expense) {
                if ($expense->trashed()) {
                    $expense->restore();
                }
                $expense->fill($values)->save();
            } else {
                $expense = Expense::create($values);
                $m->expense_id = $expense->id;
                $m->saveQuietly();
            }

            return $expense;
        });
    }

    public function remove(VehicleMaintenance $m): void
    {
        if ($m->isPaid()) {
            throw ValidationException::withMessages(['maintenance' => 'This maintenance has been paid: cancel the payment first.']);
        }
        DB::transaction(function () use ($m) {
            if ($m->expense_id) {
                Expense::whereKey($m->expense_id)->delete();
            }
            $m->delete();
        });
    }

    private function description(VehicleMaintenance $m): string
    {
        return mb_substr(implode(' · ', array_filter([
            ($m->type?->name ?? 'Maintenance') . ' — ' . ($m->vehicle?->label() ?? 'vehicle'),
            $m->vendor,
            $m->reference ? 'Ref ' . $m->reference : null,
            $m->description,
            'Maintenance #' . $m->id,
        ])), 0, 255);
    }
}
