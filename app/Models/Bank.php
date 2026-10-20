<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One of the company's money accounts.
 *
 *  kind = bank  a real bank account.
 *  kind = cash  cash on hand ("Main Cash" / petty cash), held by a custodian, funded
 *               from the banks and spent on approved expenses. Same ledger as a bank.
 *
 * total is the running balance; it always equals
 *   opening_balance + Σ credit − Σ debit of its bank_transactions.
 * It is never typed in: money only moves through deposits, receipts, transfers,
 * payments and adjustments, each of which posts a ledger line.
 *
 * Note: the same table also supplies the bank names on employee payment details
 * and payroll — use banksOnly() for those pickers so cash accounts never appear.
 */
class Bank extends Model
{
    public const KINDS = ['bank' => 'Bank account', 'cash' => 'Cash on hand'];

    protected $fillable = [
        'name',
        'kind',
        'branch',
        'acc_number',
        'custodian',
        'user_id',
        'total',            // set by code only (opening figure / postings) — never from a form
        'opening_balance',
        'opening_date',
    ];

    protected $casts = [
        'opening_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function setOpeningDateAttribute($value): void
    {
        $this->attributes['opening_date'] = $value ? \Carbon\Carbon::parse($value)->toDateString() : null;   // plain date in every database
    }

    protected static function booted(): void
    {
        // A new account starts at its opening balance, so total = opening + ledger.
        static::creating(function (Bank $bank) {
            $bank->kind ??= 'bank';
            if ($bank->opening_balance === null) {
                $bank->opening_balance = round((float) $bank->total, 2);
            }
            $bank->total ??= $bank->opening_balance;
            $bank->opening_date ??= now()->toDateString();
        });
    }

    /** Real bank accounts only (also used for employee / payroll bank pickers). */
    public function scopeBanksOnly(Builder $q): Builder
    {
        return $q->where(fn ($w) => $w->where('kind', 'bank')->orWhereNull('kind'));
    }

    public function scopeCashOnly(Builder $q): Builder
    {
        return $q->where('kind', 'cash');
    }

    /** Accounts money can still move through (closed accounts keep their history). */
    public function scopeOperating(Builder $q): Builder
    {
        return $q->where(fn ($w) => $w->where('is_active', true)->orWhereNull('is_active'));
    }

    public function isCash(): bool
    {
        return $this->kind === 'cash';
    }

    public function transactions()
    {
        return $this->hasMany(BankTransaction::class);
    }

    /** "GCB Main — 1234567" / "Main Cash (cash, held by Ama)". */
    public function getLabelAttribute(): string
    {
        if ($this->isCash()) {
            return $this->name . ' (cash' . ($this->custodian ? ', held by ' . $this->custodian : '') . ')';
        }

        return $this->name . ($this->acc_number ? ' — ' . $this->acc_number : '');
    }
}
