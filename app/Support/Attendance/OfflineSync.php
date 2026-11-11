<?php

namespace App\Support\Attendance;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Uploads from the attendance outbox on guards' and supervisors' phones (docs/offline-sync.md).
 *
 * Every sign-in is first saved on the phone with its own id (uuid) and the phone's time, then
 * uploaded, at once when there is signal or later when there isn't. This class:
 *
 *  - records each uuid once (attendance_sync_items), so an item uploaded twice, e.g. the
 *    connection dropped after the server saved it, gets the same answer and is never saved twice;
 *  - corrects the phone's clock: the phone sends its own time with each upload, the difference
 *    from the server's time is the skew, applied to the time the sign-in was made;
 *  - adds review reasons when an offline sign-in can't be trusted as is (clock far off,
 *    uploaded very late, or every offline sign-in when config says so);
 *  - then runs the normal ScanRecorder / KioskRecorder, so every online check (PIN, roster,
 *    shift, GPS, phone) applies unchanged.
 *
 * Results per item: accepted | held (saved, waits for review) | rejected (not saved; tell the
 * guard why) | skipped (a newer entry already exists) | retry (try again later; not recorded).
 */
class OfflineSync
{
    public const ACCEPTED = 'accepted';
    public const HELD = 'held';
    public const REJECTED = 'rejected';
    public const SKIPPED = 'skipped';
    public const RETRY = 'retry';

    /** Seconds to add to the phone's clock to get the server's (positive: phone is slow). */
    public static function skew(mixed $phoneNowMs): int
    {
        if (! is_numeric($phoneNowMs) || (float) $phoneNowMs <= 0) {
            return 0;
        }

        return (int) round(now()->getTimestampMs() / 1000 - (float) $phoneNowMs / 1000);
    }

    /**
     * A guard's sign-in from the QR page.
     *
     * @param  array<string, mixed>  $item  uuid, captured_at (ms), offline, employee_number, pin | pin_sealed, latitude, longitude, accuracy, device_id
     * @return array<string, mixed>
     */
    public function scan(Client $client, array $item, int $skew, ?string $ip = null): array
    {
        return $this->once($item, 'scan', $client->id, null, function () use ($client, $item, $skew, $ip) {
            [$at, $extra, $refusal] = $this->timing($item, $skew);
            if ($refusal) {
                return $this->result(self::REJECTED, $refusal);
            }

            $employeeNumber = (string) ($item['employee_number'] ?? '');
            $pin = isset($item['pin_sealed'])
                ? PinSeal::open((string) $item['pin_sealed'], (string) $item['uuid'], $employeeNumber)
                : (isset($item['pin']) ? (string) $item['pin'] : null);
            if ($pin === null) {
                return $this->result(self::REJECTED, 'This sign-in could not be read (the sign-in page was updated). Please sign in again.');
            }

            $r = ScanRecorder::record($client, ['employee_number' => $employeeNumber, 'pin' => $pin] + $item, $at, $ip, $extra);
            if (! empty($r['locked'])) {
                return $this->result(self::RETRY, $r['message']);
            }

            return $this->fromRecorder($r, ScanRecorder::employeeId($employeeNumber));
        });
    }

