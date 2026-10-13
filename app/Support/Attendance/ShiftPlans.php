<?php

namespace App\Support\Attendance;

use App\Models\Client;
use App\Models\ClientShiftPlan;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Guards-per-shift plans. A plan applies from its effective month until a later
 * plan for the same client replaces it, so changing the numbers in October never
 * changes September's shortages.
 */
class ShiftPlans
{
    public const DEFAULT_TIMES = [
        'day_start' => '06:00', 'day_end' => '18:00',
        'night_start' => '18:00', 'night_end' => '06:00',
    ];

    public static function monthStart(CarbonInterface|string|null $month): Carbon
    {
        if ($month instanceof CarbonInterface) {
            return Carbon::instance($month)->startOfMonth()->startOfDay();
        }
        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month .= '-01';
        }

        return ($month ? Carbon::parse($month) : Carbon::now())->startOfMonth()->startOfDay();
    }

    /** The plan in force for a client in the given month, or null if none has been set. */
    public static function forMonth(int $clientId, CarbonInterface|string|null $month): ?ClientShiftPlan
    {
        return ClientShiftPlan::where('client_id', $clientId)
            ->where('effective_month', '<=', self::monthStart($month)->toDateString())
            ->orderByDesc('effective_month')
            ->first();
    }

    /**
     * Plans in force for many clients in one month, keyed by client id.
     *
     * @return Collection<int, ClientShiftPlan>
     */
    public static function forMonthMany(array $clientIds, CarbonInterface|string|null $month): Collection
    {
        if (! $clientIds) {
            return collect();
        }

        return ClientShiftPlan::whereIn('client_id', $clientIds)
            ->where('effective_month', '<=', self::monthStart($month)->toDateString())
            ->orderByDesc('effective_month')
            ->get()
            ->unique('client_id')
            ->keyBy('client_id');
    }

    /**
     * Create or change the plan from $month onwards. Later plans already saved for
     * the same client are left alone (they still take over in their own month).
     */
    public static function save(Client $client, array $data, CarbonInterface|string|null $month, ?int $userId = null): ClientShiftPlan
    {
        $start = self::monthStart($month);

        $plan = ClientShiftPlan::updateOrCreate(
            ['client_id' => $client->id, 'effective_month' => $start->toDateString()],
            [
                'day_guards' => max(0, (int) ($data['day_guards'] ?? 0)),
                'night_guards' => max(0, (int) ($data['night_guards'] ?? 0)),
                'day_start' => self::time($data['day_start'] ?? null, self::DEFAULT_TIMES['day_start']),
                'day_end' => self::time($data['day_end'] ?? null, self::DEFAULT_TIMES['day_end']),
                'night_start' => self::time($data['night_start'] ?? null, self::DEFAULT_TIMES['night_start']),
                'night_end' => self::time($data['night_end'] ?? null, self::DEFAULT_TIMES['night_end']),
                'note' => $data['plan_note'] ?? null,
                'user_id' => $userId,
            ]
        );

        // clients.guards is the old free-text headcount that other screens still show:
        // keep it equal to the plan in force today.
        $current = self::forMonth($client->id, now());
        if ($current) {
            $client->forceFill(['guards' => (string) $current->totalGuards()])->saveQuietly();
        }

        return $plan;
    }

    /** "6:00" / "06:00" / "06:00:00" -> "06:00"; anything else -> $default. */
    public static function time(?string $value, string $default): string
    {
        if ($value !== null && preg_match('/^(\d{1,2}):(\d{2})/', trim($value), $m) && (int) $m[1] < 24 && (int) $m[2] < 60) {
            return sprintf('%02d:%02d', $m[1], $m[2]);
        }

        return $default;
    }

    /** Validation rules shared by the client forms and the attendance page. */
    public static function rules(bool $required = true): array
    {
        $req = $required ? 'required' : 'nullable';

        return [
            'day_guards' => [$req, 'integer', 'min:0', 'max:500'],
            'night_guards' => [$req, 'integer', 'min:0', 'max:500'],
            'day_start' => ['nullable', 'date_format:H:i'],
            'day_end' => ['nullable', 'date_format:H:i'],
            'night_start' => ['nullable', 'date_format:H:i'],
            'night_end' => ['nullable', 'date_format:H:i'],
            'plan_note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
