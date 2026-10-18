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

    /**
     * Field offices run by a user of this office: the office itself and its
     * sub-offices (Tema runs Tema and Shai Hills). Tema (id 3) always includes
     * Shai Hills (id 7), the rule the employee forms already use, in case the
     * sub-office link was never set on this database.
     *
     * @return int[]
     */
    public static function managedIds(?int $fieldId): array
    {
        if (! $fieldId) {
            return [];
        }
        $ids = array_merge([$fieldId], static::where('parent_field_id', $fieldId)->pluck('id')->map(fn ($id) => (int) $id)->all());
        if ($fieldId === 3) {
            $ids[] = 7;
        }

        return array_values(array_unique($ids));
    }
 


}
