<?php

namespace App\Exports;

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

    public function __construct($month, $bank_id, array $headers)
    {
        $this->month = Carbon::parse($month);
        $this->bank_id = $bank_id;
        $this->dynamicHeaders = $headers;
    
    }

    
    public function query()
    {
        return Salary::query()->where('bank_id', $this->bank_id )->where('payment_type', 'Bank')->whereMonth('salary_month', $this->month->month)->select([
        'employee_id',
        'payment_status',
        'employee_id',
        'field_id',
        'role_id',
        'client_id',
        'location',
        'employee_id',
        'branch',
        'account_number',
        'net_salary',
        ]);
    }

    /**
    * @param Salary $salary
    */
    public function map($salary): array
    {
        return [
            "FWSS ". $salary->employee_id,
            $salary->payment_status,
            $salary->employee?->name,
            $salary->field?->name,
            $salary->role?->name,
            $salary->client?->name . " " .$salary->client?->business_name,
            $salary->location,
            $salary->paymentInfo?->branch_code,
            $salary->branch,
            $salary->account_number,                            
            $salary->net_salary

        ];
    }
    


    public function headings(): array
    {
        return [
        ['FIRST WATCH SECURITY SERVICES LTD' ],
        [ strtoupper($this->dynamicHeaders[0]) ],
        [ strtoupper($this->dynamicHeaders[1]) ],
        [       
        'EMPLOYEE ID',
        'STATUS',
        'NAME',
        'FIELD',
        'ROLE',
        'CLIENT',
        'LOCATION',
        'BRANCH CODE',
        'BRANCH',
        'ACCOUNT NUMBER',
        'NET'],

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
                $event->sheet->setCellValue('J4', 'Total');
                $event->sheet->setCellValue('K4', '=SUM(K6:K' . $lastRow . ')');

                // Optional: Style the total row (Bold) HOW TO GET DYNAMIC ROW ('J'.$totalRow.':K'.$totalRow)
                $event->sheet->getStyle('J4:K4')
                    ->getFont()->setBold(true)->setSize(14);
            },
        ];
    }



}
