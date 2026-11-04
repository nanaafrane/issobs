<?php

namespace App\Support;

use App\Models\Salary;
use App\Models\SalaryUploadBatch;
use App\Models\User;
use App\Support\Attendance\AbsentDeduction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Salary upload (salariesCreate):
 *
 *   1) template()  the month's unpaid salaries, one row each, with only the columns
 *                  chosen, and a "Row check" formula that flags problems in Excel
 *   2) read()      reads the file back - our template, or the older DataTables export
 *   3) check()     compares every row with the salary as it is now. Nothing is saved.
 *                  Errors say what to fix; warnings ask to double check.
 *   4) apply()     saves the rows without errors, only the columns ticked, in one
 *                  transaction, and keeps old / new values so it can be undone.
 *
 * The "Salary ID" column is the key (or Employee ID + Salary month in an old file).
 * A blank amount keeps the current value; type 0 to clear one.
 */
class SalaryUpload
{
    public const KEY_HEADER = 'Salary ID';
    public const MAX_ROWS = 5000;

    /** Salaries that can still change: not yet approved (paid). */
    public const EDITABLE_STATUSES = ['pending', 'rejected', 'hold'];

    /** Information columns in the template: read back only to check the row is who it says. */
    public const INFO = [
        'employee_id' => 'Employee ID',
        'name' => 'Name',
        'office' => 'Office',
        'client' => 'Client',
        'status' => 'Status',
        'absent_days' => 'Absent days (attendance)',
        'absent_attendance' => 'Absent from attendance',
        'earnings_elsewhere' => 'Earnings not in this file',
        'deductions_elsewhere' => 'Deductions not in this file',
        'current_net' => 'Net pay now',
    ];

    /** Headers of other columns (template extras, the old DataTables export) that are skipped quietly. */
    private const IGNORED = [
        'row_check_automatic', 'row_check', 'errors_from_upload', 'errors', 'sheet_row', 'field', 'location',
        'salary_month', 'month', 'gross_salary', 'net_salary', 'total_deductions', 'tax', 'cost_to_company',
        'action', 'actions', 'edit', 'no', 'select',
    ];

    /** Download the template and check files: Finance Manager and the Accounts department. */
    public static function canUse(?User $user): bool
    {
        return (bool) $user && ($user->hasRole(['Finance Manager']) || $user->hasPermission('Accounts'));
    }

    /** Save a checked file to the salaries (and undo one): Finance Manager, or a Manager in Accounts. */
    public static function canApply(?User $user): bool
    {
        return (bool) $user && ($user->hasRole(['Finance Manager']) || ($user->hasRole(['Manager']) && $user->hasPermission('Accounts')));
    }

    /** Lower case, words joined by "_": "Basic salary *" => basic_salary. */
    public static function normalize($header): string
    {
        $h = strtolower(trim((string) $header));
        $h = preg_replace('/[^a-z0-9]+/', '_', $h);

        return trim($h, '_');
    }

    /** header (normalized) => column key */
    private static function headerMap(): array
    {
        $map = ['salary_id' => 'salary_id', 'id' => 'salary_id', 'salary' => 'salary_id'];
        foreach (SalaryCalculator::editable() as $key => $label) {
            $map[$key] = $key;
            $map[self::normalize($label)] = $key;
        }
        $map['walk_in'] = 'walkin';
        $map['amount_deducted_cof_start_date'] = 'amnt_ded_cof_start_date';
        $map['start_date'] = 'amnt_ded_cof_start_date';
        $map['airtime'] = 'airtime_allowance';
        $map['transport'] = 'transport_allowance';
        $map['basic'] = 'basic_salary';
        foreach (self::INFO as $key => $label) {
            $map[self::normalize($label)] = $key;
            $map[$key] = $key;
        }
        $map['employee'] = 'employee_id';
        $map['salary_month'] = 'salary_month';
        $map['month'] = 'salary_month';

        return $map;
    }

    // ------------------------------------------------------------------ template

