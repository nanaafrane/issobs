<?php

namespace App\Support\Attendance;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\employee;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Supervisor kiosk: a logged-in supervisor at the site marks guards on their own
 * phone (tap the guard, or scan the guard's ID-card QR). For guards without a
 * smartphone, or when a guard's phone is dead.
 *
 * The supervisor's login is the proof of who marked it; the supervisor's GPS is
 * the proof it was done at the site. A mark made away from the site (or with no
 * location) is held for review like a guard's own scan.
 */
class KioskRecorder
{
    /**
     * @param  array{latitude?:mixed, longitude?:mixed, accuracy?:mixed, device_id?:string}  $where
     * @return array{ok: bool, status?: string, message: string}
     */
    public static function record(Client $client, employee $guard, string $date, string $shift, string $status, User $supervisor, array $where, ?CarbonInterface $at = null): array
    {
        $at = Carbon::instance($at ?? now());

        // A guard from another post can be marked present here (moved / relief for the shift),
        // but an absence belongs on their own post's sheet.
        $relief = ! Roster::isPosted($guard, $client);
        if ($relief && $status !== Attendance::PRESENT) {
            return ['ok' => false, 'message' => $guard->name . ' is not posted to this client. Mark an absence on their own client\'s sheet.'];
        }
        if ($relief && ! $guard->client_id) {
            return ['ok' => false, 'message' => $guard->name . ' has no client of their own, so they can\'t be marked as moved here.'];
        }
        if (! in_array($guard->status, Roster::ACTIVE_STATUSES, true)) {
            return ['ok' => false, 'message' => $guard->name . '\'s employee record is not active.'];
        }

        $flags = [];
        $lat = is_numeric($where['latitude'] ?? null) ? round((float) $where['latitude'], 7) : null;
        $lng = is_numeric($where['longitude'] ?? null) ? round((float) $where['longitude'], 7) : null;
        $accuracy = is_numeric($where['accuracy'] ?? null) ? (int) round((float) $where['accuracy']) : null;
        $distance = null;

        if ($status === Attendance::PRESENT) {
            if ($lat === null || $lng === null) {
                $flags[] = 'Supervisor\'s phone did not share its location';
            } elseif ($client->latitude !== null && $client->longitude !== null) {
                $distance = (int) round(Geo::metres((float) $client->latitude, (float) $client->longitude, $lat, $lng));
                $allowed = (int) ($client->attendance_radius_m ?: 200) + min($accuracy ?? 0, 100);
                if ($distance > $allowed) {
                    $flags[] = 'Supervisor marked it ' . $distance . ' m from the site (allowed ' . $allowed . ' m)';
                }
            }
        }

        $existing = Attendance::where('employee_id', $guard->id)->where('work_date', $date)->where('shift', $shift)->first();
        if ($existing?->overtime_id) {
            return ['ok' => false, 'message' => $guard->name . '\'s ' . $shift . ' shift comes from overtime entry #' . $existing->overtime_id . '. Change the overtime entry instead.'];
        }
        if ($existing && (int) $existing->client_id !== (int) $client->id) {
            return ['ok' => false, 'message' => $guard->name . ' is already recorded at another client for this shift.'];
        }
        if ($existing && $existing->source === 'scan' && $existing->status === Attendance::PRESENT && $status === Attendance::PRESENT) {
            return ['ok' => true, 'status' => Attendance::PRESENT, 'message' => $guard->name . ' already signed in with their own phone at ' . $existing->checked_in_at?->format('H:i') . '.'];
        }

        $finalStatus = $flags ? Attendance::PENDING : $status;
        $values = [
            'client_id' => $client->id,
            'status' => $finalStatus,
            'checked_in_at' => $at,
            'latitude' => $lat,
            'longitude' => $lng,
            'accuracy_m' => $accuracy,
            'distance_m' => $distance,
            'flags' => $flags ?: null,
            'reason' => ShiftReasons::FOR_STATUS[$finalStatus] ?? null,
            'marked_by' => $supervisor->id,
            'note' => 'Marked on supervisor kiosk by ' . $supervisor->name . ($relief ? ' (moved here from their own post)' : ''),
            'moved_from_client_id' => $relief ? $guard->client_id : null,
        ];

        if ($existing) {
            // A guard's own scan held for review, confirmed in person at the site, counts as reviewed.
            if ($existing->status === Attendance::PENDING && ! $flags) {
                $values += ['reviewed_by' => $supervisor->id, 'reviewed_at' => $at];
            }
            $existing->fill($values + ['source' => $existing->source === 'scan' ? 'scan' : 'kiosk'])->save();
        } else {
            Attendance::create($values + [
                'employee_id' => $guard->id, 'work_date' => $date, 'shift' => $shift, 'source' => 'kiosk',
                'device_id' => preg_match('/^[A-Za-z0-9-]{16,64}$/', (string) ($where['device_id'] ?? '')) ? $where['device_id'] : null,
            ]);
        }

        $label = strtolower(Attendance::LABELS[$finalStatus]);

        return ['ok' => true, 'status' => $finalStatus,
            'message' => $flags ? $guard->name . ': saved, but held for review (' . implode('; ', $flags) . ').' : $guard->name . ': ' . $label . '.'];
    }
}
