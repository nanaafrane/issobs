<?php

namespace App\Support\Attendance;

use App\Models\Attendance;
use App\Models\employee;
use App\Models\Overtime;
use App\Models\OvertimeAttendanceLink;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the attendance sheet in step with an overtime entry.
 *
 * What an entry writes, for its date and shift:
 *
 *  absent  The guard under "Absent / Post Vacant", when the reason is about them
 *          (Absent, Sick, Off Duty, Pass), at the entry's client (or the guard's own client).
 *  cover   The O.T guard, Present at the entry's client: the post was covered, so the
 *          client's sheet doesn't count the shift as uncovered.
 *
 *  - Entry submitted / in review   both cells show "?" (awaiting review) with the reason, and count for nothing yet.
 *  - Entry fully approved          absent cell becomes Absent / Sick / Off Duty / Pass; cover cell becomes Present.
 *  - Entry rejected or deleted     both cells go back exactly to what they were before (or empty).
 *  - Entry edited                  old cells are put back, new ones written.
 *
 * It never overwrites a guard's own QR / kiosk "present" sign-in or a cell owned by another
 * overtime entry; those are recorded as a conflict on the entry instead.
 * Rows it writes have source "overtime" and attendances.overtime_id set, and the attendance
 * screens won't let them be changed except through the overtime entry.
 */
class OvertimeAttendanceSync
{
    public static function sync(Overtime $overtime): void
    {
        DB::transaction(function () use ($overtime) {
            $wanted = self::targets($overtime);
            $links = OvertimeAttendanceLink::where('overtime_id', $overtime->id)->get()->keyBy('role');

            foreach ($links as $role => $link) {
                $t = $wanted[$role] ?? null;
                if (! $t || ! self::sameSlot($link, $t)) {
                    self::reverse($link);
                    $links->forget($role);
                }
            }

            foreach ($wanted as $role => $t) {
                self::apply($overtime, $role, $t, $links->get($role));
            }
        });
    }

    /** Put every cell this entry wrote back to how it was. */
    public static function reverseAll(Overtime $overtime): void
    {
        DB::transaction(function () use ($overtime) {
            foreach (OvertimeAttendanceLink::where('overtime_id', $overtime->id)->get() as $link) {
                self::reverse($link);
            }
        });
    }

    /**
     * Cells this entry should own right now: [role => [employee_id, client_id, date, shift, status, reason]].
     */
    private static function targets(Overtime $o): array
    {
        if ($o->trashed() || $o->isRejected() || ! $o->entry_date || ! in_array($o->shift, Attendance::SHIFTS, true)) {
            return [];
        }

        $final = $o->isFullyApproved();
        $date = $o->entry_date->toDateString();
        $reason = ShiftReasons::normalise($o->reason);
        $out = [];

        $guardStatus = ShiftReasons::guardStatus($reason);
        if ($o->absent_employee_id && $guardStatus) {
            $clientId = $o->client_id ?: employee::whereKey($o->absent_employee_id)->value('client_id');
            $out['absent'] = [
                'employee_id' => (int) $o->absent_employee_id, 'client_id' => $clientId ? (int) $clientId : null,
                'date' => $date, 'shift' => $o->shift,
                'status' => $final ? $guardStatus : Attendance::PENDING, 'reason' => $reason,
            ];
        }

        if ($o->ot_employee_id && $o->client_id && (int) $o->ot_employee_id !== (int) $o->absent_employee_id) {
            $out['cover'] = [
                'employee_id' => (int) $o->ot_employee_id, 'client_id' => (int) $o->client_id,
                'date' => $date, 'shift' => $o->shift,
                'status' => $final ? Attendance::PRESENT : Attendance::PENDING, 'reason' => 'Present',
                // Taken off his own active shift at another post: that post shows "M - moved", not absent.
                'moved_from' => self::movedFrom((int) $o->ot_employee_id, (int) $o->client_id, $o->shift, $date),
            ];
        }

        return $out;
    }

    /**
     * The cover guard's own client when this shift is his own rostered shift there (so covering
     * means leaving his post: a move), otherwise null (extra shift on his off time).
     */
    public static function movedFrom(int $employeeId, int $toClientId, string $shift, ?string $date = null): ?int
    {
        $guard = employee::find($employeeId, ['id', 'client_id']);
        if (! $guard || ! $guard->client_id || (int) $guard->client_id === $toClientId) {
            return null;
        }
        $home = \App\Models\Client::find($guard->client_id);
        if (! $home) {
            return null;
        }
        $onDutyAtPost = $date && Attendance::where('employee_id', $guard->id)->where('client_id', $home->id)
            ->where('work_date', $date)->where('shift', $shift)->whereNull('overtime_id')
            ->whereIn('status', [Attendance::PRESENT, Attendance::PENDING])->exists();

        return Roster::shiftOf($guard, $home) === $shift || $onDutyAtPost ? (int) $home->id : null;
    }

    private static function sameSlot(OvertimeAttendanceLink $link, array $t): bool
    {
        return (int) $link->employee_id === $t['employee_id'] && (int) $link->client_id === (int) $t['client_id']
            && $link->work_date->toDateString() === $t['date'] && $link->shift === $t['shift'];
    }