    /**
     * Rows for the template: the month's salaries that can still change.
     *
     * @param  string[] $columns editable column keys to include
     * @return array<int, array>
     */
    public static function templateRows(Carbon $month, ?int $fieldId, array $columns): array
    {
        $salaries = Salary::with(['employee.paymentInfo', 'field', 'client'])
            ->whereBetween('salary_month', PayrollMonth::span($month))
            ->whereIn('payment_status', self::EDITABLE_STATUSES)
            ->when($fieldId, fn ($q) => $q->where('field_id', $fieldId))
            ->get()
            ->sortBy(fn ($s) => sprintf('%-40s|%s', $s->field?->name ?? 'zzz', $s->employee?->name ?? ''))
            ->values();

        $att = AbsentDeduction::attendanceFor($salaries->pluck('employee_id')->filter()->map(fn ($id) => (int) $id)->all(), $month);
        $settings = AbsentDeduction::settings();
        $notInFile = array_diff(array_keys(SalaryCalculator::editable()), $columns);

        return $salaries->map(function (Salary $s) use ($att, $month, $settings, $columns, $notInFile) {
            $amounts = SalaryCalculator::amounts($s);
            $a = $att[(int) $s->employee_id] ?? null;
            $earn = $ded = 0.0;
            foreach ($notInFile as $k) {
                SalaryCalculator::isDeduction($k) ? $ded += (float) $amounts[$k] : $earn += (float) $amounts[$k];
            }

            return [
                'salary_id' => $s->id,
                'employee_id' => $s->employee_id,
                'name' => $s->employee?->name,
                'office' => $s->field?->name,
                'client' => $s->client?->business_name ?: $s->client?->name,
                'status' => $s->payment_status,
                'values' => array_intersect_key($amounts, array_flip($columns)),
                'absent_days' => $a ? $a['absent'] : null,
                'absent_attendance' => $a ? AbsentDeduction::amount($a['absent'], (float) $s->basic_salary, (float) $s->allowances, $month, $settings) : null,
                'earnings_elsewhere' => round($earn, 2),
                'deductions_elsewhere' => round($ded, 2),
                'current_net' => $s->net_salary === null ? null : round((float) $s->net_salary, 2),
            ];
        })->all();
    }

    // ------------------------------------------------------------------ read

