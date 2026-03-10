<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Invoice;

class InvoiceData extends Model
{

        protected $fillable = [
        'invoice_number',
        'service_name',
        'description',
        'quantity',
        'unit_price',
        'amount',
    ];

    //
        /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'invoice_data';

    protected $primaryKey = 'invoice_number';
    public $incrementing = false; // if the key is not auto-incrementing



    public function invoice() {
        return $this->belongsTo(Invoice::class);
    }



}
