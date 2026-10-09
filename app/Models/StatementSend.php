<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A statement of account that was emailed to a client. */
class StatementSend extends Model
{
    protected $fillable = ['client_id', 'sent_to', 'period_from', 'period_to', 'closing_balance', 'with_pdf', 'user_id'];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'closing_balance' => 'float',
        'with_pdf' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
