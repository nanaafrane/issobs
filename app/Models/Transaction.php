<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Client;

class Transaction extends Model
{
    //

    protected $fillable = [
        'client_id',
        'invoice_id',
        'invoice_amount',
        'receipt_id',
        'receipt_amount',
        'balance',
        'status',
        'checks'
    ];

    public function invoice ()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }

    public function client ()
    {
        return $this->belongsTo(Client::class);
    }

}
