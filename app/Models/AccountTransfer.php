<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Money moved between two of our own accounts (e.g. a cheque from GCB to fund Main Cash). */
class AccountTransfer extends Model
{
    public const METHODS = [
        'cheque' => 'Cheque',
        'transfer' => 'Bank transfer',
        'withdrawal' => 'Cash withdrawal',
        'deposit' => 'Cash paid into bank',
        'handover' => 'Cash handed over',
    ];

    /** How the money arrived (newer moves; older rows have none). */
    public const IN_METHODS = [
        'transfer' => 'Transfer received',
        'cheque' => 'Cheque deposited',
        'cash' => 'Cash',
    ];

    protected $fillable = ['batch', 'leg_out', 'leg_in', 'from_bank_id', 'to_bank_id', 'amount', 'method', 'reference',
        'in_method', 'in_reference', 'transfer_date', 'in_date', 'notes', 'user_id'];

    protected $casts = [
        'transfer_date' => 'date',
        'in_date' => 'date',
        'reversed_at' => 'datetime',
    ];

    public function setInDateAttribute($value): void
    {
        $this->attributes['in_date'] = $value ? \Carbon\Carbon::parse($value)->toDateString() : null;
    }

    /** "Cheque 000457 → cash", "Bank transfer", "Cheque 12 → deposited". */
    public function routeLabel(): string
    {
        $out = (self::METHODS[$this->method] ?? ucfirst((string) $this->method)) . ($this->reference ? ' ' . $this->reference : '');
        $in = match (true) {
            $this->in_method === 'cash' && $this->method === 'cheque' => ' → cashed',
            $this->in_method === 'cash' && $this->method === 'withdrawal' && ! $this->to?->isCash() => ' → deposited as cash',
            $this->in_method === 'cheque' => ' → deposited',
            default => '',
        };

        return $out . $in;
    }

    public function setTransferDateAttribute($value): void
    {
        $this->attributes['transfer_date'] = $value ? \Carbon\Carbon::parse($value)->toDateString() : null;   // plain date in every database
    }

    public function from()
    {
        return $this->belongsTo(Bank::class, 'from_bank_id');
    }

    public function to()
    {
        return $this->belongsTo(Bank::class, 'to_bank_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }
}
