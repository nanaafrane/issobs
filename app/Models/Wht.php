<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wht extends Model
{
    //
    public $wht_rate = 0.075;

    public function getWithHoldingValue($amount)
    {
        return $amount * $this->wht_rate;
    }
}
