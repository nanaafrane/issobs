<?php

namespace App\Imports;

use App\Models\Salary;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;


class SalaryImport implements ToModel, WithHeadingRow
{
      use Importable;

    public function model(array $row)
    {
        return new Salary([
            'employee_id' => $row['employee_id'],
            'field_id' => $row['field_id'],
            'department_id' => $row['department_id'],
            'role_id' => $row['role_id'],
            'client_id' => $row['client_id'],
            'location' => $row['location'],
            'account_number' => $row['account_number'],
            'bank_id' => $row['bank_id'],
            'branch' => $row['branch'],
            'payment_type' => $row['payment_type'],
            'basic_salary' => $row['basic_salary'],
            'allowances' => $row['allowance'],
            'user_id' => $row['user_id'],
            'salary_month' => $row['salary_month'],
            'gross_salary' => $row['gross_salary'],
            'total_deductions' => $row['total_deductions'],
            'net_salary' => $row['net_salary'],
            'ssnit_comp_cont_13' => $row['ssnit_comp_cont_13'],
            'ssnit_tobe_paid13_5' => $row['ssnit_tobe_paid13_5'],
            'cost_to_company' => $row['cost_to_company'],  
            'airtime_allowance' => $row['airtime_allowance'],
            'overtime' => $row['overtime'],
            'reimbursements' => $row['reimbursements'],
            'transport_allowance' => $row['transport_allowance'],
            'ssnit_tier2_5d' => $row['ssnit_tier2_5d'],
            'ssnit_tier2_5' => $row['ssnit_tier2_5'],
            'tax' => $row['tax'],
            'ssnit_tier1_0_5' => $row['ssnit_tier1_0_5'],
            'welfare' => $row['welfare'],
            'maintenance' => $row['maintenance'],
            'absent' => $row['absent'],
            'boot' => $row['boot'],
            'iou' => $row['iou'],
            'hostel' => $row['hostel'],
            'insurance' => $row['insurance'],
            'reprimand' => $row['reprimand'],
            'scouter' => $row['scouter'],
            'raincoat' => $row['raincoat'],
            'meal' => $row['meal'],
            'loan' => $row['loan'],
            'walkin' => $row['walkin'],
            'amnt_ded_cof_start_date' => $row['amnt_ded_cof_start_date'],
            'other_deductions' => $row['other_deductions'],    
            'payment_status' => $row['payment_status'],
            // map other columns as needed...
        ]);
    }

    public function uniqueBy()
    {
        return ['employee_id', 'salary_month'];
    }
}