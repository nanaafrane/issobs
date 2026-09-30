<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class category extends Model
{
    //
    protected $fillable = [
        'name',
        'user_id',
        'client_id',
        'category_month'
    ];

        /** Client ids ticked for Category A every month (only while Active and not yet categorised). */
    public const DEFAULT_A_CLIENT_IDS = [
        100, 101, 103, 109, 110, 111, 112, 116, 118, 135, 173,176, 194, 276, 343, 440, 416,  413, 
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Note: there is no salaries.category_id column. A salary's category is its client's
    // category for that month - see App\Support\CategoryPayroll.

    public function client()
    {
        return $this->belongsTo(Client::class);
    }


    protected $casts = [
        'category_month' => 'date',
    ];


}
