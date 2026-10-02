<?php

namespace App\Exports\EmployeeUpload;

use App\Support\EmployeeBulkImport;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * The rows that could not be imported, in the template's layout (title on row 1,
 * headers on row 2) plus an "Errors" column - so the file can be fixed and uploaded
 * again as it is (EmployeeUploadImport ignores the extra columns).
 */
class ErrorReportExport extends DefaultValueBinder implements FromArray, WithTitle, WithEvents, WithCustomValueBinder
{
    use Exportable;

    /**
     * @param array<int, array<string, ?string>> $rows    EmployeeBulkImport::read() rows (by sheet row)
     * @param array<int, array>                  $results EmployeeBulkImport::validate() results to report
     */
    public function __construct(private array $rows, private array $results, private bool $withKey = false)
    {
    }

    public function title(): string
    {
        return 'Employees';
    }

    public function array(): array
    {
        $headers = $this->withKey ? [EmployeeBulkImport::KEY_HEADER . ' *'] : [];
        foreach (EmployeeBulkImport::TEMPLATE_COLUMNS as $header => [$required]) {
            $headers[] = $header . ($required ? ' *' : '');
        }
        $out = [
            ['Rows that were NOT imported. Fix them using the Errors column, then upload this file again (the Errors and Sheet row columns are ignored).'],
            array_merge($headers, ['Errors', 'Sheet row']),
        ];
        foreach ($this->results as $result) {
            if (! $result['errors']) {
                continue;
            }
            $keys = array_values(EmployeeBulkImport::COLUMNS);
            if ($this->withKey) {
                array_unshift($keys, 'employee_id');
            }
            $values = array_map(fn ($key) => $this->rows[$result['row']][$key] ?? null, $keys);
            $out[] = array_merge($values, [implode("\n", $result['errors']), (string) $result['row']]);
        }

        return $out;
    }

    /** Everything as text: keeps leading zeros in phone / account numbers. */
    public function bindValue(Cell $cell, $value)
    {
        if ($value === null || $value === '') {
            return parent::bindValue($cell, $value);
        }
        $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

        return true;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $ws = $event->sheet->getDelegate();
                $n = count(EmployeeBulkImport::TEMPLATE_COLUMNS) + ($this->withKey ? 1 : 0);
                $errCol = Coordinate::stringFromColumnIndex($n + 1);
                $rowCol = Coordinate::stringFromColumnIndex($n + 2);
                $ws->getStyle('A1')->getFont()->setBold(true)->getColor()->setARGB('FFC00000');
                $ws->getStyle('A2:' . $rowCol . '2')->getFont()->setBold(true);
                for ($i = 1; $i <= $n + 2; $i++) {
                    $ws->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth($i === $n + 1 ? 60 : 18);
                }
                $last = $ws->getHighestRow();
                if ($last >= 3) {
                    $ws->getStyle($errCol . '3:' . $errCol . $last)->getFont()->getColor()->setARGB('FFC00000');
                    $ws->getStyle($errCol . '3:' . $errCol . $last)->getAlignment()->setWrapText(true);
                }
                $ws->freezePane('B3');
            },
        ];
    }
}
