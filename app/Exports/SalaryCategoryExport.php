<?php

namespace App\Exports;

use App\Models\category;
use App\Models\Salary;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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


class SalaryCategoryExport implements FromQuery, WithMapping , WithHeadings 
{
      use Exportable;  
    /**
    * @param Salary $salary
    */

        protected $month;
        protected $category;
        // protected $dynamicHeaders;

            // 1. Define the counter property
        private $rowNumber = 0;

    public function __construct($month, $category)
    {
        $this->month = Carbon::parse($month);
        $this->category = $category;
        // $this->dynamicHeaders = $headers;
    
    }

    
    public function query()
    {
        
        $CategoryClient = category::with(['salaries' => function ($query) {
            $query->whereMonth('salary_month', $this->month->month)->whereIn('payment_status', ['pending', 'approved']);
        }, 'salaries.client'])->where('name', $this->category)->whereMonth('category_month', $this->month->month)->pluck('client_id')->toArray();

        
        // $clientEployees = Salary::whereMonth('salary_month', $this->month->month)->whereIn('payment_status', ['pending', 'approved'])->whereIn('client_id', $CategoryClient)->get();
        // dd($clientEployees);
        return Salary::query()->whereMonth('salary_month', $this->month->month)->whereIn('payment_status', ['pending', 'approved'])->whereIn('client_id', $CategoryClient)->select([
            'employee_id',
            'payment_status',
            'employee_id',
            'department_id',
            'role_id',
            'field_id',
            'client_id',
            'location',
            'payment_type',
            'bank_id',
            'account_number',
            'branch',
            'employee_id',
            'employee_id',
            'employee_id',
            'basic_salary',
            'allowances',
            'airtime_allowance',
            'overtime',
            'reimbursements',
            'transport_allowance',
            'ssnit_tier2_5d',
            'ssnit_tier2_5',
            'tax',
            'ssnit_tier1_0_5',
            'welfare',
            'maintenance',  
            'absent',
            'boot',
            'iou',
            'hostel',
            'insurance',
            'reprimand',
            'scouter',
            'raincoat',
            'meal',
            'loan',
            'walkin',
            'amnt_ded_cof_start_date',
            'other_deductions',
            'gross_salary',
            'total_deductions',
            'net_salary',
            'user_id',
            'updated_at',
            'user_id1',
            'updated_at',

        ]);
    }

    /**
    * @param Salary $salary
    */
    public function map($salary): array
    {
        return [
             ++$this->rowNumber,
            "FWSS ". $salary->employee_id,
            $salary->payment_status,
            $salary->employee?->name,
            $salary->department?->name,
            $salary->role?->name,
            $salary->field?->name,
            $salary->client?->name . "  " . $salary->client?->business_name,
            $salary->location,
            $salary->payment_type,
            $salary->bank?->name,
            $salary->account_number,
            $salary->branch,
            $salary->paymentInfo?->branch_code,
            $salary->paymentInfo?->ssnit_number,
            $salary->paymentInfo?->tin_number,
            $salary->basic_salary,
            $salary->allowances,
            $salary->airtime_allowance,
            $salary->overtime,
            $salary->reimbursements,
            $salary->transport_allowance,
            $salary->ssnit_tier2_5d,
            $salary->ssnit_tier2_5,
            $salary->tax,
            $salary->ssnit_tier1_0_5,
            $salary->welfare,
            $salary->maintenance,
            $salary->absent,
            $salary->boot,
            $salary->iou,
            $salary->hostel,
            $salary->insurance,
            $salary->reprimand,
            $salary->scouter,
            $salary->raincoat,
            $salary->meal,
            $salary->loan,
            $salary->walkin,
            $salary->amnt_ded_cof_start_date,
            $salary->other_deductions,
            $salary->gross_salary,
            $salary->total_deductions,
            $salary->net_salary,
            $salary->user?->name,
            $salary->updated_at?->format('F l d, Y, H:i A'),
            $salary->user1?->name,
            $salary->updated_at->diffForHumans(),  

        ];
    }
    


    public function headings(): array
    {
        return [
        [  
        '#',     
        'EMPLOYEE ID',
        'STATUS',
        'NAME',
        'DEPARTMENT',
        'ROLE',
        'FIELD',
        'CLIENT',
        'LOCATION',
        'PAYMENT TYPE',
        'BANK',
        'ACCOUNT NUMBER',
        'BRANCH',
        'BRANCH CODE',
        'SSNIT NUMBER',
        'TIN NUMBER',
        'BASIC SALARY',
        'ALLOWANCES',
        'AIRTIME ALLOWANCE',
        'OVERTIME',
        'REIMBURSEMENTS',
        'TRANSPORT ALLOWANCE',
        'SSNIT TIER 2.5D',
        'SSNIT TIER 2.5',
        'TAX',
        'SSNIT TIER 1.0.5',
        'WELFARE',
        'MAINTENANCE',
        'ABSENT',
        'BOOT',
        'IOU',
        'HOSTEL',
        'INSURANCE',
        'REPRIMAND',
        'SCOUTER',
        'RAINCOAT',
        'MEAL',
        'LOAN',
        'WALKIN',
        'AMNT DED COF START DATE',
        'OTHER DEDUCTIONS',
        'GROSS SALARY',
        'TOTAL DEDUCTIONS',
        'NET SALARY',
        'CREATED BY',
        'UPDATED',
        'UPDATED BY',
        'PERIOD',
        
        ],

        ];
    

    }



}
