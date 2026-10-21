<?php

namespace App\Observers;

use App\Models\Overtime;
use App\Support\Attendance\OvertimeAttendanceSync;

/**
 * Overtime entries and the attendance sheet.
 *
 * Approved overtime is no longer turned into one expense per entry (that put every
 * guard's daily shift on the bank payment screen). Approved entries are "owed" and are
 * paid through an overtime pay-out, which carries one expense and one payment for the
 * office and period — see App\Services\Overtime\OvertimePayouts.
 *
 * Registered in AppServiceProvider::boot():
 *     Overtime::observe(OvertimeObserver::class);
 */
class OvertimeObserver
{
    public function saved(Overtime $overtime): void
    {
        // Attendance sheet: "?" while in review, the reason once fully approved, put back on reject.
        OvertimeAttendanceSync::sync($overtime);
    }

    public function deleted(Overtime $overtime): void
    {
        OvertimeAttendanceSync::reverseAll($overtime);
    }

    public function restored(Overtime $overtime): void
    {
        OvertimeAttendanceSync::sync($overtime);
    }
}
