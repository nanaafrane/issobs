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

        // Warnings kept on the entry: no weekly off day, no own shift next to a shortage, cash exception.
        $fresh = $overtime->fresh() ?? $overtime;
        $checks = \App\Support\OvertimePay::checks($fresh);
        if ($checks !== (array) ($fresh->checks ?? [])) {
            Overtime::withoutEvents(fn () => Overtime::whereKey($overtime->id)->update(['checks' => $checks ? json_encode($checks) : null]));
            $overtime->checks = $checks ?: null;
        }
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
