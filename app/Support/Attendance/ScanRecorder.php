<?php

namespace App\Support\Attendance;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\employee;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * A guard scanned the QR poster at a client's site and entered FWSS number + PIN.
 *
 * The QR proves which site's poster was scanned, not who scanned it or where
 * they are standing, so the scan is checked against what we know:
 *
 *  - PIN            proves who (5 wrong tries lock that employee number for 15 minutes)
 *  - roster         is the guard posted to this client?
 *  - shift          does the time fit the guard's own shift?
 *  - GPS            is the phone within the site radius? (a photo of the poster scanned at home fails here)
 *  - phone          is it the guard's usual phone, and not one already used by another guard this shift?
 *
 * A scan that fails any check is still saved, as "pending" (shown "?" in the
 * grid), and waits in the review queue. It never counts as present until
 * operations approve it, and the guard is never simply turned away.
 */
class ScanRecorder
{
    public const MAX_PIN_TRIES = 5;
    public const LOCK_SECONDS = 900;

    /**
     * @param  array{employee_number?:string, pin?:string, latitude?:mixed, longitude?:mixed, accuracy?:mixed, device_id?:string}  $input
     * @return array{ok: bool, status?: string, message: string, attendance?: Attendance}
     */
    public static function record(Client $client, array $input, ?CarbonInterface $at = null, ?string $ip = null): array
    {
        $at = Carbon::instance($at ?? now());
        $id = self::employeeId($input['employee_number'] ?? null);
        $wrong = ['ok' => false, 'message' => 'Employee number or PIN is wrong.'];

        if (! $id) {
            return ['ok' => false, 'message' => 'Enter your employee number, e.g. FWSS 245.'];
        }

        $lockKey = 'attendance-pin:' . $id;
        if (RateLimiter::tooManyAttempts($lockKey, self::MAX_PIN_TRIES)) {
            $minutes = (int) ceil(RateLimiter::availableIn($lockKey) / 60);

            return ['ok' => false, 'message' => "Too many wrong PINs. Try again in {$minutes} minute(s) or call your supervisor."];
        }

        $guard = employee::find($id);
        if (! $guard) {
            RateLimiter::hit($lockKey, self::LOCK_SECONDS);

            return $wrong;
        }
        if (! $guard->attendance_pin) {
            return ['ok' => false, 'message' => 'You do not have an attendance PIN yet. Ask your supervisor or operations for one.'];
        }
        if (! Hash::check((string) ($input['pin'] ?? ''), $guard->attendance_pin)) {
            RateLimiter::hit($lockKey, self::LOCK_SECONDS);

            return $wrong;
        }
        RateLimiter::clear($lockKey);

        if (! in_array($guard->status, Roster::ACTIVE_STATUSES, true)) {
            return ['ok' => false, 'message' => 'Your employee record is not active. Please see operations.'];
        }

        $plan = ShiftPlans::forMonth($client->id, $at);
        $posted = Roster::isPosted($guard, $client);
        $ownShift = $posted ? Roster::shiftOf($guard, $client) : null;
        $slot = ShiftClock::resolve($at, $plan, $ownShift);
        $date = $slot['date']->toDateString();
        $shift = $slot['shift'];

        $existing = Attendance::where('employee_id', $guard->id)->where('work_date', $date)->where('shift', $shift)->first();
        if ($existing) {
            if ($existing->source === 'scan' && $existing->checked_in_at) {
                return ['ok' => true, 'status' => $existing->status, 'attendance' => $existing,
                    'message' => "You already signed in for the {$shift} shift of " . $slot['date']->format('D j M') . ' at ' . $existing->checked_in_at->format('H:i') . '.'];
            }

            return ['ok' => true, 'status' => $existing->status, 'attendance' => $existing,
                'message' => 'Operations have already recorded you as ' . strtolower(Attendance::LABELS[$existing->status] ?? $existing->status) . " for this {$shift} shift."];
        }

        // ---- checks -------------------------------------------------------------------------
        $flags = [];
        $notes = [];

        if (! $posted) {
            $flags[] = 'Not on this client\'s roster';
        }
        if (! $slot['in_own_shift']) {
            $flags[] = "Signed in for the {$shift} shift but is rostered on " . ($ownShift ?: 'another') . ' shift';
        }
        if ($slot['late_minutes'] > ShiftClock::LATE_AFTER_MINUTES) {
            $notes[] = 'Late by ' . self::duration($slot['late_minutes']);
        }

        $lat = self::coord($input['latitude'] ?? null, 90);
        $lng = self::coord($input['longitude'] ?? null, 180);
        $accuracy = isset($input['accuracy']) && is_numeric($input['accuracy']) ? (int) round((float) $input['accuracy']) : null;
        $distance = null;

        if ($lat === null || $lng === null) {
            $flags[] = 'Phone did not share its location';
        } elseif ($client->latitude !== null && $client->longitude !== null) {
            $distance = (int) round(Geo::metres((float) $client->latitude, (float) $client->longitude, $lat, $lng));
            // Allow for the phone's own uncertainty, up to 100 m of it.
            $allowed = (int) ($client->attendance_radius_m ?: 200) + min($accuracy ?? 0, 100);
            if ($distance > $allowed) {
                $flags[] = 'Scanned ' . self::metres($distance) . ' from the site (allowed ' . self::metres($allowed) . ')';
            }
        } else {
            $notes[] = 'Site location not set, distance not checked';
        }

        $device = self::deviceId($input['device_id'] ?? null);
        if (! $device) {
            $flags[] = 'Phone could not be identified';
        } else {
            $sharedWith = Attendance::where('device_id', $device)->where('work_date', $date)->where('shift', $shift)
                ->where('employee_id', '!=', $guard->id)->with('employee:id,name')->first();
            if ($sharedWith) {
                $flags[] = 'Same phone already signed in ' . ($sharedWith->employee->name ?? 'another guard') . ' (FWSS ' . $sharedWith->employee_id . ') this shift';
            }

            $owner = employee::where('attendance_device', $device)->where('id', '!=', $guard->id)->first(['id', 'name']);
            if ($guard->attendance_device && $guard->attendance_device !== $device) {
                $flags[] = 'Different phone from the one this guard normally uses';
            } elseif ($owner) {
                $flags[] = 'Phone belongs to ' . $owner->name . ' (FWSS ' . $owner->id . ')';
            } elseif (! $guard->attendance_device) {
                // First scan: this phone becomes the guard's phone.
                $guard->forceFill(['attendance_device' => $device])->saveQuietly();
                $notes[] = 'Phone registered for this guard';
            }
        }

        try {
            $attendance = Attendance::create([
                'client_id' => $client->id,
                'employee_id' => $guard->id,
                'work_date' => $date,
                'shift' => $shift,
                'status' => $flags ? Attendance::PENDING : Attendance::PRESENT,
                'source' => 'scan',
                'checked_in_at' => $at,
                'latitude' => $lat,
                'longitude' => $lng,
                'accuracy_m' => $accuracy,
                'distance_m' => $distance,
                'device_id' => $device,
                'ip' => $ip,
                'flags' => $flags ?: null,
                'note' => $notes ? implode('; ', $notes) : null,
            ]);
        } catch (UniqueConstraintViolationException) {
            // The same guard pressed "Sign in" twice at once: the first one won.
            return ['ok' => true, 'status' => Attendance::PRESENT, 'message' => "You are already signed in for the {$shift} shift."];
        }

        $first = trim(explode(' ', trim((string) $guard->name))[0] ?? '');
        $when = ucfirst($shift) . ' shift, ' . $slot['date']->format('D j M');

        return $flags
            ? ['ok' => true, 'status' => Attendance::PENDING, 'attendance' => $attendance,
                'message' => "Thank you {$first}. Your sign-in ({$when}, {$at->format('H:i')}) was received and will be checked by operations: " . implode('; ', $flags) . '.']
            : ['ok' => true, 'status' => Attendance::PRESENT, 'attendance' => $attendance,
                'message' => "Thank you {$first}. You are signed in: {$when}, {$at->format('H:i')}."];
    }

    /** "FWSS 45", "fwss45", "45" -> 45 (same rule as the employee import). */
    public static function employeeId(?string $value): ?int
    {
        if ($value === null || ! preg_match('/^\s*(?:FWSS\s*)?(\d+)\s*$/i', $value, $m)) {
            return null;
        }

        return (int) $m[1] ?: null;
    }

    private static function coord(mixed $v, int $max): ?float
    {
        return is_numeric($v) && abs((float) $v) <= $max ? round((float) $v, 7) : null;
    }

    private static function deviceId(?string $v): ?string
    {
        $v = trim((string) $v);

        return preg_match('/^[A-Za-z0-9-]{16,64}$/', $v) ? $v : null;
    }

    private static function metres(int $m): string
    {
        return $m >= 1000 ? round($m / 1000, 1) . ' km' : $m . ' m';
    }

    private static function duration(int $minutes): string
    {
        return $minutes >= 60 ? intdiv($minutes, 60) . 'h ' . str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT) . 'm' : $minutes . ' min';
    }
}
