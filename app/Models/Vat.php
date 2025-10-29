<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vat extends Model
{
    //
    public $nhil = 0.025;
    public $getfund = 0.025;
    public $chrl = 0.01;
    public $vat = 0.15;

    public function getNhilAmount($amount)
    {
        return $amount * $this->nhil;
    }

    public function getGetFundAmount($amount)
    {
        return $amount * $this->getfund;
    }

    public function getChrlAmount($amount)
    {
        return $amount * $this->chrl;
    }

    public function getVatAmount($amount)
    {
        return $amount * $this->vat;
    }


}
