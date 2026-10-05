<?php

namespace App\Exports\EmployeeUpload;

use App\Support\EmployeeBulkImport;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * The sheet people fill in: title (row 1), headers (row 2 - read back by
 * App\Imports\EmployeeUploadImport), then the input rows with dropdowns and an
 * automatic "Row check" column.
 *
 * New employees: empty input rows.
 * Bulk update:   an "Employee ID" key column first and one pre-filled row per employee.
 */
class EmployeesSheet extends DefaultValueBinder implements FromArray, WithTitle, WithEvents, WithCustomValueBinder
{
    /** Dropdowns where typing an ID instead of picking is fine (warning, not error). */
    private const LOOSE = ['FieldOffice', 'Departments', 'Roles', 'Clients', 'Banks'];

    /** @var array<string, array> column key => [header, required, kind, width, note] in sheet order */
    private array $columns = [];

    /** @var array<string, true> sheet column letters that hold text (kept as text: leading zeros) */
    private array $textColumns = [];

    /**
     * @param array<int, array<string, mixed>> $rows       pre-filled rows (column key => value) for bulk update
     * @param bool                             $withKey    add the "Employee ID" key column
     * @param string[]                         $hiddenKeys template columns to leave out (e.g. salaries)
     */
    public function __construct(private array $rows = [], private bool $withKey = false, array $hiddenKeys = [])
    {
        if ($withKey) {
            $this->columns['employee_id'] = [EmployeeBulkImport::KEY_HEADER, true, 'key', 13, 'Who this row is. Do not change it.'];
            $this->columns['current_status'] = [EmployeeBulkImport::STATUS_HEADER, false, 'info', 14,
                'Information only - use it to sort or filter (e.g. Active only). Changing it has no effect: terminate or re-instate on the employee page.'];
        }
        foreach (EmployeeBulkImport::TEMPLATE_COLUMNS as $header => [$required, $kind, $width, $note]) {
            $key = EmployeeBulkImport::COLUMNS[$header];
            if (! in_array($key, $hiddenKeys, true)) {
                // In an update file nothing but the key is required: blank = unchanged.
                $this->columns[$key] = [$header, $withKey ? false : $required, $kind, $width, $note];
            }
        }
        $i = 0;
        foreach ($this->columns as [, , $kind]) {
            $i++;
            if ($kind === 'text' || $kind === 'key' || $kind === 'info') {
                $this->textColumns[Coordinate::stringFromColumnIndex($i)] = true;
            }
        }
    }

    public function title(): string
    {
        return 'Employees';
    }

    private function inputRows(): int
    {
        return $this->withKey ? max(1, count($this->rows)) : EmployeeBulkImport::MAX_ROWS;
    }

    public function array(): array
    {
        $headers = array_map(fn ($c) => $c[0] . ($c[1] ? ' *' : ''), array_values($this->columns));
        $headers[] = 'Row check (automatic)';
        $intro = $this->withKey
            ? 'One row per employee, filled in with their current details. Change only what you want to update - a blank cell means "leave unchanged". Do not change the Employee ID column.'
            : 'Fill in one employee per row from row 3. Red headers are required. Do not rename, move or delete columns. The last column checks each row for you.';

        $out = [[$intro], $headers];
        foreach ($this->rows as $row) {
            $out[] = array_map(fn ($key) => $row[$key] ?? null, array_keys($this->columns));
        }

        return $out;
    }

