<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One line of a client's account. Never edited or deleted — corrections are
 * new lines with reverses_id pointing at the line they cancel.
 */
class ClientLedgerEntry extends Model
{
    protected $fillable = [
        'client_id', 'entry_date', 'kind', 'source_key', 'invoice_id', 'receipt_id',
        'debit', 'credit', 'description', 'details', 'reverses_id', 'user_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'debit' => 'float',
        'credit' => 'float',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
