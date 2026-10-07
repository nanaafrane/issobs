<?php

namespace App\Exports;

use App\Models\category;
use App\Models\Salary;
use Carbon\Carbon;
// use Maatwebsite\Excel\Concerns\FromCollection;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

use Maatwebsite\Excel\Concerns\WithCustomStartCell;

use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;


class SalaryBankExport implements FromQuery, WithMapping , WithHeadings, WithDrawings, WithCustomStartCell, WithStyles, WithEvents
{
      use Exportable;  
    /**
    * @param Salary $salary
    */

        protected $month;
        protected $bank_id;
        protected $dynamicHeaders;

            // 1. Define the counter property
        private $rowNumber = 0;

        /** client_id => category name for the month (latest row, shared rule). Loaded once. */
        private ?array $categoryByClient = null;

        /** client_id => ['status' => month invoice status, 'record' => last 6 months]. Loaded once. */
        private ?array $paymentsByClient = null;

    public function __construct($month, $bank_id, array $headers)
    {
        $this->month = Carbon::parse($month);
        $this->bank_id = $bank_id;
        $this->dynamicHeaders = $headers;
    
    }

    
    public function query()
    {
        return Salary::query()
        // Eager-load what map() prints, instead of several lookups per row.
        ->with(['employee:id,name', 'field:id,name', 'role:id,name', 'client:id,name,business_name', 'paymentInfo'])
        ->where('bank_id', $this->bank_id )->where('payment_type', 'Bank')->whereBetween('salary_month', \App\Support\PayrollMonth::span($this->month))->whereIn('payment_status', ['pending', 'approved'])->select([
        'id',
        'pay_priority',
        'pay_priority_reason',
        'employee_id',
        'payment_status',
        'updated_at',
        'employee_id',
        'field_id',
        'role_id',
        'client_id',
        'location',
        'employee_id',
        'branch',
        'account_number',
        'net_salary',
        ])->orderByDesc('pay_priority')->orderBy('client_id', 'ASC')->orderBy('id'); // pay first, pay early, then by client (id keeps chunked export pages stable)
    }

    /**
    * @param Salary $salary
    */
    public function map($salary): array
    {
        // Same rule as the category page and payroll master: the client's latest category that month.
        $this->categoryByClient ??= \App\Support\CategoryPayroll::latestCategorySub($this->month)->pluck('name', 'client_id')->all();

        return [
             ++$this->rowNumber,
            "FWSS ". $salary->employee_id,
            $salary->payment_status,
            $salary->updated_at->format('F l d, Y, H:i A'),
            $salary->employee?->name,
            $this->categoryByClient[$salary->client_id] ?? '',
            $salary->field?->name,
            $salary->role?->name,
            $salary->client?->name . " " .$salary->client?->business_name,
            $salary->location,
            $salary->paymentInfo?->branch_code,
            $salary->branch,
            $salary->account_number,                            
            $salary->net_salary,
            // Kept after NET so NET stays in column N (the total formula below uses it).
            $salary->pay_priority ? \App\Support\PayPriority::LABELS[(int) $salary->pay_priority] : '',
            // P, Q, R: has the client paid its invoice for this month, and when?
            ...$this->invoiceColumns($salary->client_id),
        ];
    }

    /** [status, detail, payment record] of the salary's client for the export month. */
    private function invoiceColumns($clientId): array
    {
        if ($this->paymentsByClient === null) {
            $this->paymentsByClient = [];
            foreach (\App\Support\ClientInvoiceStatus::history(null, $this->month) as $id => $h) {
                $this->paymentsByClient[$id] = ['status' => end($h['months']), 'record' => $h['record']];
            }
        }

        if (! $clientId) {
            return ['No client', '', ''];
        }
        $p = $this->paymentsByClient[(int) $clientId] ?? null;
        if ($p === null) { // not invoiced in the last months at all
            return [\App\Support\ClientInvoiceStatus::LABELS[\App\Support\ClientInvoiceStatus::NONE], '', \App\Support\ClientInvoiceStatus::RECORD_LABELS[\App\Support\ClientInvoiceStatus::RECORD_NONE]];
        }

        return [
            \App\Support\ClientInvoiceStatus::label($p['status']),
            $p['status']['status'] === \App\Support\ClientInvoiceStatus::NONE ? '' : \App\Support\ClientInvoiceStatus::detail($p['status']),
            $p['record']['label'] . ' - ' . $p['record']['summary'],
        ];
    }
    


