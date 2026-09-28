<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Payroll months are stored as DATE columns anchored on the 1st
 * (salaries.salary_month, categories.category_month, invoices.invoice_month).
 *
 * Always filter them with span(), never whereMonth(): whereMonth() ignores the
 * YEAR, so "September" silently merges September 2025 and September 2026.
 * span() is also index-friendly (a plain BETWEEN on the raw column).
 */
final class PayrollMonth
{
    /** [first day, last day] of the month containing $month, as Y-m-d strings. */
    public static function span(CarbonInterface|string|null $month): array
    {
        $m = self::parse($month);

        return [$m->copy()->startOfMonth()->toDateString(), $m->copy()->endOfMonth()->toDateString()];
    }

    /**
     * Tolerant parser for month input: "2026-09", "2026-09-01", "September, 2026",
     * Carbon instances. Falls back to the current month on anything unparseable.
     */
    public static function parse(CarbonInterface|string|null $month): Carbon
    {
        if ($month instanceof CarbonInterface) {
            return Carbon::instance($month)->startOfMonth();
        }

        $value = trim((string) $month);

        if (preg_match('/^(\d{4})-(\d{1,2})$/', $value, $m) && (int) $m[2] >= 1 && (int) $m[2] <= 12) {
            return Carbon::create((int) $m[1], (int) $m[2], 1)->startOfDay();
        }

        try {
            return $value === '' ? now()->startOfMonth() : Carbon::parse(str_replace(',', '', $value))->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }
}
