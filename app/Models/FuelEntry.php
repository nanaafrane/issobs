<?php

namespace App\Models;

use App\Models\Concerns\HasApprovalStages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One fill-up: the line of the FUEL REQUEST AND USAGE REPORT sheet.
 *
 * Same life as an overtime entry: submitted -> branch review -> finance approval -> owed ->
 * paid on a fuel pay-out (one expense + one payment per office and period). Once it is on a
 * pay-out it is locked.
 */
class FuelEntry extends Model
{
    use HasApprovalStages, SoftDeletes;

    protected $fillable = [
        'entry_date', 'vehicle_id', 'field_id', 'driver_employee_id', 'driver_name', 'purpose',
        'fuel_type', 'litres', 'price_per_litre', 'amount_requested', 'amount', 'station', 'odometer',
        'receipt_no', 'notes',
        'user_1', 'status_1', 'date_1',
        'user_2', 'status_2', 'date_2',
        'user_3', 'status_3', 'date_3',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'litres' => 'decimal:2',
        'price_per_litre' => 'decimal:3',
        'amount_requested' => 'decimal:2',
        'amount' => 'decimal:2',
        'date_1' => 'datetime',
        'date_2' => 'datetime',
        'date_3' => 'datetime',
    ];

    public function setEntryDateAttribute($v): void
    {
        $this->attributes['entry_date'] = $v ? \Carbon\Carbon::parse($v)->toDateString() : null;
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function driver()
    {
        return $this->belongsTo(employee::class, 'driver_employee_id');
    }

    public function payout()
    {
        return $this->belongsTo(FuelPayout::class, 'payout_id');
    }

    /** Approved and not on a pay-out: money owed to the station / office. */
    public function scopeOwed($q)
    {
        return $q->where('status_3', 'approved')->whereNull('payout_id');
    }

    public function isLockedForPay(): bool
    {
        return (bool) $this->payout_id;
    }

    public function driverLabel(): string
    {
        return $this->driver?->name ?: ($this->driver_name ?: '—');
    }
}
