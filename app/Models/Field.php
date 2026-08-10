<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Field extends Model
{
    //
    protected $fillable = [
        'parent_field_id',
        'name',
        'user_id',
        'status',
        'bank_id',
        'number'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function parent()
    {
    return $this->belongsTo(\App\Models\Field::class, 'parent_field_id');
    }

    public function children()
    {
    return $this->hasMany(\App\Models\Field::class, 'parent_field_id');
    }

    public function isTopLevel(): bool
    {
    return is_null($this->parent_field_id);
    }

    /**
     * The id that groups this office for dashboard aggregation and CRUD
     * scoping: its own id if it's top-level (e.g. Tema), or its parent's id
     * if it's a sub-office (e.g. Shai Hills -> Tema's id).
     */
    public function groupId(): int
    {
    return $this->parent_field_id ?? $this->id;
    }
 


}
