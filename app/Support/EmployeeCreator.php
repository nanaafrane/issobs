<?php

namespace App\Support;

use App\Models\Client;
use App\Models\employee;
use App\Models\Field;
use App\Models\PaymentInfo;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ONE way to create an employee, used by the "Add employee" form (EmployeeController::store)
 * and by the bulk upload, so both apply the same approval status, the same payment
 * details and the same TIN / SSNIT handling.
 *
 * - Employee + payment details are created in ONE transaction (no half-created employee).
 * - TIN / SSNIT are written to every place that has the column (employees and/or
 *   payment_infos). The migrations define them on payment_infos, but the form has
 *   always written them to employees; writing both keeps the lists (which read
 *   payment_infos) and older screens (which read employees) in agreement.
 * - Payment priority is set by the employee model's saving hook, as for any save.
 */
class EmployeeCreator
{
    /** @var array<string, bool> */
    private static array $columns = [];

    public static function hasColumn(string $table, string $column): bool
    {
        return self::$columns["$table.$column"] ??= Schema::hasColumn($table, $column);
    }

    /**
     * Approval fields for employees created by $user - the same rules the form has
     * always used. Null when the user's role/department matches none of them: such
     * employees get no status and do not appear in any list.
     *
     * @return array<string, mixed>|null  values; 'APPROVER' marks where the chosen approver goes
     */
    public static function workflowFor(User $user): ?array
    {
        $role = (string) $user->role?->id;
        $dept = (string) $user->department?->id;
        $w = [];

        if ($role === '1') {
            $w = ['status' => 'Active', 'ho_status' => 'approved', 'user_id2' => $user->id];
        }
        if ($dept === '7' && $role === '3') {
            $w = array_merge($w, ['ho_status' => 'pending', 'user_id2' => 'APPROVER', 'status' => 'Pending',
                'bran_status' => 'approved', 'user_id1' => $user->id]);
        }
        if (($dept === '7' || $dept === '4') && $role === '27') {
            $w = array_merge($w, ['bran_status' => 'pending', 'user_id1' => 'APPROVER', 'status' => 'Pending',
                'assit_status' => 'pending']);
        }

        return $w ?: null;
    }

    /** Does this user's workflow hand the employee to a chosen approver ("Assign to")? */
    public static function needsApprover(User $user): bool
    {
        return in_array('APPROVER', self::workflowFor($user) ?? [], true);
    }

    /** "Assign to" choices - same as the form. */
    public static function approversFor(User $user): Collection
    {
        $staff = User::all();
        $list = collect();
        if ($user->role?->name === 'Admin Assistant') {
            $list = $staff->where('department_id', '7')->where('role_id', '3');
        }
        if ($user->role?->name === 'Manager') {
            $list = $staff->whereIn('department_id', ['1', '4'])->whereIn('role_id', ['1', '3']);
        }

        // The form shows only approvers in the user's own field office, except for Managers.
        return $list->filter(fn ($u) => $u->field_id == $user->field_id || $user->hasRole(['Manager']))->values();
    }

    /** Field offices this user may assign (same idea as the form). */
    public static function allowedFieldIds(User $user): array
    {
        if (self::seesAllFields($user)) {
            return Field::pluck('id')->map(fn ($id) => (int) $id)->all();
        }
        if ((string) $user->field_id === '3') {
            return [3, 7];
        }

        return $user->field_id ? [(int) $user->field_id] : [];
    }

    /** Active, approved clients in the user's allowed field offices. */
    public static function allowedClients(User $user): Collection
    {
        $query = Client::where('status', 'Active')->where('ho_status', 'approved');
        if (! self::seesAllFields($user)) {
            $query->whereIn('field_id', self::allowedFieldIds($user) ?: [0]);
        }

        return $query->orderBy('name')->get(['id', 'name', 'business_name', 'field_id']);
    }

    private static function seesAllFields(User $user): bool
    {
        return in_array($user->role?->name, ['Finance Manager', 'Invoice'], true)
            || ($user->department?->name === 'HR' && $user->role?->name === 'Manager');
    }

    /**
     * Create one employee with payment details, in one transaction.
     *
     * @param array $data   employee attributes (name, gender, ..., tax_button, tin_number, ssnit_button, ssnit_number, image)
     * @param array $pay    bank_id, acc_number, branch, branch_code
     */
    public static function create(array $data, array $pay, User $user, $approverId = null): employee
    {
        return DB::transaction(function () use ($data, $pay, $user, $approverId) {
            $employee = new employee();
            foreach ([
                'name', 'gender', 'phone_number', 'channel', 'date_of_birth', 'nia_number', 'address', 'marital_status',
                'worker_type', 'date_of_joining', 'department_id', 'role_id', 'field_id', 'client_id', 'location',
                'tax_button', 'ssnit_button', 'basic_salary', 'allowances', 'payment_type', 'gurantor_name',
                'gurantor_number', 'gurantor_address', 'gurantor_nia_number', 'relationship', 'image',
            ] as $attr) {
                $employee->$attr = $data[$attr] ?? null;
            }
            foreach (['tin_number', 'ssnit_number'] as $attr) {
                if (self::hasColumn('employees', $attr)) {
                    $employee->$attr = $data[$attr] ?? null;
                }
            }
            $employee->user_id = $user->id;

            foreach (self::workflowFor($user) ?? [] as $attr => $value) {
                if ($attr === 'assit_status' && ! self::hasColumn('employees', 'assit_status')) {
                    continue;
                }
                $employee->$attr = $value === 'APPROVER' ? $approverId : $value;
            }
            $employee->save();

            $info = new PaymentInfo();
            $info->employee_id = $employee->id;
            $info->bank_id = $pay['bank_id'] ?? null;
            $info->acc_number = $pay['acc_number'] ?? null;
            $info->branch = $pay['branch'] ?? null;
            $info->branch_code = $pay['branch_code'] ?? null;
            foreach (['tin_number', 'ssnit_number'] as $attr) {
                if (self::hasColumn('payment_infos', $attr)) {
                    $info->$attr = $data[$attr] ?? null;
                }
            }
            $info->user_id = $user->id;
            $info->save();

            $employee->payment_infos_id = $info->id;
            $employee->save();

            return $employee;
        });
    }
}
