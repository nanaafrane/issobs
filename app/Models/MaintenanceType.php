<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A kind of maintenance users can pick: Roadworthy, Insurance, Tyres, Servicing…
 *
 *  expense_type_id  the expense item (Main Cash column) it is paid under
 *  is_document      a certificate / policy with an expiry date (Roadworthy, Insurance)
 *  repeat_months    due again N months after it was done (or valid_until on the record)
 *  repeat_km        due again N km after it was done
 */
class MaintenanceType extends Model
{
    protected $fillable = ['name', 'expense_type_id', 'is_document', 'repeat_months', 'repeat_km', 'is_active', 'notes', 'created_by'];

    protected $casts = [
        'is_document' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class);
    }

    public function records()
    {
        return $this->hasMany(VehicleMaintenance::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    /** Is it tracked for "due" (expiry or interval)? */
    public function isTracked(): bool
    {
        return $this->is_document || $this->repeat_months || $this->repeat_km;
    }

    /** The expense item to use: its own, or Vehicle Repairs & Maintenance. */
    public function resolveExpenseType(): ExpenseType
    {
        return $this->expenseType
            ?? ExpenseType::whereRaw('LOWER(TRIM(name)) = ?', ['vehicle repairs & maintenance'])->first()
            ?? ExpenseType::create(['name' => 'Vehicle Repairs & Maintenance', 'scope' => 'field', 'created_by' => auth()->id()]);
    }
}