    /**
     * @return array{error: ?string, rows: array<int, array>, columns: string[], ignored: string[], layout: string}
     *         rows: sheet row number => [key => raw value]
     */
    public static function read(string $path, string $disk = 'local'): array
    {
        $fail = fn (string $m) => ['error' => $m, 'rows' => [], 'columns' => [], 'ignored' => [], 'layout' => ''];
        try {
            $full = Storage::disk($disk)->path($path);
            $reader = IOFactory::createReaderForFile($full);
            if (method_exists($reader, 'setReadDataOnly')) {
                $reader->setReadDataOnly(true);
            }
            $book = $reader->load($full);
        } catch (\Throwable $e) {
            return $fail('The file could not be read. Save it as Excel (.xlsx) and upload it again.');
        }

        $sheet = $book->getSheetByName('Salaries') ?? $book->getSheet(0);
        $map = self::headerMap();
        $highestCol = $sheet->getHighestDataColumn();
        $highestRow = $sheet->getHighestDataRow();

        // Find the header row: our template has it on row 2, the old export on row 1.
        $header = null;
        for ($r = 1; $r <= min(5, $highestRow); $r++) {
            $cells = $sheet->rangeToArray("A{$r}:{$highestCol}{$r}", null, false, false, true)[$r] ?? [];
            $keys = [];
            foreach ($cells as $col => $v) {
                $keys[$col] = $map[self::normalize($v)] ?? null;
            }
            $money = array_filter($keys, fn ($k) => $k && isset(SalaryCalculator::editable()[$k]));
            $hasKey = in_array('salary_id', $keys, true) || (in_array('employee_id', $keys, true) && in_array('salary_month', $keys, true));
            if ($money && $hasKey) {
                $header = ['row' => $r, 'keys' => $keys, 'cells' => $cells];
                break;
            }
            if ($money && ! $header) {
                $header = ['row' => $r, 'keys' => $keys, 'cells' => $cells, 'nokey' => true];
            }
        }
        if (! $header) {
            return $fail('No salary columns were found. Use "Download template" on this page, fill it in and upload that file.');
        }
        if (! empty($header['nokey'])) {
            return $fail('This file has no "Salary ID" column, so the rows cannot be matched to salaries. Download the template again - it has the Salary ID in column A. (An old export also works: it needs its "id" column, or Employee ID and Salary Month.)');
        }

        $columns = [];
        $ignored = [];
        $seen = [];
        foreach ($header['keys'] as $col => $key) {
            $label = trim((string) $header['cells'][$col]);
            if ($key === null) {
                if ($label !== '' && ! in_array(self::normalize($label), self::IGNORED, true)) {
                    $ignored[] = $label;
                }
                continue;
            }
            if (isset($seen[$key])) {
                $ignored[] = $label . ' (second column with this name)';
                unset($header['keys'][$col]);
                continue;
            }
            $seen[$key] = true;
            if (isset(SalaryCalculator::editable()[$key])) {
                $columns[] = $key;
            }
        }

        $rows = [];
        for ($r = $header['row'] + 1; $r <= $highestRow; $r++) {
            $row = [];
            $any = false;
            foreach ($header['keys'] as $col => $key) {
                if ($key === null) {
                    continue;
                }
                $cell = $sheet->getCell($col . $r);
                $v = $cell->getValue();
                if (is_string($v) && str_starts_with($v, '=')) {
                    try {
                        $v = $cell->getCalculatedValue();
                    } catch (\Throwable $e) {
                        $v = $cell->getOldCalculatedValue() ?? $v;
                    }
                }
                if ($v instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                    $v = $v->getPlainText();
                }
                if (is_string($v)) {
                    $v = trim($v);
                }
                if ($v !== null && $v !== '') {
                    $any = true;
                }
                $row[$key] = $v === '' ? null : $v;
            }
            if ($any) {
                $rows[$r] = $row;
            }
        }
        if (! $rows) {
            return $fail('The file has the salary columns but no rows under them.');
        }
        if (count($rows) > self::MAX_ROWS) {
            return $fail(count($rows) . ' rows: upload at most ' . self::MAX_ROWS . ' at a time (e.g. one office per file).');
        }

        return [
            'error' => null,
            'rows' => $rows,
            'columns' => $columns,
            'ignored' => $ignored,
            'layout' => in_array('office', $header['keys'], true) ? 'template' : 'older export',
        ];
    }

