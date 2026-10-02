<?php

namespace App\Exports\EmployeeUpload;

use App\Models\User;
use App\Support\EmployeeBulkImport;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\BeforeWriting;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Bulk UPDATE file: the chosen employees, pre-filled with their current details,
 * keyed by "Employee ID". Same sheets and dropdowns as the new-employee template.
 *
 *     return (new UpdateTemplateExport($user, $employeeIds, $canViewSalary))->download('...xlsx');
 */
class UpdateTemplateExport implements WithMultipleSheets, WithEvents
{
    use Exportable;

    public function __construct(private User $user, private array $employeeIds, private bool $canViewSalary)
    {
    }

    public function sheets(): array
    {
        $lists = TemplateExport::listsFor($this->user);
        $hidden = $this->canViewSalary ? [] : ['basic_salary', 'allowances'];

        return [
            new InstructionsSheet($this->user, 'update'),
            new EmployeesSheet($this->rows($lists), true, $hidden),
            new ListsSheet($lists),
        ];
    }

    /** Current details as template cells: dropdown values in the lists' "ID | Name" form. */
    private function rows(array $lists): array
    {
        $label = function (string $list, $id) use ($lists) {
            if ($id === null || $id === '') {
                return null;
            }
            foreach ($lists[$list] as $option) {
                if (str_starts_with($option, $id . ' | ')) {
                    return $option;
                }
            }

            return (string) $id; // e.g. a client outside the user's list: keep the ID
        };
        $networks = ['mtn-gh' => 'MTN', 'vodafone-gh' => 'TELECEL', 'tigo-gh' => 'AIRTELTIGO'];
        $date = fn ($v) => $v ? ExcelDate::PHPToExcel(substr((string) $v, 0, 10)) : null;

        $out = [];
        $current = EmployeeBulkImport::currentDetails($this->employeeIds);
        foreach ($this->employeeIds as $id) {
            if (! $d = $current[(int) $id] ?? null) {
                continue;
            }
            $out[] = [
                'employee_id' => 'FWSS ' . $id,
                'full_name' => $d['name'],
                'gender' => $d['gender'] ? ucfirst($d['gender']) : null,
                'phone_number' => $d['phone_number'],
                'momo_network' => $networks[$d['channel']] ?? $d['channel'],
                'date_of_birth' => $date($d['date_of_birth']),
                'nia_number' => $d['nia_number'],
                'address' => $d['address'],
                'marital_status' => $d['marital_status'] ? ucfirst($d['marital_status']) : null,
                'worker_type' => $d['worker_type'] ? ucfirst($d['worker_type']) : null,
                'date_of_joining' => $date($d['date_of_joining']),
                'department' => $label('Departments', $d['department_id']),
                'role' => $label('Roles', $d['role_id']),
                'field_office' => $label('FieldOffice', $d['field_id']),
                'client' => $label('Clients', $d['client_id']),
                'location' => $d['location'],
                'basic_salary' => $d['basic_salary'] !== null ? (float) $d['basic_salary'] : null,
                'allowances' => $d['allowances'] !== null ? (float) $d['allowances'] : null,
                'deduct_tax' => $d['tax_button'] ? 'Yes' : 'No',
                'tin_number' => $d['tin_number'],
                'deduct_ssnit' => $d['ssnit_button'] ? 'Yes' : 'No',
                'ssnit_number' => $d['ssnit_number'],
                'payment_type' => $d['payment_type'],
                'bank' => $label('Banks', $d['pay.bank_id']),
                'account_number' => $d['pay.acc_number'],
                'branch' => $d['pay.branch'],
                'branch_code' => $d['pay.branch_code'],
                'guarantor_name' => $d['gurantor_name'],
                'guarantor_phone' => $d['gurantor_number'],
                'guarantor_address' => $d['gurantor_address'],
                'guarantor_nia' => $d['gurantor_nia_number'],
                'relationship' => $d['relationship'],
            ];
        }

        return $out;
    }

    public function registerEvents(): array
    {
        return [
            BeforeWriting::class => fn (BeforeWriting $event) => $event->writer->getDelegate()->setActiveSheetIndexByName('Employees'),
        ];
    }
}
