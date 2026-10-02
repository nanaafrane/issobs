<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Reads the bulk employee upload template (same approach as SalaryImport):
 *
 *     $sheets = (new EmployeeUploadImport)->toCollection($file);
 *
 * The template has a title on row 1 and the column headers on row 2, so the heading
 * row is 2. Headers are turned into keys by Laravel Excel's "slug" formatter
 * ("Full Name *" -> full_name, "Ghana Card (NIA) No." -> ghana_card_nia_no).
 * Checking the rows and creating employees is done by App\Support\EmployeeBulkImport
 * and App\Support\EmployeeCreator.
 */
class EmployeeUploadImport implements WithHeadingRow
{
    use Importable;

    public function __construct(private int $headingRow = 2)
    {
    }

    public function headingRow(): int
    {
        return $this->headingRow;
    }
}
