<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    //
    protected $fillable = [
        'invoice_id',
        'client_id',
        'from',
        'mode',
        'receipt_month',
        'dAmount',
        'description',
        'cheque_reference',
        'cheque_amount',
        'cheque_bank',
        'transfer_reference',
        'transfer_bank',
        'transfer_amount',
        'momo_transactin_id',
        'other_payment_descri',
        'other_payment_amnt',
        'momo_amount',
        'cash_amount',
        'user_id',
        'status',
        'total',
        'wht_amount',
        'amount_received',
        'vat7_value',
        'vat7_amount',
        'image',
    ];


    protected $casts = [
        'receipt_month' => 'date',
    ];


    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
