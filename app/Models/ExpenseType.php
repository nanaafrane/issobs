<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseType extends Model
{
    protected $fillable = ['name', 'scope', 'is_accommodation', 'created_by'];

    protected $casts = [
        'is_accommodation' => 'boolean',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Fields that typically use this expense type (used for the soft
     * "unusual pick" warning on the create form, not for restriction).
     */
    public function fields(): BelongsToMany
    {
        return $this->belongsToMany(Field::class, 'field_expense_type');
    }

    public function scopeField($query)
    {
        return $query->where('scope', 'field');
    }

    public function scopeCorporate($query)
    {
        return $query->where('scope', 'corporate');
    }
}