    private static function apply(Overtime $o, string $role, array $t, ?OvertimeAttendanceLink $link): void
    {
        $base = ['overtime_id' => $o->id, 'role' => $role, 'employee_id' => $t['employee_id'], 'client_id' => $t['client_id'],
            'work_date' => $t['date'], 'shift' => $t['shift']];

        if (! $t['client_id']) {
            self::saveLink($link, $base + ['state' => 'skipped', 'attendance_id' => null, 'previous' => null,
                'message' => 'No client on the entry or the guard, so nothing was put on a sheet.']);

            return;
        }

        $label = 'Overtime #' . $o->id . ' (' . ($role === 'cover' ? 'covering' : $t['reason']) . ')';
        $values = [
            'client_id' => $t['client_id'],
            'moved_from_client_id' => $t['moved_from'] ?? null,
            'status' => $t['status'],
            'reason' => $t['status'] === Attendance::PENDING ? $t['reason'] : (ShiftReasons::FOR_STATUS[$t['status']] ?? $t['reason']),
            'source' => 'overtime',
            'overtime_id' => $o->id,
            'flags' => $t['status'] === Attendance::PENDING ? [$label . ': awaiting overtime approval'] : null,
            'note' => $t['status'] === Attendance::PENDING ? null : $label . ', approved',
            'marked_by' => $o->user_1,
            'reviewed_by' => $t['status'] === Attendance::PENDING ? null : $o->user_3,
            'reviewed_at' => $t['status'] === Attendance::PENDING ? null : $o->date_3,
            'checked_in_at' => null, 'latitude' => null, 'longitude' => null, 'accuracy_m' => null,
            'distance_m' => null, 'device_id' => null, 'ip' => null,
        ];

        // Already ours: just move it on (pending -> approved, or a changed reason).
        if ($link && $link->state === 'linked' && ($row = Attendance::find($link->attendance_id)) && (int) $row->overtime_id === (int) $o->id) {
            $row->fill($values)->save();
            self::saveLink($link, $base + ['state' => 'linked', 'message' => self::movedMessage($t)]);

            return;
        }
        if ($link && $link->state === 'matched' && Attendance::whereKey($link->attendance_id)->exists()) {
            return;
        }

        $existing = Attendance::where('employee_id', $t['employee_id'])->where('work_date', $t['date'])->where('shift', $t['shift'])->first();

        if ($existing) {
            if ($existing->overtime_id && (int) $existing->overtime_id !== (int) $o->id) {
                self::saveLink($link, $base + ['state' => 'conflict', 'attendance_id' => $existing->id, 'previous' => null,
                    'message' => 'This shift is already on overtime #' . $existing->overtime_id . '.']);

                return;
            }

            $signedIn = $existing->status === Attendance::PRESENT && in_array($existing->source, ['scan', 'kiosk'], true);
            if ($role === 'cover' && $existing->status === Attendance::PRESENT && (int) $existing->client_id === (int) $t['client_id']) {
                // The O.T guard already signed in at this post: nothing to add.
                self::saveLink($link, $base + ['state' => 'matched', 'attendance_id' => $existing->id, 'previous' => null,
                    'message' => 'Already present on the sheet (' . $existing->source . ').']);

                return;
            }
            // Moved off his own post: his mark there for this shift (even a sign-in) is replaced, and put back if the entry goes.
            $movedOffHomeMark = $role === 'cover' && ! empty($t['moved_from']) && (int) $existing->client_id === (int) $t['moved_from'];
            if (! $movedOffHomeMark && ($signedIn || $role === 'cover')) {
                $where = $existing->client?->business_name ?: $existing->client?->name ?: 'another client';
                self::saveLink($link, $base + ['state' => 'conflict', 'attendance_id' => $existing->id, 'previous' => null,
                    'message' => ($role === 'absent' ? 'The guard signed in for this shift' : 'The O.T guard is already on the sheet for this shift')
                        . ' (' . strtolower(Attendance::LABELS[$existing->status] ?? $existing->status) . ' at ' . $where
                        . ($existing->checked_in_at ? ', ' . $existing->checked_in_at->format('H:i') : '') . '). Attendance was not changed.']);

                return;
            }

            $previous = collect($existing->getAttributes())->except(['id', 'created_at', 'updated_at'])->all();
            $existing->fill($values)->save();
            self::saveLink($link, $base + ['state' => 'linked', 'attendance_id' => $existing->id, 'previous' => $previous, 'message' => self::movedMessage($t)]);

            return;
        }

        $row = Attendance::create($values + ['employee_id' => $t['employee_id'], 'work_date' => $t['date'], 'shift' => $t['shift']]);
        self::saveLink($link, $base + ['state' => 'linked', 'attendance_id' => $row->id, 'previous' => null, 'message' => self::movedMessage($t)]);
    }

    private static function reverse(OvertimeAttendanceLink $link): void
    {
        if ($link->state === 'linked' && $link->attendance_id) {
            $row = Attendance::find($link->attendance_id);
            if ($row && (int) $row->overtime_id === (int) $link->overtime_id) {
                if ($link->previous) {
                    // forceFill: the snapshot holds every column the row had, written back as it was.
                    DB::table('attendances')->where('id', $row->id)->update(
                        collect($link->previous)->only(array_keys($row->getAttributes()))->except(['id'])->all() + ['updated_at' => now()]
                    );
                    // Written directly (no model events): re-check a "T" termination request.
                    \App\Support\AttendanceTerminations::sync((int) $row->employee_id);
                } else {
                    $row->delete();
                }
            }
        }
        $link->delete();
    }

    private static function movedMessage(array $t): ?string
    {
        if (empty($t['moved_from'])) {
            return null;
        }
        $home = \App\Models\Client::find($t['moved_from']);

        return 'Moved off his own ' . $t['shift'] . ' shift at ' . ($home?->business_name ?: $home?->name) . ' (shows M there, not absent).';
    }

    private static function saveLink(?OvertimeAttendanceLink $link, array $values): void
    {
        if ($link) {
            $link->fill($values)->save();
        } else {
            OvertimeAttendanceLink::create($values);
        }
    }
}
