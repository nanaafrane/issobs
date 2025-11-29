<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    //
    protected $fillable = [
        'salary_month',
        'employee_id',
        'field_id',
        'role_id',
        'client_id',
        'location',
        'account_number',
        'bank_name',
        'branch',
        'payment_type',
        'status1',
        'status2',
        'payment_status',
        'basic_salary',
        'allowances',
        'overtime',
        'reimbursements',
        'transport_allowance',
        'ssnit_tier2_5%',
        'tax',
        'ssnit_tier1_0.5%',
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
        'ssnit_comp_cont_13%',
        'ssnit_tobe_paid13.5%',
        'cost_to_company',

        
    ];

}
