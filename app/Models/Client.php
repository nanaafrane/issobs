<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'name',
        'phone_number',
        'phone_number1',
        'business_name',
        'branch',
        'address',
        'gps',
        'map',
        'field_id',
        'status',  
        'status_date',   
        'rate',
        'guards',
        'start_date',
        'scope_of_work',
        'state_institution',
       
        'coll_status',
        'coll_date',
        'user_id',

        'bran_status',
        'bran_date',
        'user_id1',

        'ho_status',
        'ho_date',
        'user_id2',

        'category_id',
        'category_name',
        'category_month',
    ];

    protected $casts = [
        'start_date' => 'date',
        'status_date' => 'date',
        'coll_date' => 'date',
        'bran_date' => 'date',
        'ho_date' => 'date',
    ];


    public function field(){
        return $this->belongsTo(Field::class);
    }

    public function employees() : BelongsToMany
    {
        return $this->belongsToMany(employee::class);
    }


    public function invoices () : HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function receipts () : HasMany
    {
        return $this->hasMany(Receipt::class);
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

    public function category()
    {
        return $this->belongsTo(category::class);
    }


}
