<?php

namespace App\Models;

use App\Models\Concerns\HasApprovalStages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One maintenance job or renewal on one vehicle (tyres, roadworthy, insurance, repairs…).
 *
 * Same approval chain as overtime. A job is one bill from one garage / insurer, so once fully
 * approved it becomes ONE expense (the type's expense item) that finance pays from Payments ›
 * Pay approved expenses, like any other bill. Once that expense is paid the record is locked.
 * See App\Services\Fleet\MaintenanceExpenses.
 */
class VehicleMaintenance extends Model
{
    use HasApprovalStages, SoftDeletes;

    protected $fillable = [
        'vehicle_id', 'maintenance_type_id', 'field_id', 'service_date', 'vendor', 'reference', 'description',
        'amount', 'odometer', 'valid_until', 'next_due_km',
        'user_1', 'status_1', 'date_1',
        'user_2', 'status_2', 'date_2',
        'user_3', 'status_3', 'date_3',
    ];

    protected $casts = [
        'service_date' => 'date',
        'valid_until' => 'date',
        'amount' => 'decimal:2',
        'date_1' => 'datetime',
        'date_2' => 'datetime',
        'date_3' => 'datetime',
    ];

    public function setServiceDateAttribute($v): void { $this->attributes['service_date'] = $v ? \Carbon\Carbon::parse($v)->toDateString() : null; }
    public function setValidUntilAttribute($v): void { $this->attributes['valid_until'] = $v ? \Carbon\Carbon::parse($v)->toDateString() : null; }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function type()
    {
        return $this->belongsTo(MaintenanceType::class, 'maintenance_type_id');
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class)->withTrashed();
    }

    public function isPaid(): bool
    {
        return (bool) $this->expense && ! $this->expense->trashed() && $this->expense->isPaid();
    }

    public function isLockedForPay(): bool
    {
        return $this->isPaid();
    }

    public function payState(): string
    {
        if ($this->isPaid()) {
            return 'paid';
        }

        return $this->expense_id && $this->isFullyApproved() ? 'awaiting payment' : '';
    }
}
