<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Salaries whose current net differs from what was paid. */
class SalaryPaymentsChangesSheet implements FromArray, WithStrictNullComparison, WithStyles, WithTitle
{
    public function __construct(private array $report)
    {
    }

    public function title(): string
    {
        return 'CHANGED AFTER PAYMENT';
    }

    public function array(): array
    {
        $out = [['SALARY ID', 'EMPLOYEE', 'OFFICE', 'STATUS', 'NET WHEN FIRST PAID', 'PAID SO FAR', 'CURRENT NET', 'DIFFERENCE', '']];
        foreach ($this->report['changed'] as $c) {
            $out[] = [$c->id, $c->employee_name, $c->field_name, $c->payment_status, (float) $c->first_net, (float) $c->paid, (float) $c->net_salary,
                $c->difference, $c->difference > 0 ? 'Owed to staff' : 'Overpaid'];
        }
        if (count($out) === 1) {
            $out[] = ['', 'No salary changed after it was paid.'];
        }

        return $out;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('E2:H' . ($sheet->getHighestRow()))->getNumberFormat()->setFormatCode('#,##0.00;[Red]-#,##0.00');
        foreach (['A' => 10, 'B' => 32, 'C' => 20, 'D' => 11, 'E' => 18, 'F' => 14, 'G' => 14, 'H' => 14, 'I' => 16] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        return [];
    }
}