    /** Text columns stay text (phone / account numbers keep leading zeros); dates stay Excel dates. */
    public function bindValue(Cell $cell, $value)
    {
        if ($cell->getRow() >= 3 && isset($this->textColumns[$cell->getColumn()]) && $value !== null && $value !== '') {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $ws = $event->sheet->getDelegate();
                $first = 3;
                $last = $first + $this->inputRows() - 1;
                $n = count($this->columns);
                $L = fn (int $i) => Coordinate::stringFromColumnIndex($i);
                $lastCol = $L($n);
                $checkCol = $L($n + 1);
                $fill = fn ($range, $argb) => $ws->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($argb);
                $col = array_combine(array_keys($this->columns), array_map($L, range(1, $n)));

                $ws->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
                $ws->mergeCells('A1:' . $checkCol . '1');
                $ws->getStyle('A1')->getFont()->setBold(true)->getColor()->setARGB('FF1F3864');
                $ws->getStyle('A1')->getAlignment()->setWrapText(true);
                $ws->getRowDimension(1)->setRowHeight(30);
                $fill('A' . $first . ':' . $lastCol . $last, 'FFFFF2CC');     // cells to fill in

                foreach ($this->columns as $key => [, $required, $kind, $width, $note]) {
                    $c = $col[$key];
                    $ws->getColumnDimension($c)->setWidth($width);
                    $fill($c . '2', $required ? 'FFC00000' : 'FF1F3864');
                    if ($note !== '') {
                        $ws->getComment($c . '2')->getText()->createTextRun($note);
                        $ws->getComment($c . '2')->setWidth('240pt')->setHeight('60pt');
                    }

                    $range = $c . $first . ':' . $c . $last;
                    if ($kind === 'info') {
                        $ws->getStyle($range)->getNumberFormat()->setFormatCode('@');
                        $fill($range, 'FFEDEDED');
                        $ws->getStyle($range)->getFont()->setItalic(true)->getColor()->setARGB('FF595959');
                    } elseif ($kind === 'key') {
                        $ws->getStyle($range)->getNumberFormat()->setFormatCode('@');
                        $fill($range, 'FFD9D9D9');
                        $ws->getStyle($range)->getFont()->setBold(true);
                    } elseif ($kind === 'text') {
                        $ws->getStyle($range)->getNumberFormat()->setFormatCode('@');
                    } elseif ($kind === 'date') {
                        $ws->getStyle($range)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                        $ws->setDataValidation($range, (new DataValidation())->setType(DataValidation::TYPE_DATE)
                            ->setOperator(DataValidation::OPERATOR_BETWEEN)->setFormula1('DATE(1940,1,1)')->setFormula2('DATE(2100,12,31)')
                            ->setAllowBlank(true)->setShowErrorMessage(true)->setErrorTitle('Date')->setError('Enter a real date, e.g. 14/03/1990.'));
                    } elseif ($kind === 'money') {
                        $ws->getStyle($range)->getNumberFormat()->setFormatCode('#,##0.00');
                        $ws->setDataValidation($range, (new DataValidation())->setType(DataValidation::TYPE_DECIMAL)
                            ->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL)->setFormula1('0')
                            ->setAllowBlank(true)->setShowErrorMessage(true)->setErrorTitle('Numbers only')->setError('Enter an amount of 0 or more.'));
                    } else {
                        $name = substr($kind, 5);
                        // One validation per column range, named range without "=" (as Excel stores it).
                        $ws->setDataValidation($range, (new DataValidation())->setType(DataValidation::TYPE_LIST)
                            ->setErrorStyle(in_array($name, self::LOOSE, true) ? DataValidation::STYLE_WARNING : DataValidation::STYLE_STOP)
                            ->setAllowBlank(true)->setShowDropDown(true)->setShowErrorMessage(true)
                            ->setErrorTitle('Choose from the list')->setError('Pick a value from the list.')
                            ->setFormula1($name));
                    }
                }

                $ws->getStyle('A2:' . $lastCol . '2')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $ws->getStyle('A2:' . $checkCol . '2')->getAlignment()->setWrapText(true)->setVertical('center')->setHorizontal('center');
                $ws->getRowDimension(2)->setRowHeight(34);

                // "Row check": not uploaded; flags the basic problems before upload.
                $fill($checkCol . '2', 'FFE7E6E6');
                $ws->getStyle($checkCol . '2')->getFont()->setBold(true);
                $ws->getColumnDimension($checkCol)->setWidth(32);
                $required = $this->withKey ? ['employee_id'] : EmployeeBulkImport::REQUIRED;
                for ($r = $first; $r <= $last; $r++) {
                    $tests = [];
                    $missing = array_map(fn ($k) => $col[$k] . $r . '=""', array_filter($required, fn ($k) => isset($col[$k])));
                    if ($missing) {
                        $tests[] = ['OR(' . implode(',', $missing) . ')', $this->withKey ? 'Employee ID missing' : 'Missing required field'];
                    }
                    if (isset($col['payment_type'], $col['bank'], $col['account_number'])) {
                        $tests[] = ["AND({$col['payment_type']}{$r}=\"Bank\",OR({$col['bank']}{$r}=\"\",{$col['account_number']}{$r}=\"\"))", 'Bank and account number required'];
                    }
                    if (isset($col['deduct_tax'], $col['tin_number'])) {
                        $tests[] = ["AND({$col['deduct_tax']}{$r}=\"Yes\",{$col['tin_number']}{$r}=\"\")", 'TIN required (Deduct Tax = Yes)'];
                    }
                    if (isset($col['deduct_ssnit'], $col['ssnit_number'])) {
                        $tests[] = ["AND({$col['deduct_ssnit']}{$r}=\"Yes\",{$col['ssnit_number']}{$r}=\"\")", 'SSNIT number required'];
                    }
                    if (isset($col['phone_number'])) {
                        $p = $col['phone_number'];
                        $tests[] = ["AND({$p}{$r}<>\"\",COUNTIF(\${$p}\${$first}:\${$p}\${$last},{$p}{$r})>1)", 'Phone repeated in this file'];
                    }
                    if ($this->withKey) {
                        $k = $col['employee_id'];
                        $tests[] = ["COUNTIF(\${$k}\${$first}:\${$k}\${$last},{$k}{$r})>1", 'Employee ID repeated in this file'];
                    }
                    $formula = '"OK"';
                    foreach (array_reverse($tests) as [$test, $message]) {
                        $formula = "IF({$test},\"{$message}\",{$formula})";
                    }
                    $ws->setCellValue($checkCol . $r, "=IF(COUNTA(A{$r}:{$lastCol}{$r})=0,\"\",{$formula})");
                }
                $fill($checkCol . $first . ':' . $checkCol . $last, 'FFE7E6E6');
                $ws->getStyle($checkCol . $first . ':' . $checkCol . $last)->getFont()->setBold(true);
                $ws->freezePane($this->withKey ? 'D3' : 'B3');  // keep Employee ID + Current Status (+ name) in view
                if ($this->withKey) {
                    $ws->setAutoFilter('A2:' . $lastCol . $last);
                }
            },
        ];
    }
}
