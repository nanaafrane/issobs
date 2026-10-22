<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Someone the company has borrowed from (a director's loan, a shareholder, a lender).
 * balance = what the company owes them now = opening_balance + Σ entries.owed_change.
 * See App\Services\Banking\CreditorBook.
 */
class Creditor extends Model
{
    public const KINDS = [
        'director' => 'Director',
        'shareholder' => 'Shareholder',
        'lender' => 'Lender (bank / institution)',
        'other' => 'Other',
    ];

    protected $fillable = ['name', 'kind', 'phone', 'email', 'notes', 'opening_balance', 'opening_date', 'balance', 'user_id'];

    protected $casts = [
        'opening_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function setOpeningDateAttribute($v): void
    {
        $this->attributes['opening_date'] = $v ? \Carbon\Carbon::parse($v)->toDateString() : null;
    }

    public function entries()
    {
        return $this->hasMany(CreditorEntry::class);
    }

    public function getKindLabelAttribute(): string
    {
        return self::KINDS[$this->kind] ?? ucfirst((string) $this->kind);
    }
}
