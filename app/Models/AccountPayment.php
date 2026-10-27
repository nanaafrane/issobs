<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Money paid out of one of our accounts to someone else. */
class AccountPayment extends Model
{
    public const METHODS = [
        'cheque' => 'Cheque',
        'transfer' => 'Bank transfer',
        'momo' => 'Mobile money',
        'cash' => 'Cash',
    ];

    public const CATEGORIES = [
        'expenses' => 'Approved expenses',
        'overtime' => 'Overtime pay-outs',
        'fuel' => 'Fuel pay-outs',
        'supplier' => 'Supplier / service provider',
        'salaries' => 'Salaries & wages',
        'statutory' => 'Tax, SSNIT & statutory',
        'bank_charges' => 'Bank charges',
        'rent_utilities' => 'Rent & utilities',
        'loan' => 'Loan repayment (old — use Creditors)',
        'interest' => 'Interest on loans',
        'refund' => 'Client refund',
        'owner' => 'Owner / director drawings',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'paid' => 'Paid',
        'issued' => 'Cheque issued',
        'cleared' => 'Cheque cleared',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = ['bank_id', 'payee', 'method', 'reference', 'amount', 'payment_date', 'category', 'description', 'status', 'user_id'];

    protected $casts = [
        'payment_date' => 'date',
        'cleared_date' => 'date',
        'cancelled_at' => 'datetime',
    ];

    public function setPaymentDateAttribute($value): void
    {
        $this->attributes['payment_date'] = $value ? \Carbon\Carbon::parse($value)->toDateString() : null;   // plain date in every database
    }

    public function setClearedDateAttribute($value): void
    {
        $this->attributes['cleared_date'] = $value ? \Carbon\Carbon::parse($value)->toDateString() : null;   // plain date in every database
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'payment_id');
    }

    public function bankLines()
    {
        return $this->hasMany(BankTransaction::class, 'payment_id')->orderBy('id');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string) $this->category);
    }
}
