<?php

namespace App\Exports;

use App\Models\Salary;
// use Maatwebsite\Excel\Concerns\FromCollection;

// use Illuminate\Contracts\View\View;
// use Maatwebsite\Excel\Concerns\FromView;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;


class SalaryExport implements FromQuery, WithMapping , WithHeadings
{  
      use Exportable;  
    /**
    * @param Salary $salary
    */

    protected $month;

    public function __construct($month)
    {
        $this->month = $month;
    
    }

    public function query()
    {
        return Salary::query()->where('salary_month', $this->month)->select([
        'id',
        'employee_id',
        'employee_id',
        'department_id',
        'role_id',
        'field_id',
        'client_id',
        'location',
        'employee_id',
        'employee_id',
        'payment_type',
        'bank_id',
        'branch',
        'account_number',
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
        'ssnit_comp_cont_13',
        'ssnit_tobe_paid13_5',
        'cost_to_company',
        ]);
    }

    /**
    * @param Salary $salary
    */
    public function map($salary): array
    {
        return [
            $salary->id,
            "FWSS ". $salary->employee_id,
            $salary->employee?->name,
            $salary->department?->name,
            $salary->role?->name,
            $salary->field?->name,
            $salary->client?->name . " " .$salary->client?->business_name,
            $salary->location,
            $salary->paymentInfo?->ssnit_number,
            $salary->paymentInfo?->tin_number,
            $salary->payment_type,
            $salary->bank?->name,
            $salary->branch,
            $salary->account_number,
            $salary->basic_salary,
            $salary->allowances,
            $salary->airtime_allowance,
            $salary->overtime,
            $salary->reimbursements,
            $salary->transport_allowance,
            $salary->ssnit_tier2_5,
            $salary->tax,  
            $salary->ssnit_tier2_5d,   
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
            $salary->ssnit_comp_cont_13,                            
            $salary->ssnit_tobe_paid13_5,                            
            $salary->cost_to_company ,


        ];
    }
    


    public function headings(): array
    {
        return [
        '#',
        'Employee ID',
        'Name',
        'Department',
        'Role',
        'Field',
        'Client',
        'Location',
        'SSNIT',
        'TIN',
        'payment_type',
        'Bank',
        'Branch',
        'Account Number',
        'Basic Salary',
        'Allowances',
        'Airtime Allowance',
        'Overtime',
        'Reimbursements',
        'Transport Allowance',
        'SSNIT TIER 2 5%',
        'TAX',
        'SSNIT TIER 2 5% DED',
        'SSNIT TIER 1%',
        'Welfare',
        'Maintenance',
        'Absent',
        'Boot',
        'IOU',
        'Hostel',
        'Insurance',
        'Reprimand',
        'Scouter',
        'Raincoat',
        'Meal',
        'Loan',
        'Walkin',
        'Amnt Ded Cof Start Date',
        'Other Deductions',
        'Gross',
        'Total Deductions',
        'NET',
        'SNNT Companies Contribution 13%',
        'SNNT To Be Paid 13.5%',
        'Cost To Company',
        ];
    

    }

}