    /** A typed amount: number, or text like "1,250.00" / "GHS 50". null when blank; false when not a number. */
    public static function parseAmount($raw): float|false|null
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_int($raw) || is_float($raw)) {
            return round((float) $raw, 2);
        }
        if (is_bool($raw)) {
            return false;
        }
        $s = str_replace(["\u{00A0}", ' ', ','], '', (string) $raw);
        $s = preg_replace('/^(GHS|GH₵|GH¢|GHC|₵|¢)/iu', '', $s);
        $negative = false;
        if (preg_match('/^\((.*)\)$/', $s, $m)) {   // (50.00) = minus 50 in accounting format
            $s = $m[1];
            $negative = true;
        }
        if ($s === '' || $s === '-') {
            return null;
        }
        if (! preg_match('/^-?\d+(\.\d+)?$/', $s)) {
            return false;
        }

        return round(((float) $s) * ($negative ? -1 : 1), 2);
    }

    private static function parseMonth($raw): ?Carbon
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        try {
            if (is_numeric($raw)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $raw))->startOfMonth();
            }
            $s = trim((string) $raw);
            foreach (['F, Y', 'F Y', 'M, Y', 'M Y', 'Y-m', 'm/Y'] as $format) {
                $d = \DateTime::createFromFormat('!' . $format, $s);
                if ($d && $d->format($format) === $s) {
                    return Carbon::instance($d)->startOfMonth();
                }
            }

            return Carbon::parse($s)->startOfMonth();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function money(?float $v): string
    {
        return $v === null ? 'blank' : number_format($v, 2);
    }

    // ------------------------------------------------------------------ check

    /**
     * Compare every row with the salary now. Nothing is saved.
     *
     * @param  string[]|null $apply columns to apply (null = every column in the file)
     * @return array{results: array<int, array>, summary: array}
     */
    public static function check(array $read, ?array $apply = null): array
    {
        $labels = SalaryCalculator::editable();
        $inFile = $read['columns'];
        $apply = $apply === null ? $inFile : array_values(array_intersect($inFile, $apply));

        // Keys: Salary ID, or Employee ID + Salary month (old export).
        $keyed = [];
        foreach ($read['rows'] as $r => $row) {
            $id = null;
            $raw = $row['salary_id'] ?? null;
            if ($raw !== null && $raw !== '') {
                $id = preg_match('/^\s*(\d+)\s*$/', (string) (is_float($raw) && floor($raw) == $raw ? (int) $raw : $raw), $m) ? (int) $m[1] : false;
            }
            $keyed[$r] = $id;
        }
        $byEmployeeMonth = [];
        foreach ($read['rows'] as $r => $row) {
            if ($keyed[$r] === null && ($row['employee_id'] ?? null) !== null && ($row['salary_month'] ?? null) !== null) {
                $emp = EmployeeBulkImport::parseEmployeeId(is_numeric($row['employee_id']) ? (string) (int) $row['employee_id'] : (string) $row['employee_id']);
                $month = self::parseMonth($row['salary_month']);
                if ($emp && $month) {
                    $byEmployeeMonth[$r] = [$emp, $month];
                }
            }
        }
        if ($byEmployeeMonth) {
            foreach ($byEmployeeMonth as $r => [$emp, $month]) {
                $keyed[$r] = Salary::where('employee_id', $emp)->whereBetween('salary_month', PayrollMonth::span($month))->value('id') ?? -1;
            }
        }

        $ids = array_values(array_unique(array_filter($keyed, fn ($id) => is_int($id) && $id > 0)));
        $salaries = collect();
        foreach (array_chunk($ids, 1000) as $chunk) {
            $salaries = $salaries->union(Salary::with(['employee.paymentInfo', 'field', 'client'])->whereIn('id', $chunk)->get()->keyBy('id'));
        }
        $counts = array_count_values(array_filter($keyed, fn ($id) => is_int($id) && $id > 0));

        // Attendance, for the absent check.
        $att = [];
        if (in_array('absent', $apply, true) && $salaries->isNotEmpty()) {
            foreach ($salaries->groupBy(fn ($s) => $s->salary_month?->format('Y-m')) as $ym => $group) {
                if ($ym) {
                    $att[$ym] = AbsentDeduction::attendanceFor($group->pluck('employee_id')->map(fn ($id) => (int) $id)->all(), $ym);
                }
            }
        }
        $settings = AbsentDeduction::settings();

        $results = [];
        foreach ($read['rows'] as $r => $row) {
            $res = [
                'row' => $r, 'salary_id' => null, 'salary' => null, 'errors' => [], 'warnings' => [],
                'changes' => [], 'values' => [], 'before' => null, 'after' => null, 'state' => 'error',
            ];
            $err = function (?string $col, string $msg) use (&$res) {
                $res['errors'][] = ['col' => $col, 'msg' => $msg];
            };
            $warn = function (?string $col, string $msg) use (&$res) {
                $res['warnings'][] = ['col' => $col, 'msg' => $msg];
            };

            $id = $keyed[$r];
            if ($id === null) {
                $err('salary_id', 'Salary ID is blank. Do not delete or change column A - copy the row again from a new template.');
            } elseif ($id === false) {
                $err('salary_id', 'Salary ID "' . $row['salary_id'] . '" is not a number. Do not change column A.');
            } elseif ($id === -1) {
                $err('salary_id', 'No salary for this Employee ID in that month. Add the employee to the month\'s salaries first (Add to Salaries).');
            }
            $salary = is_int($id) && $id > 0 ? $salaries->get($id) : null;
            if (is_int($id) && $id > 0 && ! $salary) {
                $err('salary_id', 'Salary ID ' . $id . ' does not exist (deleted?). Download a new template.');
            }
            if (is_int($id) && $id > 0 && ($counts[$id] ?? 0) > 1) {
                $err('salary_id', 'Salary ID ' . $id . ' is in the file ' . $counts[$id] . ' times. Keep one row for it.');
            }

            // Amounts typed in.
            $typed = [];
            foreach ($inFile as $k) {
                $v = self::parseAmount($row[$k] ?? null);
                if ($v === false) {
                    $err($k, $labels[$k] . ': "' . $row[$k] . '" is not a number. Type the amount only, e.g. 1250.50 (no letters).');
                } elseif ($v !== null && $v < 0) {
                    $err($k, $labels[$k] . ': ' . number_format($v, 2) . ' is below 0. Type the amount as a positive number' . (SalaryCalculator::isDeduction($k) ? ' - it is already taken off.' : '.'));
                } else {
                    $typed[$k] = $v;
                }
            }

            if ($salary) {
                $res['salary_id'] = $salary->id;
                $res['salary'] = $salary;
                $e = $salary->employee;

                if (! in_array($salary->payment_status, self::EDITABLE_STATUSES, true)) {
                    $err(null, $salary->payment_status === 'approved'
                        ? 'Already approved (paid) - it is not changed. Unapprove it on Salaries Transaction first, or pay a top-up.'
                        : 'Status is "' . $salary->payment_status . '" - only pending, rejected or held salaries can be changed.');
                }
                // The info columns must still describe this salary (catches a sort of one column only).
                if (($row['employee_id'] ?? null) !== null) {
                    $raw = is_float($row['employee_id']) || is_int($row['employee_id']) ? (string) (int) $row['employee_id'] : (string) $row['employee_id'];
                    $emp = EmployeeBulkImport::parseEmployeeId($raw);
                    if ($emp !== (int) $salary->employee_id) {
                        $err('employee_id', 'Employee ID ' . $raw . ' does not match Salary ID ' . $salary->id . ', which is FWSS ' . $salary->employee_id . ' ' . ($e?->name ?? '')
                            . '. The rows were probably sorted or pasted out of line - download a new template.');
                    }
                }

                $current = SalaryCalculator::amounts($salary);
                $values = $current;
                foreach ($apply as $k) {
                    if (! array_key_exists($k, $typed)) {
                        continue;
                    }
                    if ($typed[$k] === null) {
                        if (($current[$k] ?? null) !== null && (float) $current[$k] != 0.0) {
                            $warn($k, $labels[$k] . ' is blank: kept at ' . self::money($current[$k]) . '. Type 0 to clear it.');
                        }
                        continue;
                    }
                    $values[$k] = $typed[$k];
                    if (round((float) ($current[$k] ?? 0), 2) !== round($typed[$k], 2) || ($current[$k] === null && $typed[$k] != 0.0)) {
                        $res['changes'][$k] = [$current[$k], $typed[$k]];
                    }
                }

                $old = [];
                foreach (SalaryCalculator::TOTALS as $k) {
                    $old[$k] = $salary->$k === null ? null : round((float) $salary->$k, 2);
                }
                $new = array_map(fn ($v) => round($v, 2), SalaryCalculator::totals($values, $e));
                $res['values'] = $values;
                $res['before'] = $old;
                $res['after'] = $new;
                $stale = false;
                foreach ($new as $k => $v) {
                    if ($old[$k] === null ? $v != 0.0 || $k === 'net_salary' : abs($old[$k] - $v) > 0.005) {
                        $stale = true;
                    }
                }

                if ($new['net_salary'] < 0) {
                    $biggest = collect(SalaryCalculator::DEDUCTIONS)->keys()->sortByDesc(fn ($k) => (float) ($values[$k] ?? 0))->first();
                    $err($biggest, 'Net pay would be ' . number_format($new['net_salary'], 2) . ': deductions (' . number_format($new['total_deductions'], 2)
                        . ') are more than earnings (' . number_format($new['gross_salary'], 2) . '). Check ' . $labels[$biggest] . ' (' . self::money($values[$biggest]) . ').');
                }

                if (! $res['errors']) {
                    if (array_key_exists('basic_salary', $res['changes'])) {
                        $file = $e?->basic_salary === null ? null : round((float) $e->basic_salary, 2);
                        if ($file !== null && $file !== round((float) $values['basic_salary'], 2)) {
                            $warn('basic_salary', 'Basic salary ' . self::money($values['basic_salary']) . ' is not the ' . self::money($file) . ' on the employee record (only this month changes).');
                        }
                    }
                    if (in_array('basic_salary', $apply, true) && (float) $values['basic_salary'] == 0.0) {
                        $warn('basic_salary', 'Basic salary is 0.');
                    }
                    if (in_array('absent', $apply, true) && $e) {
                        $a = $att[$salary->salary_month?->format('Y-m')][(int) $salary->employee_id] ?? null;
                        if ($a) {
                            $should = AbsentDeduction::amount($a['absent'], (float) $values['basic_salary'], (float) $values['allowances'], $salary->salary_month, $settings);
                            if (round((float) $values['absent'], 2) !== round($should, 2)) {
                                $warn('absent', 'Absent ' . self::money($values['absent']) . ' is not what attendance gives: ' . $a['absent'] . ' day(s) absent = ' . number_format($should, 2) . '.'
                                    . ($a['pending'] ? ' ' . $a['pending'] . ' day(s) are still pending review.' : ''));
                            }
                        }
                    }
                    $oldNet = (float) ($old['net_salary'] ?? 0);
                    if ($res['changes'] && $old['net_salary'] !== null && abs($new['net_salary'] - $oldNet) >= 200 && abs($new['net_salary'] - $oldNet) > 0.5 * max(1, abs($oldNet))) {
                        $warn('net_salary', 'Net pay moves a lot: ' . number_format($oldNet, 2) . ' to ' . number_format($new['net_salary'], 2) . '.');
                    }
                }

                $res['state'] = $res['errors'] ? 'error' : ($res['changes'] ? 'change' : ($stale ? 'totals' : 'same'));
            }
            $results[$r] = $res;
        }

        return ['results' => $results, 'summary' => self::summarize($results, $read, $apply)];
    }

    private static function summarize(array $results, array $read, array $apply): array
    {
        $c = collect($results);
        $ready = $c->whereIn('state', ['change', 'totals']);

        $offices = [];
        foreach ($ready as $r) {
            $name = $r['salary']->field?->name ?? 'No office';
            $offices[$name] ??= ['count' => 0, 'before' => 0.0, 'after' => 0.0];
            $offices[$name]['count']++;
            $offices[$name]['before'] += (float) ($r['before']['net_salary'] ?? 0);
            $offices[$name]['after'] += $r['after']['net_salary'];
        }
        ksort($offices);

        $columns = [];
        foreach ($read['columns'] as $k) {
            $changed = $c->where('state', 'change')->filter(fn ($r) => isset($r['changes'][$k]));
            $columns[$k] = [
                'label' => SalaryCalculator::editable()[$k],
                'applied' => in_array($k, $apply, true),
                'changed' => $changed->count(),
                'before' => $changed->sum(fn ($r) => (float) ($r['changes'][$k][0] ?? 0)),
                'after' => $changed->sum(fn ($r) => (float) $r['changes'][$k][1]),
            ];
        }
        $months = $c->pluck('salary')->filter()->map(fn ($s) => $s->salary_month?->format('F Y'))->unique()->values()->all();

        return [
            'total' => $c->count(),
            'errors' => $c->where('state', 'error')->count(),
            'change' => $c->where('state', 'change')->count(),
            'totals' => $c->where('state', 'totals')->count(),
            'same' => $c->where('state', 'same')->count(),
            'ready' => $ready->count(),
            'warnings' => $c->filter(fn ($r) => $r['warnings'] && $r['state'] !== 'error')->count(),
            'offices' => $offices,
            'columns' => $columns,
            'months' => $months,
            'applied' => $apply,
            'net_before' => array_sum(array_column($offices, 'before')),
            'net_after' => array_sum(array_column($offices, 'after')),
        ];
    }

    // ------------------------------------------------------------------ apply / undo

    /**
     * Save the rows without errors (checked again first), only the ticked columns, in one
     * transaction. Rows with errors are left as they are.
     *
     * @return array{updated: int, skipped: int, check: array}
     */
    public static function apply(SalaryUploadBatch $upload, array $read, array $apply, User $user): array
    {
        $check = self::check($read, $apply);
        $audit = [];
        $skipped = 0;

        DB::transaction(function () use ($check, $user, &$audit, &$skipped) {
            foreach ($check['results'] as $r) {
                if (! in_array($r['state'], ['change', 'totals'], true)) {
                    continue;
                }
                $salary = Salary::with('employee.paymentInfo')->lockForUpdate()->find($r['salary_id']);
                if (! $salary || ! in_array($salary->payment_status, self::EDITABLE_STATUSES, true)) {
                    $skipped++;
                    continue;
                }
                $before = self::snapshot($salary);
                SalaryCalculator::fill($salary, $r['values']);
                $salary->user_id1 = $user->id;
                $salary->save();
                $audit[$salary->id] = [
                    'row' => $r['row'],
                    'employee_id' => $salary->employee_id,
                    'changes' => array_map(fn ($c) => ['old' => $c[0], 'new' => $c[1]], $r['changes']),
                    'before' => $before,
                    'after' => self::snapshot($salary->refresh()),
                ];
            }
        });

        if (! $audit) {
            return ['updated' => 0, 'skipped' => $skipped, 'check' => $check];
        }
        $upload->update([
            'status' => 'applied',
            'applied_columns' => $apply,
            'updated_count' => count($audit),
            'skipped_count' => $check['summary']['errors'] + $skipped,
            'net_before' => round(array_sum(array_map(fn ($a) => (float) ($a['before']['net_salary'] ?? 0), $audit)), 2),
            'net_after' => round(array_sum(array_map(fn ($a) => (float) ($a['after']['net_salary'] ?? 0), $audit)), 2),
            'changes' => $audit,
            'applied_by' => $user->id,
            'applied_at' => now(),
        ]);

        return ['updated' => count($audit), 'skipped' => $skipped, 'check' => $check];
    }

    /** Every amount and total of a salary, as saved. */
    public static function snapshot(Salary $salary): array
    {
        $out = [];
        foreach ([...array_keys(SalaryCalculator::editable()), ...SalaryCalculator::TOTALS] as $k) {
            $out[$k] = $salary->$k === null ? null : round((float) $salary->$k, 2);
        }

        return $out;
    }

    /**
     * Put back what the upload changed - only on salaries still unpaid and not changed
     * again since (those are listed, not touched).
     *
     * @return array{restored: int, kept: array<int, string>}
     */
    public static function undo(SalaryUploadBatch $upload, User $user): array
    {
        $restored = 0;
        $kept = [];
        DB::transaction(function () use ($upload, $user, &$restored, &$kept) {
            foreach ($upload->changes ?? [] as $salaryId => $a) {
                $salary = Salary::with('employee')->lockForUpdate()->find($salaryId);
                $who = 'Salary ID ' . $salaryId . ($salary?->employee ? ' (' . $salary->employee->name . ')' : '');
                if (! $salary) {
                    $kept[] = $who . ': deleted';
                    continue;
                }
                if (! in_array($salary->payment_status, self::EDITABLE_STATUSES, true)) {
                    $kept[] = $who . ': ' . $salary->payment_status . ' since the upload';
                    continue;
                }
                if (self::snapshot($salary) != $a['after']) {
                    $kept[] = $who . ': changed again since the upload';
                    continue;
                }
                foreach ($a['before'] as $k => $v) {
                    $salary->$k = $v;
                }
                $salary->user_id1 = $user->id;
                $salary->save();
                $restored++;
            }
            $upload->update(['status' => 'undone', 'undone_by' => $user->id, 'undone_at' => now(), 'undo_kept' => $kept]);
        });

        return ['restored' => $restored, 'kept' => $kept];
    }

    /** Ticked columns from a request, in the usual order (null when none were sent). */
    public static function columnsFromRequest($value): ?array
    {
        if ($value === null) {
            return null;
        }

        return array_values(array_intersect(array_keys(SalaryCalculator::editable()), (array) $value));
    }
}
