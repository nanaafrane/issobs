<?php

namespace App\Support;

use App\Models\Bank;
use App\Models\Department;
use App\Models\Field;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Imports\EmployeeUploadImport;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Bulk employee upload: read the template, check every row, build the error report
 * and the per-user template. Creating the employees is done by EmployeeCreator,
 * exactly as for the single "Add employee" form.
 */
class EmployeeBulkImport
{
    public const MAX_ROWS = 1000;          // new employees per file
    public const MAX_UPDATE_ROWS = 5000;   // employees per bulk-update file
    public const KEY_HEADER = 'Employee ID';

    /** Template header (without " *") => key. Order does not matter when reading. */
    public const COLUMNS = [
        'Full Name' => 'full_name', 'Gender' => 'gender', 'Phone Number' => 'phone_number', 'MoMo Network' => 'momo_network',
        'Date of Birth' => 'date_of_birth', 'Ghana Card (NIA) No.' => 'nia_number', 'Address' => 'address',
        'Marital Status' => 'marital_status', 'Worker Type' => 'worker_type', 'Date of Joining' => 'date_of_joining',
        'Department' => 'department', 'Role' => 'role', 'Field Office' => 'field_office', 'Client' => 'client',
        'Location' => 'location', 'Basic Salary' => 'basic_salary', 'Allowances' => 'allowances', 'Deduct Tax' => 'deduct_tax',
        'TIN Number' => 'tin_number', 'Deduct SSNIT' => 'deduct_ssnit', 'SSNIT Number' => 'ssnit_number',
        'Payment Type' => 'payment_type', 'Bank' => 'bank', 'Account Number' => 'account_number', 'Branch' => 'branch',
        'Branch Code' => 'branch_code', 'Guarantor Name' => 'guarantor_name', 'Guarantor Phone' => 'guarantor_phone',
        'Guarantor Address' => 'guarantor_address', 'Guarantor NIA No.' => 'guarantor_nia', 'Relationship' => 'relationship',
    ];
    public const REQUIRED = ['full_name', 'gender', 'phone_number', 'field_office', 'payment_type'];

    private const NETWORKS = ['MTN' => 'mtn-gh', 'TELECEL' => 'vodafone-gh', 'VODAFONE' => 'vodafone-gh',
        'AIRTELTIGO' => 'tigo-gh', 'AIRTEL TIGO' => 'tigo-gh', 'TIGO' => 'tigo-gh', 'AIRTEL' => 'tigo-gh'];

    /* ================================================================ reading */

    /**
     * Read an uploaded file with Laravel Excel (App\Imports\EmployeeUploadImport).
     *
     * @param string      $path  a path on $disk (or an absolute path when $disk is null)
     * @return array{rows: array<int, array<string, string|null>>, error: ?string, headers: array<int, string>}
     *   rows keyed by spreadsheet row number, values keyed by COLUMNS keys
     */
    public static function read(string $path, ?string $disk = null, string $mode = 'create'): array
    {
        // Laravel Excel turns headers into slugs; map those slugs back to our keys.
        $slugToKey = array_combine(HeadingRowFormatter::format(array_keys(self::COLUMNS)), array_values(self::COLUMNS));
        $slugToKey[HeadingRowFormatter::format([self::KEY_HEADER])[0]] = 'employee_id';
        $required = $mode === 'update' ? ['employee_id'] : self::REQUIRED;
        $maxRows = $mode === 'update' ? self::MAX_UPDATE_ROWS : self::MAX_ROWS;

        $best = null;
        try {
            foreach ([2, 1, 3] as $headingRow) {       // 2 = the template; 1/3 = hand-made files
                $sheets = (new EmployeeUploadImport($headingRow))->toCollection($path, $disk);
                foreach ($sheets as $sheet) {
                    $first = $sheet->first();
                    if (! $first) {
                        continue;
                    }
                    $matched = array_intersect_key($slugToKey, $first->toArray());
                    if (count($matched) >= 5 && (! $best || count($matched) > count($best['map']))) {
                        $best = ['map' => $matched, 'rows' => $sheet, 'heading' => $headingRow];
                    }
                }
                if ($best) {
                    break;
                }
            }
        } catch (\Throwable $e) {
            return ['rows' => [], 'headers' => [], 'error' => 'The file could not be read. Upload the .xlsx template.'];
        }

        if (! $best) {
            return ['rows' => [], 'headers' => [], 'error' => 'This is not the employee template: its column headers were not found.'];
        }
        $missing = array_diff($required, $best['map']);
        if ($missing) {
            if ($mode === 'update') {
                return ['rows' => [], 'headers' => [], 'error' => 'This file has no "Employee ID" column. Download the bulk update file from the employee list - it identifies each employee.'];
            }
            return ['rows' => [], 'headers' => [], 'error' => 'Required columns are missing: ' . implode(', ', array_map(fn ($k) => array_search($k, self::COLUMNS), $missing)) . '.'];
        }

        $rows = [];
        foreach ($best['rows'] as $index => $row) {
            $values = [];
            $any = false;
            foreach ($best['map'] as $slug => $key) {
                $v = $row[$slug] ?? null;
                if (in_array($key, ['date_of_birth', 'date_of_joining'], true) && is_numeric($v)) {
                    $v = ExcelDate::excelToDateTimeObject((float) $v)->format('Y-m-d');   // Excel date serial
                } elseif ((is_int($v) || (is_float($v) && floor($v) == $v)) && ! in_array($key, ['basic_salary', 'allowances'], true)) {
                    $v = sprintf('%.0f', $v); // numbers typed into text columns (phone, account...)
                }
                $v = $v === null ? null : trim((string) $v);
                $values[$key] = $v === '' ? null : $v;
                $any = $any || $values[$key] !== null;
            }
            foreach (array_merge(self::COLUMNS, ['employee_id']) as $key) {
                $values[$key] ??= null; // optional columns missing from a hand-made file
            }
            if ($any) {
                $rows[$best['heading'] + 1 + $index] = $values;   // spreadsheet row number
            }
        }

        if (count($rows) > $maxRows) {
            return ['rows' => [], 'headers' => [], 'error' => 'The file has ' . count($rows) . ' employees. The limit is ' . $maxRows . ' per upload.'];
        }
        if ($mode === 'create' && array_filter(array_column($rows, 'employee_id'))) {
            return ['rows' => [], 'headers' => [], 'error' => 'This is a bulk UPDATE file (it has Employee IDs). Upload it on Employees > Bulk update, or remove the Employee ID column to add new employees.'];
        }

        return ['rows' => $rows, 'headers' => $best['map'], 'error' => null];
    }

