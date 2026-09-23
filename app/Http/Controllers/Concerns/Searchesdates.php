<?php

namespace App\Http\Controllers\Concerns;

use Carbon\Carbon;

/**
 * Search helpers for server-side DataTables.
 *
 * Problem this solves: with serverSide: true, DataTables does no filtering
 * itself - it sends the typed text to the controller and MySQL matches it
 * against whatever string we build. If the string MySQL searches differs from
 * the string the user sees in the cell, copy-pasting from the screen finds
 * nothing. These helpers make date / ID / number columns match what users
 * actually type, and add report-style keywords ("today", "this month", ...).
 *
 * SECURITY: every $column / $expression passed in must be a hard-coded string
 * from the controller, never request input. Both are also regex-checked here
 * because they are interpolated into raw SQL.
 */
trait SearchesDates
{
    /** Escape LIKE wildcards in user input and wrap for a contains-match. */
    protected function likeTerm(string $value): string
    {
        return '%' . addcslashes(trim($value), '%_\\') . '%';
    }

    /**
     * Match a prefixed display id ("FWSSR45", "FWSSi12", "#45") by its digits.
     * Input with no digits matches nothing rather than everything.
     */
    protected function whereIdMatches($query, string $column, string $input): void
    {
        $this->assertSafeColumn($column);

        $digits = preg_replace('/\D/', '', $input);

        if ($digits === '') {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->where($column, 'like', "%{$digits}%");
    }

    /**
     * Tolerant date search. Accepts:
     *   - keywords: today, yesterday, this week, this month, last month, this year
     *   - 2026-03-05   (exact day)      2026-03  (month)      2026  (year)
     *   - 05/03/2026   (d/m/Y day)      03/2026  (m/Y month)
     *   - free text such as "Monday", "5 January", "January 2026", "05 Mar 2026",
     *     matched against every format we display, ignoring commas / extra spaces.
     */
    protected function whereDateMatches($query, string $column, string $input): void
    {
        $this->assertSafeColumn($column);

        $input = trim($input);
        if ($input === '') {
            return;
        }

        $now = now();
        $ranges = [
            'today'      => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday'  => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'this week'  => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'this month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last month' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'this year'  => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
        ];

        $keyword = strtolower(preg_replace('/\s+/', ' ', $input));
        if (isset($ranges[$keyword])) {
            $query->whereBetween($column, $ranges[$keyword]);
            return;
        }

        // 2026-03-05
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $input, $m)) {
            checkdate((int) $m[2], (int) $m[3], (int) $m[1])
                ? $query->whereDate($column, $input)
                : $query->whereRaw('1 = 0');
            return;
        }

        // 2026-03
        if (preg_match('/^(\d{4})-(\d{1,2})$/', $input, $m)) {
            $query->whereYear($column, (int) $m[1])->whereMonth($column, (int) $m[2]);
            return;
        }

        // 2026
        if (preg_match('/^\d{4}$/', $input)) {
            $query->whereYear($column, (int) $input);
            return;
        }

