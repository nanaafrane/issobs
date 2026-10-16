<?php

namespace App\Support\Attendance;

/**
 * One list of shift reasons for overtime and attendance.
 *
 * An overtime entry's reason says what happened to the guard named under
 * "Absent / Post Vacant". Four reasons are about that guard and become their
 * attendance for the shift; the rest are about the post (nobody to mark).
 */
final class ShiftReasons
{
    /** The overtime reason list (same order as before). */
    public const ALL = ['Shortage', 'Absent', 'Sick', 'Terminated', 'Extra Post', 'Event/Function', 'Other', 'Off Duty', 'Pass'];

    /** Reason => attendance status for the named guard; null = post vacant, the guard is not marked. */
    public const GUARD_STATUS = [
        'Absent' => 'absent',
        'Sick' => 'sick',
        'Off Duty' => 'off',
        'Pass' => 'pass',
        'Terminated' => null,
        'Shortage' => null,
        'Extra Post' => null,
        'Event/Function' => null,
        'Other' => null,
    ];

    /** Attendance status => the reason name shown on the sheet (same words as overtime). */
    public const FOR_STATUS = [
        'present' => 'Present',
        'absent' => 'Absent',
        'sick' => 'Sick',
        'off' => 'Off Duty',
        'pass' => 'Pass',
    ];

    /** "sick", " Off duty " -> canonical reason name, or null if it isn't one. */
    public static function normalise(?string $reason): ?string
    {
        $r = strtolower(trim((string) $reason));
        foreach (self::ALL as $name) {
            if (strtolower($name) === $r) {
                return $name;
            }
        }

        return null;
    }

    /** Attendance status the named guard gets for this reason, or null (post vacant / unknown). */
    public static function guardStatus(?string $reason): ?string
    {
        $name = self::normalise($reason);

        return $name ? self::GUARD_STATUS[$name] : null;
    }

    /** Reasons that mark the named guard: Absent, Sick, Off Duty, Pass. */
    public static function guardReasons(): array
    {
        return array_keys(array_filter(self::GUARD_STATUS));
    }
}