    /* ============================================================= validation */

    /** 0244123456 / 244123456 / +233 24 412 3456 -> 233244123456 */
    public static function normalisePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }
        $d = preg_replace('/\D/', '', $phone);
        if (strlen($d) === 10 && $d[0] === '0') {
            $d = '233' . substr($d, 1);
        } elseif (strlen($d) === 9) {
            $d = '233' . $d;
        }

        return $d;
    }

    /** DB attribute => label, for change lists. Employee attributes first, then payment details (pay.*). */
    public const FIELD_LABELS = [
        'name' => 'Full Name', 'gender' => 'Gender', 'phone_number' => 'Phone Number', 'channel' => 'MoMo Network',
        'date_of_birth' => 'Date of Birth', 'nia_number' => 'Ghana Card (NIA) No.', 'address' => 'Address',
        'marital_status' => 'Marital Status', 'worker_type' => 'Worker Type', 'date_of_joining' => 'Date of Joining',
        'department_id' => 'Department', 'role_id' => 'Role', 'field_id' => 'Field Office', 'client_id' => 'Client',
        'location' => 'Location', 'basic_salary' => 'Basic Salary', 'allowances' => 'Allowances',
        'tax_button' => 'Deduct Tax', 'tin_number' => 'TIN Number', 'ssnit_button' => 'Deduct SSNIT', 'ssnit_number' => 'SSNIT Number',
        'payment_type' => 'Payment Type', 'gurantor_name' => 'Guarantor Name', 'gurantor_number' => 'Guarantor Phone',
        'gurantor_address' => 'Guarantor Address', 'gurantor_nia_number' => 'Guarantor NIA No.', 'relationship' => 'Relationship',
        'pay.bank_id' => 'Bank', 'pay.acc_number' => 'Account Number', 'pay.branch' => 'Branch', 'pay.branch_code' => 'Branch Code',
    ];

    /** Changing any of these is a payment-detail change (extra confirmation; no re-approval, as on the payment info screen). */
    public const PAYMENT_FIELDS = ['payment_type', 'pay.bank_id', 'pay.acc_number', 'pay.branch', 'pay.branch_code'];

    /**
     * Turn the PROVIDED cells of one row into database values and check their format.
     * Blank cells are not returned. This is the only place field rules live: create and
     * update both use it.
     *
     * @return array<string, mixed> attribute => value ("pay.*" keys are payment details)
     */
    private static function convert(array $v, array $lookups, array &$e, array &$w): array
    {
        $out = [];
        $text = function (string $key, string $attr, int $max) use ($v, &$out, &$e) {
            if ($v[$key] !== null) {
                if (mb_strlen($v[$key]) > $max) $e[] = array_search($key, self::COLUMNS) . " is too long (max $max).";
                $out[$attr] = $v[$key];
            }
        };
        $choice = function (string $key, string $attr, array $allowed, string $message) use ($v, &$out, &$e) {
            if ($v[$key] !== null) {
                $x = strtolower($v[$key]);
                if (! in_array($x, $allowed, true)) $e[] = $message;
                $out[$attr] = $x;
            }
        };

        $text('full_name', 'name', 255);
        $choice('gender', 'gender', ['male', 'female'], 'Gender must be Male or Female.');

        if ($v['phone_number'] !== null) {
            $phone = self::normalisePhone($v['phone_number']);
            if (! preg_match('/^233\d{9}$/', $phone)) $e[] = 'Phone Number must be a Ghana number, e.g. 233241234567.';
            $out['phone_number'] = $phone;
        }
        if ($v['momo_network'] !== null) {
            $n = strtoupper($v['momo_network']);
            if (! isset(self::NETWORKS[$n])) $e[] = 'MoMo Network must be MTN, TELECEL or AIRTELTIGO.';
            $out['channel'] = self::NETWORKS[$n] ?? null;
        }
        foreach (['date_of_birth' => 'Date of Birth', 'date_of_joining' => 'Date of Joining'] as $key => $label) {
            if ($v[$key] !== null) {
                $d = self::parseDate($v[$key]);
                if (! $d) $e[] = "$label is not a valid date.";
                $out[$key] = $d;
            }
        }
        if (! empty($out['date_of_birth']) && Carbon::parse($out['date_of_birth'])->gt(now()->subYears(16))) {
            $w[] = 'Date of Birth makes this person younger than 16.';
        }
        $text('nia_number', 'nia_number', 50);
        $text('address', 'address', 500);
        $choice('marital_status', 'marital_status', ['single', 'married', 'divorced', 'widowed'], 'Marital Status must be Single, Married, Divorced or Widowed.');
        $choice('worker_type', 'worker_type', ['employee', 'contractor'], 'Worker Type must be Employee or Contractor.');

        if ($v['department'] !== null) $out['department_id'] = self::resolve($v['department'], $lookups['departments'], 'Department', $e);
        if ($v['role'] !== null) $out['role_id'] = self::resolve($v['role'], $lookups['roles'], 'Role', $e);

        // Field office + client must be within what this user may assign.
        if ($v['field_office'] !== null) {
            $fieldId = self::resolve($v['field_office'], $lookups['fields'], 'Field Office', $e);
            if ($fieldId && ! in_array($fieldId, $lookups['allowed_fields'], true)) {
                $e[] = 'Field Office "' . $lookups['fields'][$fieldId] . '" is not one you can assign.';
                $fieldId = null;
            }
            $out['field_id'] = $fieldId;
        }
        if ($v['client'] !== null) {
            $out['client_id'] = self::resolve($v['client'], $lookups['clients'], 'Client', $e, 'is not an active, approved client you can assign');
        }
        $text('location', 'location', 255);

        foreach (['basic_salary' => 'Basic Salary', 'allowances' => 'Allowances'] as $key => $label) {
            if ($v[$key] !== null) {
                $num = str_replace([',', 'GH₵', 'GHS', ' '], '', $v[$key]);
                if (! is_numeric($num) || (float) $num < 0) $e[] = "$label must be a number of 0 or more.";
                else $out[$key] = round((float) $num, 2);
            }
        }

        // Tax / SSNIT: "Yes" = the form's ticked checkbox ("on").
        foreach ([['deduct_tax', 'tax_button', 'Deduct Tax'], ['deduct_ssnit', 'ssnit_button', 'Deduct SSNIT']] as [$flag, $button, $label]) {
            if ($v[$flag] !== null) {
                $f = strtolower($v[$flag]);
                if (! in_array($f, ['yes', 'no', 'y', 'n'], true)) $e[] = "$label must be Yes or No.";
                $out[$button] = in_array($f, ['yes', 'y'], true) ? 'on' : null;
            }
        }
        $text('tin_number', 'tin_number', 50);
        $text('ssnit_number', 'ssnit_number', 50);

        if ($v['payment_type'] !== null) {
            $type = ucfirst(strtolower($v['payment_type']));
            if (! in_array($type, ['Cash', 'Bank'], true)) $e[] = 'Payment Type must be Cash or Bank.';
            $out['payment_type'] = $type;
        }
        if ($v['bank'] !== null) $out['pay.bank_id'] = self::resolve($v['bank'], $lookups['banks'], 'Bank', $e);
        $text('account_number', 'pay.acc_number', 50);
        $text('branch', 'pay.branch', 255);
        $text('branch_code', 'pay.branch_code', 50);

        $text('guarantor_name', 'gurantor_name', 255);
        $text('guarantor_phone', 'gurantor_number', 20);
        $text('guarantor_address', 'gurantor_address', 500);
        $text('guarantor_nia', 'gurantor_nia_number', 50);
        $text('relationship', 'relationship', 100);

        return $out;
    }

    /** Values that must be unique, collected per row (only cells that are filled in). */
    private static function inFileValues(array $rows): array
    {
        $inFile = [];
        foreach ($rows as $r => $v) {
            foreach (['phone' => self::normalisePhone($v['phone_number']), 'nia' => $v['nia_number'], 'acc' => $v['account_number'],
                      'tin' => $v['tin_number'], 'ssnit' => $v['ssnit_number'], 'gnia' => $v['guarantor_nia']] as $k => $val) {
                if ($val !== null) {
                    $inFile[$k][mb_strtolower($val)][] = $r;
                }
            }
        }

        return $inFile;
    }

    /** Uniqueness of every value in the row; $self = the employee being updated (its own values are fine). */
    private static function uniqueChecks(array $v, array $existing, array $inFile, array &$e, ?int $self = null): void
    {
        $phone = self::normalisePhone($v['phone_number']);
        if ($phone !== null && preg_match('/^233\d{9}$/', $phone)) {
            $owner = $existing['phone'][$phone] ?? null;
            if ($owner !== null && (int) $owner !== $self) {
                $e[] = 'Phone Number already belongs to FWSS ' . $owner . '.';
            } elseif (count($inFile['phone'][mb_strtolower($phone)] ?? []) > 1) {
                $e[] = 'Phone Number is repeated in this file (rows ' . implode(', ', $inFile['phone'][mb_strtolower($phone)]) . ').';
            }
        }
        foreach ([['nia_number', 'nia', 'Ghana Card (NIA) No.'], ['account_number', 'acc', 'Account Number'], ['tin_number', 'tin', 'TIN Number'],
                  ['ssnit_number', 'ssnit', 'SSNIT Number'], ['guarantor_nia', 'gnia', 'Guarantor NIA No.']] as [$key, $k, $label]) {
            self::uniqueCheck($v[$key], $k, $label, $existing, $inFile, $e, $self);
        }
    }

    /**
     * NEW employees: check every row. Nothing is saved.
     *
     * @return array{results: array<int, array>, summary: array}
     *   result: row, name, field, client, priority, errors[], warnings[], employee[], pay[]
     */
    public static function validate(array $rows, User $user): array
    {
        $lookups = self::lookups($user);
        $existing = self::existingValues($rows);
        $inFile = self::inFileValues($rows);
        $workflow = EmployeeCreator::workflowFor($user);

        $results = [];
        foreach ($rows as $r => $v) {
            $e = [];
            $w = [];
            foreach (self::REQUIRED as $key) {
                if ($v[$key] === null) {
                    $e[] = array_search($key, self::COLUMNS) . ' is required.';
                }
            }
            $c = self::convert($v, $lookups, $e, $w);
            self::uniqueChecks($v, $existing, $inFile, $e);

            if ($v['department'] === null) $w[] = 'No Department given.';
            if ($v['role'] === null) $w[] = 'No Role given.';
            if ($v['client'] === null) $w[] = 'No Client given.';
            if ($v['basic_salary'] === null) $w[] = 'No Basic Salary given.';
            $fieldId = $c['field_id'] ?? null;
            $clientId = $c['client_id'] ?? null;
            if ($clientId && $fieldId && (int) $lookups['client_field'][$clientId] !== $fieldId) {
                $w[] = 'Client belongs to another field office (' . ($lookups['fields'][$lookups['client_field'][$clientId]] ?? '?') . ').';
            }

            // Cross-field rules (same as the form).
            if (($c['payment_type'] ?? null) === 'Bank') {
                if ($v['bank'] === null) $e[] = 'Bank is required when Payment Type is Bank.';
                if ($v['account_number'] === null) $e[] = 'Account Number is required when Payment Type is Bank.';
            }
            foreach ([['tax_button', 'tin_number', 'Deduct Tax', 'TIN Number'], ['ssnit_button', 'ssnit_number', 'Deduct SSNIT', 'SSNIT Number']] as [$button, $num, $flagLabel, $numLabel]) {
                $yes = ($c[$button] ?? null) === 'on';
                if ($yes && empty($c[$num])) $e[] = "$numLabel is required when $flagLabel is Yes.";
                // As on the form: the number is only kept when the deduction is switched on.
                if (! $yes && ! empty($c[$num])) $w[] = "$numLabel is ignored because $flagLabel is not Yes.";
                if (! $yes) $c[$num] = null;
            }

            $emp = [];
            $pay = [];
            foreach (array_keys(self::FIELD_LABELS) as $attr) {
                if (str_starts_with($attr, 'pay.')) {
                    $pay[substr($attr, 4)] = $c[$attr] ?? null;
                } else {
                    $emp[$attr] = $c[$attr] ?? null;
                }
            }

            [$priority] = PayPriority::evaluate($clientId, $fieldId, $emp['location'], $emp['gender']);

            $results[$r] = [
                'row' => $r,
                'name' => $v['full_name'],
                'field' => $fieldId ? ($lookups['fields'][$fieldId] ?? '') : ($v['field_office'] ?? ''),
                'client' => $clientId ? ((array) $lookups['clients'][$clientId])[0] : ($v['client'] ?? ''),
                'priority' => $priority,
                'errors' => $e,
                'warnings' => $w,
                'employee' => $emp,
                'pay' => $pay,
            ];
        }

        $valid = count(array_filter($results, fn ($x) => ! $x['errors']));

        return [
            'results' => $results,
            'summary' => [
                'total' => count($results),
                'valid' => $valid,
                'errors' => count($results) - $valid,
                'warnings' => count(array_filter($results, fn ($x) => ! $x['errors'] && $x['warnings'])),
                'status' => $workflow['status'] ?? null,
            ],
        ];
    }

    /** "FWSS 45", "fwss45", "45" -> 45 */
    public static function parseEmployeeId(?string $value): ?int
    {
        if ($value === null || ! preg_match('/^\s*(?:FWSS\s*)?(\d+)\s*$/i', $value, $m)) {
            return null;
        }

        return (int) $m[1];
    }

    /**
     * EXISTING employees: compare each row with the employee's current details. Nothing is saved.
     * Blank cells mean "leave unchanged".
     *
     * @param int[] $allowedIds  employees this user may update (the employee list's scope)
     * @return array{results: array<int, array>, summary: array}
     *   result: row, employee_id, name, errors[], warnings[], changes[label => [old, new]],
     *           employee[attr => new], pay[attr => new], payment_change, priority_before, priority_after
     */
    public static function validateUpdate(array $rows, User $user, array $allowedIds, bool $canViewSalary): array
    {
        $lookups = self::lookups($user);
        $existing = self::existingValues($rows);
        $inFile = self::inFileValues($rows);
        $allowed = array_flip(array_map('intval', $allowedIds));

        $ids = array_values(array_unique(array_filter(array_map(fn ($v) => self::parseEmployeeId($v['employee_id'] ?? null), $rows))));
        $current = self::currentDetails($ids);
        $idRows = [];
        foreach ($rows as $r => $v) {
            if ($id = self::parseEmployeeId($v['employee_id'] ?? null)) {
                $idRows[$id][] = $r;
            }
        }

        $results = [];
        foreach ($rows as $r => $v) {
            $e = [];
            $w = [];
            $id = self::parseEmployeeId($v['employee_id'] ?? null);
            $now = $id ? ($current[$id] ?? null) : null;

            if (($v['employee_id'] ?? null) === null) {
                $e[] = 'Employee ID is required (it identifies who to update).';
            } elseif (! $id) {
                $e[] = 'Employee ID "' . $v['employee_id'] . '" is not valid (use e.g. FWSS 45).';
            } elseif (! $now || ! isset($allowed[$id])) {
                $e[] = 'FWSS ' . $id . ' was not found among the employees you can update.';
            } elseif (count($idRows[$id]) > 1) {
                $e[] = 'FWSS ' . $id . ' appears more than once in this file (rows ' . implode(', ', $idRows[$id]) . ').';
            }

            if (! $canViewSalary && ($v['basic_salary'] !== null || $v['allowances'] !== null)) {
                $w[] = 'Basic Salary and Allowances are ignored: you cannot change salaries.';
                $v['basic_salary'] = $v['allowances'] = null;
            }

            $provided = self::convert($v, $lookups, $e, $w);
            self::uniqueChecks($v, $existing, $inFile, $e, $id);

            $changes = [];
            $emp = [];
            $pay = [];
            $before = $after = 0;
            if ($now && ! $e) {
                // The employee as it would be after this row.
                $merged = array_merge($now, $provided);

                if (($merged['payment_type'] ?? null) === 'Bank') {
                    if (empty($merged['pay.bank_id'])) $e[] = 'Bank is required when Payment Type is Bank.';
                    if (empty($merged['pay.acc_number'])) $e[] = 'Account Number is required when Payment Type is Bank.';
                }
                foreach ([['tax_button', 'tin_number', 'Deduct Tax', 'TIN Number'], ['ssnit_button', 'ssnit_number', 'Deduct SSNIT', 'SSNIT Number']] as [$button, $num, $flagLabel, $numLabel]) {
                    if (($merged[$button] ?? null) === 'on' && empty($merged[$num])) $e[] = "$numLabel is required when $flagLabel is Yes.";
                    // As on the edit form: switching the deduction off clears the number.
                    if (($merged[$button] ?? null) !== 'on' && ! empty($merged[$num])) {
                        if (array_key_exists($button, $provided)) {
                            $merged[$num] = null;
                        } elseif (array_key_exists($num, $provided)) {
                            $w[] = "$numLabel is ignored because $flagLabel is not Yes.";
                            $merged[$num] = $now[$num];
                        }
                    }
                }
                if (isset($provided['field_id']) || isset($provided['client_id'])) {
                    $f = $merged['field_id'] ?? null;
                    $cl = $merged['client_id'] ?? null;
                    if ($cl && $f && isset($lookups['client_field'][$cl]) && (int) $lookups['client_field'][$cl] !== (int) $f) {
                        $w[] = 'Client belongs to another field office (' . ($lookups['fields'][$lookups['client_field'][$cl]] ?? '?') . ').';
                    }
                }

                foreach (self::FIELD_LABELS as $attr => $label) {
                    if (! array_key_exists($attr, $merged) || self::same($attr, $now[$attr] ?? null, $merged[$attr])) {
                        continue;
                    }
                    $changes[$attr] = [$label, self::display($attr, $now[$attr] ?? null, $lookups), self::display($attr, $merged[$attr], $lookups)];
                    if (str_starts_with($attr, 'pay.')) {
                        $pay[substr($attr, 4)] = $merged[$attr];
                    } else {
                        $emp[$attr] = $merged[$attr];
                    }
                }

                [$before] = PayPriority::evaluate($now['client_id'] ?? null, $now['field_id'] ?? null, $now['location'] ?? null, $now['gender'] ?? null);
                [$after] = PayPriority::evaluate($merged['client_id'] ?? null, $merged['field_id'] ?? null, $merged['location'] ?? null, $merged['gender'] ?? null);
            }

            $results[$r] = [
                'row' => $r,
                'employee_id' => $id,
                'name' => $now['name'] ?? ($v['full_name'] ?? ''),
                'errors' => $e,
                'warnings' => $w,
                'changes' => $e ? [] : $changes,
                'employee' => $e ? [] : $emp,
                'pay' => $e ? [] : $pay,
                'payment_change' => ! $e && (bool) array_intersect(array_keys($changes), self::PAYMENT_FIELDS),
                'profile_change' => ! $e && (bool) array_diff(array_keys($changes), self::PAYMENT_FIELDS),
                'priority_before' => $before,
                'priority_after' => $after,
            ];
        }

        $ok = array_filter($results, fn ($x) => ! $x['errors']);

        return [
            'results' => $results,
            'summary' => [
                'total' => count($results),
                'changed' => count(array_filter($ok, fn ($x) => $x['changes'])),
                'unchanged' => count(array_filter($ok, fn ($x) => ! $x['changes'])),
                'errors' => count($results) - count($ok),
                'payment_changes' => count(array_filter($ok, fn ($x) => $x['payment_change'])),
                'profile_changes' => count(array_filter($ok, fn ($x) => $x['profile_change'])),
                'status' => EmployeeCreator::workflowFor($user, true)['status'] ?? null,
            ],
        ];
    }

    /** Current details of employees, in the same attribute names convert() produces. */
    public static function currentDetails(array $ids): array
    {
        if (! $ids) {
            return [];
        }
        $out = [];
        $cols = ['id', 'name', 'gender', 'phone_number', 'channel', 'date_of_birth', 'nia_number', 'address', 'marital_status',
            'worker_type', 'date_of_joining', 'department_id', 'role_id', 'field_id', 'client_id', 'location', 'basic_salary',
            'allowances', 'tax_button', 'ssnit_button', 'payment_type', 'gurantor_name', 'gurantor_number', 'gurantor_address',
            'gurantor_nia_number', 'relationship'];
        foreach (['tin_number', 'ssnit_number'] as $c) {
            if (EmployeeCreator::hasColumn('employees', $c)) $cols[] = $c;
        }
        foreach (array_chunk($ids, 500) as $chunk) {
            $pays = DB::table('payment_infos')->whereIn('employee_id', $chunk)->orderBy('id')->get()->groupBy('employee_id');
            foreach (DB::table('employees')->whereIn('id', $chunk)->get($cols) as $row) {
                $d = (array) $row;
                $p = $pays[$row->id][0] ?? null; // first record = what the app shows
                foreach (['bank_id', 'acc_number', 'branch', 'branch_code'] as $c) {
                    $d['pay.' . $c] = $p->$c ?? null;
                }
                foreach (['tin_number', 'ssnit_number'] as $c) {
                    $d[$c] = ($d[$c] ?? null) ?: ($p->$c ?? null); // either place
                }
                $out[(int) $row->id] = $d;
            }
        }

        return $out;
    }

    private static function same(string $attr, $old, $new): bool
    {
        if (in_array($attr, ['basic_salary', 'allowances'], true)) {
            return round((float) $old, 2) === round((float) $new, 2) && ($old === null) === ($new === null);
        }
        if (in_array($attr, ['date_of_birth', 'date_of_joining'], true)) {
            return ($old ? substr((string) $old, 0, 10) : null) === ($new ? substr((string) $new, 0, 10) : null);
        }
        if (in_array($attr, ['department_id', 'role_id', 'field_id', 'client_id', 'pay.bank_id'], true)) {
            return (int) $old === (int) $new;
        }
        $o = $old === null ? '' : trim((string) $old);
        $n = $new === null ? '' : trim((string) $new);

        return in_array($attr, ['gender', 'marital_status', 'worker_type'], true) ? strcasecmp($o, $n) === 0 : $o === $n;
    }

    private static function display(string $attr, $value, array $lookups): string
    {
        if (in_array($attr, ['tax_button', 'ssnit_button'], true)) {
            return $value === 'on' ? 'Yes' : 'No';
        }
        if ($value === null || $value === '') {
            return '(blank)';
        }

        return match ($attr) {
            'department_id' => $lookups['departments'][(int) $value] ?? (string) $value,
            'role_id' => $lookups['roles'][(int) $value] ?? (string) $value,
            'field_id' => $lookups['fields'][(int) $value] ?? (string) $value,
            'client_id' => isset($lookups['clients'][(int) $value]) ? ((array) $lookups['clients'][(int) $value])[0] : 'Client ' . $value,
            'pay.bank_id' => $lookups['banks'][(int) $value] ?? (string) $value,
            'channel' => array_search($value, ['MTN' => 'mtn-gh', 'TELECEL' => 'vodafone-gh', 'AIRTELTIGO' => 'tigo-gh'], true) ?: (string) $value,
            'basic_salary', 'allowances' => number_format((float) $value, 2),
            'date_of_birth', 'date_of_joining' => Carbon::parse($value)->format('d/m/Y'),
            default => ucfirst((string) $value),
        };
    }

    /** Accepts 2026-10-01, 01/10/2026, 1/10/2026, 01-10-2026, 01.10.2026. Rejects impossible dates (31/02/2026). */
    private static function parseDate(string $value): ?string
    {
        foreach (['Y-m-d', 'd/m/Y', 'j/n/Y', 'd-m-Y', 'd.m.Y', 'Y/m/d'] as $format) {
            $d = \DateTime::createFromFormat('!' . $format, $value);
            $problems = \DateTime::getLastErrors();
            if ($d && ! ($problems && ($problems['warning_count'] || $problems['error_count']))) {
                $year = (int) $d->format('Y');
                return ($year >= 1900 && $year <= 2100) ? $d->format('Y-m-d') : null;
            }
        }

        return null;
    }

    private static function uniqueCheck(?string $value, string $key, string $label, array $existing, array $inFile, array &$e, ?int $self = null): void
    {
        if ($value === null) {
            return;
        }
        $lc = mb_strtolower($value);
        $owner = $existing[$key][$lc] ?? null;
        if ($owner !== null && (int) $owner !== $self) {
            $e[] = "$label already exists in ISSOBS (FWSS " . $owner . ').';
        } elseif (count($inFile[$key][$lc] ?? []) > 1) {
            $e[] = "$label is repeated in this file (rows " . implode(', ', $inFile[$key][$lc]) . ').';
        }
    }

    /**
     * "12", "12 | Name" or an exact name (case-insensitive) -> id.
     *
     * @param array<int, string|string[]> $options id => label, or id => [label, other names...]
     */
    private static function resolve(?string $value, array $options, string $label, array &$e, string $notFound = 'was not found'): ?int
    {
        if ($value === null) {
            return null;
        }
        if (preg_match('/^\s*(\d+)\s*(\|.*)?$/', $value, $m)) {
            if (isset($options[(int) $m[1]])) {
                return (int) $m[1];
            }
            $e[] = "$label \"$value\" $notFound.";

            return null;
        }
        $needle = mb_strtolower(trim(preg_replace('/\s+/', ' ', $value)));
        $matches = array_keys(array_filter($options, function ($names) use ($needle) {
            foreach ((array) $names as $name) {
                if (mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $name))) === $needle) {
                    return true;
                }
            }
            return false;
        }));
        if (count($matches) === 1) {
            return (int) $matches[0];
        }
        $e[] = count($matches) > 1
            ? "$label \"$value\" matches more than one entry; use its ID (" . implode(', ', $matches) . ').'
            : "$label \"$value\" $notFound.";

        return null;
    }

    /** Everything a row can refer to, loaded once, scoped to the user. */
    private static function lookups(User $user): array
    {
        $clients = [];
        $clientField = [];
        foreach (EmployeeCreator::allowedClients($user) as $c) {
            // A client can be named by "name business_name", by its name or by its business name.
            $clients[(int) $c->id] = array_values(array_filter([trim($c->name . ' ' . $c->business_name), $c->name, $c->business_name]));
            $clientField[(int) $c->id] = (int) $c->field_id;
        }

        return [
            'departments' => Department::pluck('name', 'id')->mapWithKeys(fn ($n, $id) => [(int) $id => (string) $n])->all(),
            'roles' => Role::pluck('name', 'id')->mapWithKeys(fn ($n, $id) => [(int) $id => (string) $n])->all(),
            'fields' => Field::pluck('name', 'id')->mapWithKeys(fn ($n, $id) => [(int) $id => (string) $n])->all(),
            'banks' => Bank::pluck('name', 'id')->mapWithKeys(fn ($n, $id) => [(int) $id => (string) $n])->all(),
            'clients' => $clients,
            'client_field' => $clientField,
            'allowed_fields' => EmployeeCreator::allowedFieldIds($user),
        ];
    }

    /** Values already in ISSOBS that the file must not repeat: key => [lowercased value => employee id]. */
    private static function existingValues(array $rows): array
    {
        $col = fn ($key) => array_values(array_filter(array_column($rows, $key), fn ($v) => $v !== null));
        $out = [];
        $find = function (string $table, string $column, array $values, string $key, string $idColumn) use (&$out) {
            if (! $values || ! EmployeeCreator::hasColumn($table, $column)) {
                return;
            }
            foreach (array_chunk($values, 500) as $chunk) {
                foreach (DB::table($table)->whereIn($column, $chunk)->get([$column, $idColumn]) as $row) {
                    $out[$key][mb_strtolower((string) $row->$column)] = $row->$idColumn;
                }
            }
        };

        $phones = array_values(array_filter(array_map([self::class, 'normalisePhone'], $col('phone_number'))));
        // Existing records may be stored as 0244... - check both forms.
        $local = array_map(fn ($p) => '0' . substr($p, 3), array_filter($phones, fn ($p) => str_starts_with($p, '233')));
        $find('employees', 'phone_number', array_merge($phones, $local), 'phone_raw', 'id');
        foreach ($out['phone_raw'] ?? [] as $p => $id) {
            $out['phone'][self::normalisePhone($p)] = $id;
        }

        $find('employees', 'nia_number', $col('nia_number'), 'nia', 'id');
        $find('payment_infos', 'acc_number', $col('account_number'), 'acc', 'employee_id');
        $find('employees', 'tin_number', $col('tin_number'), 'tin', 'id');
        $find('payment_infos', 'tin_number', $col('tin_number'), 'tin', 'employee_id');
        $find('employees', 'ssnit_number', $col('ssnit_number'), 'ssnit', 'id');
        $find('payment_infos', 'ssnit_number', $col('ssnit_number'), 'ssnit', 'employee_id');
        $find('employees', 'gurantor_nia_number', $col('guarantor_nia'), 'gnia', 'id');

        return $out;
    }

    /* ========================================================= output files */
    // The template and the error report are Laravel Excel exports:
    // App\Exports\EmployeeUpload\TemplateExport and App\Exports\EmployeeUpload\ErrorReportExport.

    /**
     * Column definitions for the template: header => [required, kind, width, note].
     * kind: text | date | money | list:<NamedRange>. Same order as COLUMNS.
     */
    public const TEMPLATE_COLUMNS = [
        'Full Name' => [true, 'text', 28, "Employee's full name as on the Ghana Card."],
        'Gender' => [true, 'list:Gender', 10, 'Male or Female.'],
        'Phone Number' => [true, 'text', 16, '12 digits starting 233, e.g. 233241234567 (0241234567 is also accepted). Must not already exist.'],
        'MoMo Network' => [false, 'list:MoMoNetwork', 14, 'MTN, TELECEL or AIRTELTIGO.'],
        'Date of Birth' => [false, 'date', 13, 'A real date, e.g. 14/03/1990.'],
        'Ghana Card (NIA) No.' => [false, 'text', 18, 'e.g. GHA-123456789-0. Must be unique.'],
        'Address' => [false, 'text', 26, 'Residential address (max 500 characters).'],
        'Marital Status' => [false, 'list:MaritalStatus', 13, 'Single, Married, Divorced or Widowed.'],
        'Worker Type' => [false, 'list:WorkerType', 12, 'Employee or Contractor.'],
        'Date of Joining' => [false, 'date', 14, 'Employment start date.'],
        'Department' => [false, 'list:Departments', 18, 'Pick from the list, or type the department ID.'],
        'Role' => [false, 'list:Roles', 16, 'Pick from the list, or type the role ID.'],
        'Field Office' => [true, 'list:FieldOffice', 16, 'Only the field offices you can assign are listed.'],
        'Client' => [false, 'list:Clients', 30, 'Only active, approved clients you can assign are listed. Pick from the list or type the client ID.'],
        'Location' => [false, 'text', 18, 'Post / site name, e.g. CAPE COAST. This also sets payment priority.'],
        'Basic Salary' => [false, 'money', 13, 'Monthly basic in GH¢, numbers only.'],
        'Allowances' => [false, 'money', 12, 'Monthly allowances in GH¢, numbers only.'],
        'Deduct Tax' => [false, 'list:YesNo', 11, 'Yes = deduct PAYE (same as ticking TIN on the form).'],
        'TIN Number' => [false, 'text', 16, 'Required when Deduct Tax = Yes. Must be unique.'],
        'Deduct SSNIT' => [false, 'list:YesNo', 12, 'Yes = deduct SSNIT (same as ticking SSNIT on the form).'],
        'SSNIT Number' => [false, 'text', 16, 'Required when Deduct SSNIT = Yes. Must be unique.'],
        'Payment Type' => [true, 'list:PaymentType', 13, 'Cash or Bank.'],
        'Bank' => [false, 'list:Banks', 16, 'Required when Payment Type = Bank.'],
        'Account Number' => [false, 'text', 18, 'Required when Payment Type = Bank. Must be unique.'],
        'Branch' => [false, 'text', 16, 'Bank branch name.'],
        'Branch Code' => [false, 'text', 12, 'Bank branch code.'],
        'Guarantor Name' => [false, 'text', 22, ''],
        'Guarantor Phone' => [false, 'text', 16, 'Max 20 characters.'],
        'Guarantor Address' => [false, 'text', 24, ''],
        'Guarantor NIA No.' => [false, 'text', 18, 'Must be unique.'],
        'Relationship' => [false, 'text', 14, "Guarantor's relationship to the employee."],
    ];
}
