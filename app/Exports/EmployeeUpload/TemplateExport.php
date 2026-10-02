<?php

namespace App\Exports\EmployeeUpload;

use App\Models\Bank;
use App\Models\Department;
use App\Models\Field;
use App\Models\Role;
use App\Models\User;
use App\Support\EmployeeCreator;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\BeforeWriting;

/**
 * Bulk employee upload template, built for one user:
 *     return (new TemplateExport($user))->download('ISSOBS_Employee_Upload_Template.xlsx');
 *
 * Sheets: Instructions, Employees (fill in), Lists (dropdown values). The dropdowns
 * list only the field offices and clients the user may assign.
 */
class TemplateExport implements WithMultipleSheets, WithEvents
{
    use Exportable;

    /** @var array<string, string[]> named range => values */
    private array $lists;

    public function __construct(private User $user)
    {
        $this->lists = self::listsFor($user);
    }

    /** Dropdown values for $user (named range => values). Shared with UpdateTemplateExport. */
    public static function listsFor(User $user): array
    {
        $fieldNames = Field::pluck('name', 'id');

        return [
            'Gender' => ['Male', 'Female'],
            'MoMoNetwork' => ['MTN', 'TELECEL', 'AIRTELTIGO'],
            'MaritalStatus' => ['Single', 'Married', 'Divorced', 'Widowed'],
            'WorkerType' => ['Employee', 'Contractor'],
            'YesNo' => ['Yes', 'No'],
            'PaymentType' => ['Cash', 'Bank'],
            'FieldOffice' => array_map(fn ($id) => $id . ' | ' . ($fieldNames[$id] ?? 'Field ' . $id), EmployeeCreator::allowedFieldIds($user)),
            'Departments' => Department::orderBy('name')->get(['id', 'name'])->map(fn ($d) => $d->id . ' | ' . $d->name)->all(),
            'Roles' => Role::orderBy('name')->get(['id', 'name'])->map(fn ($r) => $r->id . ' | ' . $r->name)->all(),
            'Clients' => EmployeeCreator::allowedClients($user)->map(fn ($c) => $c->id . ' | ' . trim($c->name . ' ' . $c->business_name))->all(),
            'Banks' => Bank::orderBy('name')->get(['id', 'name'])->map(fn ($b) => $b->id . ' | ' . $b->name)->all(),
        ];
    }

    public function sheets(): array
    {
        return [
            new InstructionsSheet($this->user),
            new EmployeesSheet(),
            new ListsSheet($this->lists),
        ];
    }

    public function registerEvents(): array
    {
        return [
            // Open the file on the sheet people fill in.
            BeforeWriting::class => fn (BeforeWriting $event) => $event->writer->getDelegate()->setActiveSheetIndexByName('Employees'),
        ];
    }
}
