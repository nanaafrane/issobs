<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Which calendar the period picker shows, and the date a named period is built around.
 *
 *   monthly / quarterly / semiannual / yearly -> month picker  (?month=YYYY-MM)
 *   daily / weekly                            -> date picker   (?date=Y-m-d)
 *   custom                                    -> from / to dates
 *
 * ?date= still works for every period (old links, the ‹ › buttons), and wins when both are sent.
 */
class PeriodPicker
{
    public const MONTH_PERIODS = ['monthly', 'quarterly', 'semiannual', 'yearly'];

    /** 'month' | 'date' | 'range' */
    public static function calendarFor(string $period): string
    {
        return match (true) {
            $period === 'custom' => 'range',
            in_array($period, self::MONTH_PERIODS, true) => 'month',
            default => 'date',
        };
    }

    /** The anchor date from ?date=Y-m-d, else ?month=YYYY-MM (its first day), else null. */
    public static function anchor(Request $request): ?Carbon
    {
        return self::date($request->input('date')) ?? self::month($request->input('month'));
    }

    public static function date($value): ?Carbon
    {
        $value = trim((string) $value);
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        try {
            $d = Carbon::createFromFormat('!Y-m-d', $value);

            return $d && $d->format('Y-m-d') === $value ? $d : null;   // rejects 2026-02-31
        } catch (\Throwable) {
            return null;
        }
    }

    public static function month($value): ?Carbon
    {
        $value = trim((string) $value);
        if (! preg_match('/^(\d{4})-(\d{2})$/', $value, $m) || (int) $m[2] < 1 || (int) $m[2] > 12) {
            return null;
        }

        return Carbon::create((int) $m[1], (int) $m[2], 1)->startOfDay();
    }
}