    public function headings(): array
    {
        return [
        ['FIRST WATCH SECURITY SERVICES LTD' ],
        [ strtoupper($this->dynamicHeaders[0]) ],
        [ strtoupper($this->dynamicHeaders[1])." SALARY" ],
        [  
        '#',     
        'EMPLOYEE ID',
        'STATUS',
        'UPDATED',
        'NAME',
        'CATEGORY',
        'FIELD',
        'ROLE',
        'CLIENT',
        'LOCATION',
        'BRANCH CODE',
        'BRANCH',
        'ACCOUNT NUMBER',
        'NET',
        'PAY PRIORITY',
        'CLIENT INVOICE',
        'INVOICE PAID / DUE',
        'CLIENT PAYMENT RECORD'],

        ];
    

    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Company Logo');
        $drawing->setPath('https://issobs.com/img/icons/brands/issobs.png');
        $drawing->setHeight(90);
        $drawing->setCoordinates('G1'); // Top-left corner

        return $drawing;
    }

    public function startCell(): string
    {
        return 'A1'; 
    }


    public function styles(Worksheet $sheet)
    {
        // Set the height of the first row to 100 pixels for a large logo
        // $sheet->getRowDimension(1)->setRowHeight(50);

        return [
            // Style the first row (header) as bold text.
            1 => ['font' => ['bold' => true, 'size' => 18]],
            2 => ['font' => ['bold' => true , 'size' => 18]],
            3 => ['font' => ['bold' => true, 'size' => 18]],
        ];
        // Optional: Set specific column width
        // $sheet->getColumnDimension('A')->setWidth(30);
    }


        /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
              
                            // Insert row 4 and set data
                $event->sheet->insertNewRowBefore(4, 1);
              

                // Get the highest row number (last row with data)
                $lastRow = $event->sheet->getHighestRow();
               

                // Add 1 to get the row number for the total
                $totalRow = $lastRow + 1;

                // Append the formula for total (e.g., column C)
                $event->sheet->setCellValue('M4', 'Total');
                $event->sheet->setCellValue('N4', '=SUM(N6:N' . $lastRow . ')');

                // Optional: Style the total row (Bold) HOW TO GET DYNAMIC ROW ('J'.$totalRow.':K'.$totalRow)
                $event->sheet->getStyle('M4:N4')
                    ->getFont()->setBold(true)->setSize(14);

                // Payment priority: subtotals beside the total, and shaded rows (pay first / pay early).
                if ($lastRow >= 6) {
                    $range = fn ($col) => $col . '6:' . $col . $lastRow;
                    $event->sheet->setCellValue('I4', 'Pay first');
                    $event->sheet->setCellValue('J4', '=SUMIF(' . $range('O') . ',"Pay first",' . $range('N') . ')');
                    $event->sheet->setCellValue('K4', 'Pay early');
                    $event->sheet->setCellValue('L4', '=SUMIF(' . $range('O') . ',"Pay early",' . $range('N') . ')');
                    $event->sheet->getStyle('I4:L4')->getFont()->setBold(true)->setSize(12);
                    $event->sheet->getStyle('I4:J4')->getFont()->getColor()->setARGB('FF9C0006');
                    $event->sheet->getStyle('K4:L4')->getFont()->getColor()->setARGB('FF7F6000');

                    $fill = [
                        \App\Support\PayPriority::LABELS[\App\Support\PayPriority::URGENT] => 'FFF8D7DA',   // light red
                        \App\Support\PayPriority::LABELS[\App\Support\PayPriority::PRIORITY] => 'FFFFF3CD', // light amber
                    ];
                    // Net salaries of clients whose invoice for the month is not fully paid ("Paid" and "Paid late" count as paid).
                    $event->sheet->setCellValue('P4', 'Client not paid');
                    $event->sheet->setCellValue('Q4', '=N4-SUMIF(' . $range('P') . ',"Paid*",' . $range('N') . ')');
                    $event->sheet->getStyle('P4:Q4')->getFont()->setBold(true)->setSize(12);
                    $event->sheet->getStyle('P4:Q4')->getFont()->getColor()->setARGB('FF9C0006');

                    $sheet = $event->sheet->getDelegate();
                    for ($row = 6; $row <= $lastRow; $row++) {
                        $label = (string) $sheet->getCell('O' . $row)->getValue();
                        if (isset($fill[$label])) {
                            $sheet->getStyle('A' . $row . ':O' . $row)->getFill()
                                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                ->getStartColor()->setARGB($fill[$label]);
                            $sheet->getStyle('O' . $row)->getFont()->setBold(true);
                        }

                        // Client invoice status: coloured text in column P.
                        $invColor = [
                            'Paid' => 'FF2E7D32', 'Paid late' => 'FF0277BD', 'Part paid' => 'FFB26A00',
                            'Overdue' => 'FFC62828', 'Unpaid' => 'FF5F6B7A', 'No invoice' => 'FF5F6B7A',
                        ][(string) $sheet->getCell('P' . $row)->getValue()] ?? null;
                        if ($invColor) {
                            $sheet->getStyle('P' . $row)->getFont()->setBold(true)->getColor()->setARGB($invColor);
                        }
                    }
                    foreach (['P' => 14, 'Q' => 48, 'R' => 60] as $col => $width) {
                        $sheet->getColumnDimension($col)->setWidth($width);
                    }
                }
            },
        ];
    }



}
