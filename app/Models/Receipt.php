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
        'advance_payment',
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
        'coll_status',
        'user_id1',
        'coll_date',
        'bran_status',
        'user_id2',
        'bran_date',
        'ho_status',
        'ho_date',
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
        'coll_date' => 'date',
        'bran_date' => 'date',
        'ho_date' => 'date',
        'mode' => 'array',
    ];

    public function hasMode(string $mode): bool
    {
        return in_array($mode, $this->mode ?? [], true);
    }


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

    public function user1()
    {
        return $this->belongsTo(User::class, 'user_id1');
    }

    public function user2()
    {
        return $this->belongsTo(User::class, 'user_id2');
    }

}
