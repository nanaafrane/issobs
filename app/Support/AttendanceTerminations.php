<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\employee;
use App\Models\EmployeeMovement;
use App\Models\User;
use Carbon\Carbon;

/**
 * A guard marked "T" (Terminated) on the attendance sheet - by hand, or by an approved
 * overtime entry whose reason is Terminated - becomes a termination REQUEST: the guard
 * shows as pending on the pending employees page until head office / HR approves or
 * declines it (EmployeeMovements::changeStatus). Nothing is terminated by the sheet alone.
 *
 *  - The request's last day employed is the day before the first "T".
 *  - Removing every "T" (sheet corrected, overtime rejected or deleted) withdraws a request
 *    that came from the sheet and is still waiting; the guard is Active again.
 *  - A guard already terminated, or with a termination already waiting, is left alone.
 *  - Called after every change to the guard's attendance (Attendance model events, and the
 *    overtime sync's direct restore).
 */
class AttendanceTerminations
{
    public const SOURCES = ['attendance', 'overtime'];

    private static bool $running = false;

    public static function sync(?int $employeeId): void
    {
        if (! $employeeId || self::$running) {
            return;
        }
        self::$running = true;
        try {
            self::run($employeeId);
        } finally {
            self::$running = false;
        }
    }

    private static function run(int $employeeId): void
    {
        $first = Attendance::with('client')->where('employee_id', $employeeId)->where('status', Attendance::TERMINATED)
            ->orderBy('work_date')->orderBy('id')->first();
        $waiting = EmployeeMovements::pending($employeeId, EmployeeMovements::TERMINATION);
        $fromSheet = $waiting && in_array($waiting->source, self::SOURCES, true);

        if (! $first) {
            // Every "T" removed: withdraw the sheet's own request.
            if ($fromSheet) {
                $by = User::find($waiting->requested_by) ?? User::find(EmployeeMovements::defaultApprover());
                if ($by) {
                    EmployeeMovements::withdraw($waiting, $by, 'the "T" was removed from the attendance sheet');
                }
            }

            return;
        }

        $lastDay = Carbon::parse($first->work_date)->subDay()->toDateString();
        $where = $first->client?->business_name ?: $first->client?->name ?: 'a client';
        $note = 'Marked T on the attendance sheet at ' . $where . ' on ' . Carbon::parse($first->work_date)->format('d M Y')
            . ($first->overtime_id ? ' (overtime #' . $first->overtime_id . ')' : '') . '.';

        if ($fromSheet) {
            // The first "T" moved: keep the request's date in step.
            if ($waiting->effective_date?->toDateString() !== $lastDay) {
                $waiting->update(['effective_date' => $lastDay, 'note' => $note]);
            }

            return;
        }
        if ($waiting) {
            return;   // a termination asked for on the employee page is already waiting
        }

        $e = employee::find($employeeId);
        if (! $e || $e->status !== 'Active') {
            return;   // already terminated (or not yet employed): nothing to ask
        }

        // A request even when a decider marked the sheet: terminating is decided on the
        // employee / pending page, with a reason, not by a cell on a time sheet.
        $requester = User::find($first->marked_by);
        $asRequest = $requester && EmployeeMovements::canDecide($requester) ? null : $requester;
        $m = EmployeeMovements::changeStatus($e, EmployeeMovements::TERMINATION, $lastDay, [
            'reason' => null,
            'note' => $note,
            'source' => $first->overtime_id ? 'overtime' : 'attendance',
        ], $asRequest);
        if ($requester && ! $asRequest) {
            $m->update(['requested_by' => $requester->id]);
        }
    }
}