        // 05/03/2026  (Ghana / UK style: day/month/year)
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $input, $m)) {
            checkdate((int) $m[2], (int) $m[1], (int) $m[3])
                ? $query->whereDate($column, sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]))
                : $query->whereRaw('1 = 0');
            return;
        }

        // 03/2026
        if (preg_match('#^(\d{1,2})/(\d{4})$#', $input, $m)) {
            $query->whereYear($column, (int) $m[2])->whereMonth($column, (int) $m[1]);
            return;
        }

        // Free text: try every format the UI shows, with commas removed on the
        // SQL side and collapsed on the input side.
        $needle = addcslashes(preg_replace('/[\s,]+/', ' ', $input), '%_\\');
        $formats = [
            '%W %e %M %Y',     // Monday 5 January 2026   (receipt list display)
            '%W %d %M %Y',     // Monday 05 January 2026
            '%e %M %Y',        // 5 January 2026
            '%d %M %Y',        // 05 January 2026
            '%M %d %Y',        // January 05 2026
            '%M %Y',           // January 2026            (invoice month display)
            '%d %b %Y %H:%i',  // 05 Jan 2026 14:30       (created_at display)
            '%d %b %Y',        // 05 Jan 2026
            '%d/%m/%Y',        // 05/01/2026
            '%Y-%m-%d',        // 2026-01-05
        ];

        $query->where(function ($q) use ($column, $formats, $needle) {
            foreach ($formats as $format) {
                $q->orWhereRaw(
                    "REPLACE(DATE_FORMAT({$column}, '{$format}'), ',', '') LIKE ?",
                    ["%{$needle}%"]
                );
            }
        });
    }

    /**
     * Numeric search. Accepts an optional operator: ">1000", "<=250.50", "=0",
     * "!=0". Without an operator it does a contains-match on the stored value,
     * so "1200" finds 1200.00 even though the cell shows "1,200.00".
     * $expression may be a column or a hard-coded SQL expression.
     */
    protected function whereNumberMatches($query, string $expression, string $input): void
    {
        if (! preg_match('/^[A-Za-z0-9_.\s()+\-*,]+$/', $expression)) {
            throw new \InvalidArgumentException('Unsafe SQL expression.');
        }

        $clean = str_replace([',', ' '], '', trim($input));
        $clean = preg_replace('/^(GH₵|₵)/u', '', $clean);

        if (preg_match('/^(>=|<=|<>|!=|>|<|=)?(\d+(?:\.\d+)?)$/', $clean, $m)) {
            if ($m[1] !== '') {
                $operator = $m[1] === '<>' ? '!=' : $m[1]; // whitelisted by the regex
                $query->whereRaw("{$expression} {$operator} ?", [(float) $m[2]]);
                return;
            }

            $query->whereRaw("CAST({$expression} AS CHAR) LIKE ?", ["%{$m[2]}%"]);
            return;
        }

        $query->whereRaw('1 = 0');
    }

    /** Request value as a string, or null (guards against ?from[]=x array input). */
    protected function requestString($request, string $key): ?string
    {
        $value = $request->input($key);

        return is_string($value) ? $value : null;
    }

    /** ColumnControl / standard DataTables per-column search text, always a string. */
    protected function columnFilterValue($column): string
    {
        if (! is_array($column)) {
            return '';
        }

        $value = $column['columnControl']['search']['value'] ?? $column['search']['value'] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * Bounded range filter. Uses plain >= / <= comparisons (no functions on the
     * column), so it can use an index.
     *
     * $from / $to accept "Y-m-d" or "Y-m"; blank or invalid edges are ignored.
     * A "Y-m" value always means the WHOLE month (from = 1st, to = last day).
     * $wholeMonths forces the same snapping for full dates - use it for columns
     * that store month-anchored dates (invoices.invoice_month is always the 1st,
     * so a mid-month picker would otherwise match nothing).
     * A reversed range is swapped instead of returning nothing.
     */
    protected function whereWithinRange($query, string $column, ?string $from, ?string $to, bool $wholeMonths = false): void
    {
        $this->assertSafeColumn($column);

        $start = $this->parseRangeEdge($from, false, $wholeMonths);
        $end   = $this->parseRangeEdge($to, true, $wholeMonths);

        if ($start && $end && $start->gt($end)) {
            $start = $this->parseRangeEdge($to, false, $wholeMonths);
            $end   = $this->parseRangeEdge($from, true, $wholeMonths);
        }

        if ($start) {
            $query->where($column, '>=', $start->toDateTimeString());
        }
        if ($end) {
            $query->where($column, '<=', $end->toDateTimeString());
        }
    }

    private function parseRangeEdge(?string $value, bool $isEnd, bool $wholeMonths): ?Carbon
    {
        $value = trim((string) $value);

        if (preg_match('/^(\d{4})-(\d{2})$/', $value, $m)) {
            if ((int) $m[2] < 1 || (int) $m[2] > 12) {
                return null;
            }
            $date = Carbon::create((int) $m[1], (int) $m[2], 1, 0, 0, 0);
            $wholeMonths = true;
        } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)
            && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            $date = Carbon::create((int) $m[1], (int) $m[2], (int) $m[3], 0, 0, 0);
        } else {
            return null;
        }

        if ($wholeMonths) {
            return $isEnd ? $date->endOfMonth() : $date->startOfMonth();
        }

        return $isEnd ? $date->endOfDay() : $date->startOfDay();
    }

    private function assertSafeColumn(string $column): void
    {
        if (! preg_match('/^[A-Za-z0-9_.]+$/', $column)) {
            throw new \InvalidArgumentException('Unsafe column name.');
        }
    }
}