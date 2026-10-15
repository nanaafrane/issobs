<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * The month's salary payment report, in the layout of the old "SAL SUMM" sheet,
 * plus a sheet listing salaries whose net changed after they were paid.
 */
class SalaryPaymentsExport implements WithMultipleSheets
{
    public function __construct(private array $report)
    {
    }

    public function sheets(): array
    {
        return [
            new SalaryPaymentsSummarySheet($this->report),
            new SalaryPaymentsChangesSheet($this->report),
        ];
    }
}
