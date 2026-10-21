<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One overtime pay-out: an office's approved overtime for a period, paid in one go.
 *
 *  cash     one payment (bank or Main Cash) to the office officer, who pays each guard;
 *  momo     one payment from a bank by mobile money; each guard's transfer is ticked off;
 *  payroll  no money moves now: the entries are paid with the salaries of payroll_month.
 *
 * For cash / MoMo the pay-out has ONE expense (type Overtime) and ONE payment, so the bank
 * or cash statement shows one line. See App\Services\Overtime\OvertimePayouts.
 */
class OvertimePayout extends Model
{
    public const METHODS = [
        'cash' => 'Cash, through the office officer',
        'momo' => 'MoMo from head office',
        'payroll' => 'With the monthly salary',
    ];

    protected $fillable = ['field_id', 'period_from', 'period_to', 'method', 'payroll_month', 'bank_id', 'held_by', 'total', 'notes', 'user_id'];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'payroll_month' => 'date',
        'closed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function setPeriodFromAttribute($v): void { $this->attributes['period_from'] = $v ? \Carbon\Carbon::parse($v)->toDateString() : null; }
    public function setPeriodToAttribute($v): void { $this->attributes['period_to'] = $v ? \Carbon\Carbon::parse($v)->toDateString() : null; }
    public function setPayrollMonthAttribute($v): void { $this->attributes['payroll_month'] = $v ? \Carbon\Carbon::parse($v)->startOfMonth()->toDateString() : null; }

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
        return $this->hasMany(Overtime::class, 'payout_id');
    }

    public function label(): string
    {
        return 'Overtime pay-out #' . $this->id . ' — ' . ($this->field?->name ?? 'office') . ' '
            . $this->period_from?->format('d M') . '–' . $this->period_to?->format('d M Y');
    }
}
