<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Model;

/**
 * How many guards a client needs per shift, from effective_month onwards
 * (until a later plan for the same client takes over).
 */
class ClientShiftPlan extends Model
{
    protected $fillable = [
        'client_id', 'effective_month', 'day_guards', 'night_guards',
        'day_start', 'day_end', 'night_start', 'night_end', 'note', 'user_id',
    ];

    protected $casts = [
        'effective_month' => DateOnly::class,
        'day_guards' => 'integer',
        'night_guards' => 'integer',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function totalGuards(): int
    {
        return (int) $this->day_guards + (int) $this->night_guards;
    }

    /** "Day & Night", "Day only", "Night only" or "No guards". */
    public function patternLabel(): string
    {
        return match (true) {
            $this->day_guards > 0 && $this->night_guards > 0 => 'Day & Night',
            $this->day_guards > 0 => 'Day only',
            $this->night_guards > 0 => 'Night only',
            default => 'No guards',
        };
    }
}
