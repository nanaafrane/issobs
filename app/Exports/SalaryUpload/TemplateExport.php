<?php

namespace App\Exports\SalaryUpload;

use App\Models\User;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\BeforeWriting;

/**
 * Salary upload workbook: Instructions + Salaries.
 *
 *   template      (new TemplateExport($user, $columns, $rows, 'October 2026', 'Accra'))
 *   error report  (new TemplateExport($user, $columns, $rows, ..., errors: $errors))
 */
class TemplateExport implements WithMultipleSheets, WithEvents
{
    use Exportable;

    public function __construct(
        private User $user,
        private array $columns,
        private array $rows,
        private string $month,
        private ?string $office = null,
        private ?array $errors = null,
    ) {
    }

    public function sheets(): array
    {
        $intro = $this->errors === null ? '' :
            'ROWS THAT WERE NOT SAVED. Each red cell has a note saying what is wrong, and the "Errors from upload" column lists them. Fix them here, then upload this file again (only these rows are in it).';

        return [
            new InstructionsSheet($this->user, $this->columns, count($this->rows), $this->month, $this->office, $this->errors !== null),
            new SalariesSheet($this->columns, $this->rows, $this->errors, $intro),
        ];
    }

    public function registerEvents(): array
    {
        return [
            BeforeWriting::class => fn (BeforeWriting $event) => $event->writer->getDelegate()->setActiveSheetIndexByName('Salaries'),
        ];
    }
}
