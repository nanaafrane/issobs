<?php

namespace App\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * A DATE column that is always written as "Y-m-d".
 *
 * Laravel's 'date' cast writes "Y-m-d H:i:s", which MySQL trims but SQLite keeps,
 * and then string comparisons such as work_date <= '2026-10-31' and unique keys on
 * the date stop matching. Reading gives a Carbon at midnight, like 'date'.
 */
class DateOnly implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        return $value === null ? null : Carbon::parse($value)->startOfDay();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return ($value instanceof \DateTimeInterface ? Carbon::instance($value) : Carbon::parse($value))->toDateString();
    }
}
