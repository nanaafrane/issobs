<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class employee extends Model
{
    //

    protected $fillable = [
        'image',  
        'name',
        'gender',
        'phone_number',
        'channel',
        'date_of_birth',
        'nia_number',
        'address',
        'marital_status',
        'worker_type',
        'date_of_joining',

        'department_id',
        'role_id',
        'field_id',
        'client_id',
        'location', 
        'basic_salary',
        'allowances',
        'user_id',

        'tax_button',
        'ssnit_button',
        'tin_number',
        'ssnit_number',
       
        'message',
        'responseCode',
        'reg_name',
        'status_momo',
        
        'status',
        'status_date', //terminiate and reinstate

        'assit_status',
        'assit_date',
        'user_id',

        'bran_status',
        'bran_date',
        'user_id1',

        'ho_status',
        'ho_date',
        'user_id2',

        'payment_type',
        'payment_infos_id',
        'salary_id',

        'gurantor_name',
        'gurantor_number',
        'gurantor_address',
        'gurantor_nia_number',
        // 'gurantor_nia_image',
        'relationship',
        
    ];


    /** Keep the stored payment priority in step with client / field / location / gender. */
    protected static function booted(): void
    {
        static::saving(function (self $employee) {
            // Every employee belongs to a field office: refuse to create one without it, or to
            // clear it. Last line of defence behind the forms, bulk upload and bulk update.
            // Older employees saved without one can still be saved for other changes.
            if ((! $employee->exists || $employee->isDirty('field_id')) && empty($employee->field_id)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['field_id' => 'Field Office is required.']);
            }
            if (! $employee->exists || $employee->isDirty(['client_id', 'field_id', 'location', 'gender'])) {
                [$employee->pay_priority, $employee->pay_priority_reason] = \App\Support\PayPriority::evaluate(
                    $employee->client_id, $employee->field_id, $employee->location, $employee->gender
                );
            }
        });

        // HR movements: head office approved this employee - by the Approve button, by saving
        // the edit form, by a bulk update, whatever the screen. The waiting request (termination,
        // re-instatement, new recruit) is approved with it, so it always counts in the report.
        static::updated(function (self $employee) {
            if ($employee->wasChanged('ho_status') && $employee->ho_status === 'approved') {
                \App\Support\EmployeeMovements::approveWaiting($employee);
            }
        });

        // HR movements: an active employee moved to another field office (edit form, bulk update).
        static::updated(function (self $employee) {
            // Employed before this save: an edit (form or bulk update) can put an active
            // employee back to Pending for approval in the same save that moves their office.
            if ($employee->wasChanged('field_id') && $employee->getOriginal('field_id')
                && ($employee->status === 'Active' || $employee->getOriginal('status') === 'Active')) {
                \App\Support\EmployeeMovements::transferred($employee, $employee->getOriginal('field_id'));
            }
            // Date of joining corrected on the edit form / bulk update: the hire follows it.
            if ($employee->wasChanged('date_of_joining') && $employee->date_of_joining) {
                \App\Models\EmployeeMovement::where('employee_id', $employee->id)->where('type', \App\Support\EmployeeMovements::HIRE)
                    ->whereIn('status', [\App\Support\EmployeeMovements::PENDING, \App\Support\EmployeeMovements::APPROVED])
                    ->latest('id')->first()?->update(['effective_date' => $employee->date_of_joining->toDateString(), 'date_estimated' => false]);
            }
        });

        // Salaries snapshot client / field / location, but gender comes from the employee.
        static::saved(function (self $employee) {
            if ($employee->wasChanged('gender')) {
                \App\Support\PayPriority::recomputeSalaries([$employee->id]);
            }
        });
    }

    /** The attendance PIN is a hash; never send it to a view or JSON. */
    protected $hidden = ['attendance_pin'];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_of_joining' => 'date',  
        'status_date' => 'date',  
        'assit_date' => 'date',  
        'bran_date' => 'date',  
        'ho_date' => 'date',  
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    } 
    

    public function paymentInfo()
    {
        return $this->hasOne(PaymentInfo::class);
    }

    public function salary()
    {
        return $this->hasMany(Salary::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function user1()
    {
        return $this->belongsTo(User::class, 'user_id1');
    }

    public function user2()
    {
        return $this->belongsTo(User::class, 'user_id2');
    }

    public function clients() : BelongsToMany
    {
        return $this->belongsToMany(Client::class)->withPivot('shift', 'location');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

}
