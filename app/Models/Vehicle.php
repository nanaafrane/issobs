<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A company vehicle or motorbike. Fuel entries and maintenance records point at it. */
class Vehicle extends Model
{
    public const KINDS = [
        'car' => 'Car / saloon',
        'pickup' => 'Pick-up',
        'suv' => '4x4 / SUV',
        'van' => 'Van / bus',
        'truck' => 'Truck',
        'motorbike' => 'Motorbike',
        'other' => 'Other',
    ];

    public const FUEL_TYPES = [
        'diesel' => 'Diesel',
        'super' => 'Super (petrol)',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'active' => 'In use',
        'off_road' => 'Off the road',
        'disposed' => 'Sold / disposed',
    ];

    protected $fillable = [
        'reg_no', 'name', 'kind', 'fuel_type', 'field_id', 'driver_employee_id', 'driver_name',
        'tank_litres', 'opening_odometer', 'status', 'notes', 'user_id',
    ];

    protected $casts = [
        'tank_litres' => 'decimal:2',
    ];

    /** GR 1788-21, gr-1788-21 and "GR-1788-21 " are the same plate. */
    public static function normaliseReg(?string $reg): string
    {
        return strtoupper(preg_replace('/[\s_]+/', '-', trim((string) $reg)));
    }

    public function setRegNoAttribute($v): void
    {
        $this->attributes['reg_no'] = self::normaliseReg($v);
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function driver()
    {
        return $this->belongsTo(employee::class, 'driver_employee_id');
    }

    public function fuelEntries()
    {
        return $this->hasMany(FuelEntry::class);
    }

    public function maintenances()
    {
        return $this->hasMany(VehicleMaintenance::class);
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    public function label(): string
    {
        return $this->reg_no . ($this->name ? ' · ' . $this->name : '');
    }

    public function driverLabel(): string
    {
        return $this->driver?->name ?: ($this->driver_name ?: '—');
    }

    /** Highest km reading known for this vehicle (fuel, maintenance or the opening reading). */
    public function lastOdometer(): ?int
    {
        $max = max(
            (int) $this->fuelEntries()->max('odometer'),
            (int) $this->maintenances()->max('odometer'),
            (int) $this->opening_odometer
        );

        return $max > 0 ? $max : null;
    }
}
