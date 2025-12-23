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
        'department_id',
        'role_id',
        'client_id',
        'location',
        'account_number',
        'bank_id',
        'branch',
        'payment_type',
        'status1',
        'status2',
        'payment_status',
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

        
    ];

    protected $casts = [
        'salary_month' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }   

    public function client()
    {
        return $this->belongsTo(Client::class);
    } 
    
    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function paymentInfo()
    {
        return $this->hasOneThrough(PaymentInfo::class, Employee::class, 'id', 'employee_id', 'employee_id', 'id');
    }
 


}
