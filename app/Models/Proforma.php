<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proforma extends Model
{
    //
    protected $fillable = [
        'client_id',
        'nhil',
        'getfund',
        'chrl',
        'sub_amount',
        'vat_amount',
        'sub_total',
        'total',
        'due_date',
        'invoice_month',
        'status',
        'user_id',
        'balance',
        'wht_amount',
        'amount_received',
        'vat7_value',
        'vat7_amount',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'invoice_month' => 'date',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(ProformaClient::class);
    }


}
