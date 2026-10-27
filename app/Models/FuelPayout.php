<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Approved fuel for one office and period paid in one go — to the fuel station for its bill,
 * or in cash to whoever bought it. ONE expense (type Fuel) and ONE payment, so the bank or
 * Main Cash statement shows one line. See App\Services\Fleet\FuelPayouts.
 */
class FuelPayout extends Model
{
    protected $fillable = ['field_id', 'period_from', 'period_to', 'station', 'payee', 'bank_id', 'total', 'litres', 'notes', 'user_id'];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'cancelled_at' => 'datetime',
    ];

    public function setPeriodFromAttribute($v): void { $this->attributes['period_from'] = $v ? \Carbon\Carbon::parse($v)->toDateString() : null; }
    public function setPeriodToAttribute($v): void { $this->attributes['period_to'] = $v ? \Carbon\Carbon::parse($v)->toDateString() : null; }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function payment()
    {
        return $this->belongsTo(AccountPayment::class, 'payment_id');
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function entries()
    {
        return $this->hasMany(FuelEntry::class, 'payout_id');
    }

    public function label(): string
    {
        return 'Fuel pay-out #' . $this->id . ' — ' . ($this->field?->name ?? 'office') . ($this->station ? ' (' . $this->station . ')' : '') . ' '
            . $this->period_from?->format('d M') . '–' . $this->period_to?->format('d M Y');
    }
}