    /**
     * A supervisor's P / A mark from the kiosk.
     *
     * @param  array<string, mixed>  $item  uuid, captured_at (ms), offline, employee, date, shift, status, latitude, longitude, accuracy, device_id
     * @return array<string, mixed>
     */
    public function kiosk(Client $client, User $supervisor, array $item, int $skew): array
    {
        return $this->once($item, 'kiosk', $client->id, $supervisor->id, function () use ($client, $supervisor, $item, $skew) {
            if (! Roster::canManage($supervisor, $client)) {
                return $this->result(self::REJECTED, 'You cannot take attendance for this client.');
            }
            [$at, $extra, $refusal] = $this->timing($item, $skew);
            if ($refusal) {
                return $this->result(self::REJECTED, $refusal);
            }

            $shift = (string) ($item['shift'] ?? '');
            $status = (string) ($item['status'] ?? '');
            $date = is_string($item['date'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $item['date']) ? Carbon::parse($item['date']) : null;
            if (! in_array($shift, Attendance::SHIFTS, true) || ! in_array($status, [Attendance::PRESENT, Attendance::ABSENT], true) || ! $date) {
                return $this->result(self::REJECTED, 'This mark is incomplete. Mark the guard again.');
            }
            // Same rule as the online kiosk, measured from when the mark was made: only the current shift.
            if ($date->gt($at->copy()->startOfDay()) || $date->lt($at->copy()->subDay()->startOfDay())) {
                return $this->result(self::REJECTED, 'The kiosk only marks the current shift. Use the monthly sheet for other days.');
            }

            $id = ScanRecorder::employeeId(preg_replace('/^ISSOBS-GUARD:/i', '', trim((string) ($item['employee'] ?? ''))));
            $guard = $id ? employee::find($id) : null;
            if (! $guard) {
                return $this->result(self::REJECTED, 'Guard not found. Check the ID card or number.');
            }

            // A mark made offline never overwrites something done after it (operations' hand entry,
            // an approval, the guard's own later sign-in): the newer entry wins.
            $existing = Attendance::where('employee_id', $guard->id)->where('work_date', $date->toDateString())->where('shift', $shift)->first();
            if ($existing && ($extra['values']['captured_offline'] ?? false) && $existing->updated_at && $existing->updated_at->gt($at)) {
                return $this->result(self::SKIPPED, $guard->name . ' already has a newer entry for this shift ('
                    . strtolower(Attendance::LABELS[$existing->status] ?? $existing->status) . ', ' . $existing->updated_at->format('D j M H:i') . '). Kept that one.', $guard->id, $existing->id);
            }

            $r = KioskRecorder::record($client, $guard, $date->toDateString(), $shift, $status, $supervisor, $item, $at, $extra);

            return $this->fromRecorder($r, $guard->id);
        });
    }

    /**
     * When the sign-in was made (server time), and the review reasons that come from timing.
     *
     * @return array{0: Carbon, 1: array{flags: array<int,string>, notes: array<int,string>, values: array<string,mixed>}, 2: ?string}
     */
    private function timing(array $item, int $skew): array
    {
        $now = now();
        $c = config('attendance.offline');
        $phoneMs = is_numeric($item['captured_at'] ?? null) ? (float) $item['captured_at'] : null;
        $at = $phoneMs ? Carbon::createFromTimestampMs($phoneMs + $skew * 1000)->setTimezone(config('app.timezone')) : $now->copy();
        $extra = ['flags' => [], 'notes' => [], 'values' => []];

        if ($at->gt($now->copy()->addMinutes(2))) {
            return [$at, $extra, 'The time on this sign-in is in the future. Check the phone\'s date and time, then sign in again.'];
        }
        if ($at->lt($now->copy()->subDays((int) $c['max_age_days']))) {
            return [$at, $extra, 'This sign-in is more than ' . (int) $c['max_age_days'] . ' days old, so it was not saved. Ask operations to enter it on the monthly sheet.'];
        }

        $delayMinutes = (int) floor($at->diffInMinutes($now, true));
        $offline = filter_var($item['offline'] ?? false, FILTER_VALIDATE_BOOLEAN) || $delayMinutes >= 2;
        if (! $offline) {
            return [$at, $extra, null];
        }

        $extra['values'] = ['captured_offline' => true, 'synced_at' => $now];
        $extra['notes'][] = 'Made offline at ' . $at->format('D j M H:i') . ', uploaded ' . $now->format('D j M H:i');

        if (abs($skew) > (int) $c['max_clock_skew_minutes'] * 60) {
            $extra['flags'][] = 'Phone clock was ' . self::duration(intdiv(abs($skew), 60)) . ($skew > 0 ? ' slow' : ' fast')
                . '; sign-in time corrected to ' . $at->format('D j M H:i');
        }
        if ($delayMinutes > (int) $c['review_after_hours'] * 60) {
            $extra['flags'][] = 'Uploaded ' . self::duration($delayMinutes) . ' after it was made (phone was offline)';
        }
        if (($c['review'] ?? 'flagged') === 'all' && ! $extra['flags']) {
            $extra['flags'][] = 'Made while the phone was offline';
        }

        return [$at, $extra, null];
    }

    /** @return array<string, mixed> */
    private function fromRecorder(array $r, ?int $employeeId): array
    {
        if (! $r['ok']) {
            return $this->result(self::REJECTED, $r['message'], $employeeId);
        }
        $status = $r['status'] ?? null;

        return $this->result($status === Attendance::PENDING ? self::HELD : self::ACCEPTED, $r['message'], $employeeId, $r['attendance']->id ?? null) + ['status' => $status];
    }

    /** @return array<string, mixed> */
    private function result(string $result, string $message, ?int $employeeId = null, ?int $attendanceId = null): array
    {
        return array_filter(['result' => $result, 'message' => $message, 'employee_id' => $employeeId, 'attendance_id' => $attendanceId], fn ($v) => $v !== null);
    }

    /**
     * Run $work once per uuid. The sync row is written in the same transaction as the attendance,
     * so either both are saved or neither. "retry" answers are rolled back so the item can come again.
     *
     * @param  callable(): array<string, mixed>  $work
     * @return array<string, mixed>
     */
    private function once(array $item, string $kind, ?int $clientId, ?int $userId, callable $work): array
    {
        $uuid = (string) ($item['uuid'] ?? '');
        if (! preg_match('/^[A-Za-z0-9-]{16,64}$/', $uuid)) {
            return ['uuid' => $uuid, 'result' => self::REJECTED, 'message' => 'This sign-in has no valid id. Please sign in again.'];
        }
        if ($done = $this->previous($uuid)) {
            return $done;
        }

        try {
            return DB::transaction(function () use ($uuid, $kind, $clientId, $userId, $item, $work) {
                $row = DB::table('attendance_sync_items')->insertGetId([
                    'uuid' => $uuid, 'kind' => $kind, 'client_id' => $clientId, 'user_id' => $userId,
                    'result' => 'working', 'created_at' => now(), 'updated_at' => now(),
                ]);

                $r = $work();
                if ($r['result'] === self::RETRY) {
                    throw new RetryLater($r);
                }

                DB::table('attendance_sync_items')->where('id', $row)->update([
                    'result' => $r['result'], 'message' => $r['message'],
                    'employee_id' => $r['employee_id'] ?? null, 'attendance_id' => $r['attendance_id'] ?? null,
                    'captured_at' => is_numeric($item['captured_at'] ?? null) ? Carbon::createFromTimestampMs((float) $item['captured_at']) : null,
                    'updated_at' => now(),
                ]);

                return ['uuid' => $uuid] + $r;
            });
        } catch (RetryLater $e) {
            return ['uuid' => $uuid] + $e->result;
        } catch (UniqueConstraintViolationException $e) {
            // The same item arrived twice at the same moment; the first one finished first.
            if ($done = $this->previous($uuid)) {
                return $done;
            }
            throw $e;
        }
    }

    /** @return array<string, mixed>|null */
    private function previous(string $uuid): ?array
    {
        $row = DB::table('attendance_sync_items')->where('uuid', $uuid)->first();
        if (! $row || $row->result === 'working') {
            return null;
        }
        $status = $row->attendance_id ? Attendance::whereKey($row->attendance_id)->value('status') : null;

        return array_filter(['uuid' => $uuid, 'result' => $row->result, 'message' => $row->message, 'status' => $status,
            'employee_id' => $row->employee_id, 'duplicate' => true], fn ($v) => $v !== null);
    }

    private static function duration(int $minutes): string
    {
        if ($minutes >= 48 * 60) {
            return intdiv($minutes, 1440) . ' days';
        }

        return $minutes >= 60 ? intdiv($minutes, 60) . 'h ' . str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT) . 'm' : $minutes . ' min';
    }
}

