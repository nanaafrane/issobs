<?php

namespace App\Support;

use App\Models\Bank;
use App\Models\Department;
use App\Models\Field;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Bulk employee upload: read the template, check every row, build the error report
 * and the per-user template. Creating the employees is done by EmployeeCreator,
 * exactly as for the single "Add employee" form.
 */
class EmployeeBulkImport
{
    public const MAX_ROWS = 1000;

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
     * @return array{rows: array<int, array<string, string|null>>, error: ?string, headers: array<int, string>}
     *   rows keyed by spreadsheet row number
     */
    public static function read(string $path): array
    {
        try {
            $book = IOFactory::load($path);
        } catch (\Throwable $e) {
            return ['rows' => [], 'headers' => [], 'error' => 'The file could not be read. Upload the .xlsx template.'];
        }
        $sheet = $book->getSheetByName('Employees') ?? $book->getActiveSheet();

        // Find the header row (row 2 in the template; also accept row 1).
        $headerRow = null;
        $map = [];
        foreach ([2, 1, 3] as $r) {
            if ($r > $sheet->getHighestRow()) {
                continue;
            }
            $found = [];
            foreach ($sheet->getRowIterator($r, $r)->current()->getCellIterator() as $cell) {
                $label = trim(preg_replace('/\s*\*\s*$/', '', (string) $cell->getValue()));
                if (isset(self::COLUMNS[$label])) {
                    $found[$cell->getColumn()] = self::COLUMNS[$label];
                }
            }
            if (count($found) >= 5) {
                $headerRow = $r;
                $map = $found;
                break;
            }
        }
        if (! $headerRow) {
            return ['rows' => [], 'headers' => [], 'error' => 'This is not the employee template: its column headers were not found.'];
        }
        $missing = array_diff(self::REQUIRED, $map);
        if ($missing) {
            return ['rows' => [], 'headers' => [], 'error' => 'Required columns are missing: ' . implode(', ', array_map(fn ($k) => array_search($k, self::COLUMNS), $missing)) . '.'];
        }

        $rows = [];
        $last = $sheet->getHighestDataRow();
        for ($r = $headerRow + 1; $r <= $last; $r++) {
            $values = [];
            $any = false;
            foreach ($map as $col => $key) {
                $cell = $sheet->getCell($col . $r);
                $v = $cell->getValue();
                if (is_string($v) && str_starts_with($v, '=')) {
                    $v = $cell->getCalculatedValue();
                }
                if (in_array($key, ['date_of_birth', 'date_of_joining'], true) && is_numeric($v)) {
                    $v = ExcelDate::excelToDateTimeObject((float) $v)->format('Y-m-d');
                } elseif (is_float($v) && floor($v) == $v && ! in_array($key, ['basic_salary', 'allowances'], true)) {
                    $v = sprintf('%.0f', $v); // numbers typed into text columns (phone, account...)
                }
                $v = $v === null ? null : trim((string) $v);
                $values[$key] = $v === '' ? null : $v;
                $any = $any || $values[$key] !== null;
            }
            if ($any) {
                $rows[$r] = $values;
            }
        }

        if (count($rows) > self::MAX_ROWS) {
            return ['rows' => [], 'headers' => [], 'error' => 'The file has ' . count($rows) . ' employees. The limit is ' . self::MAX_ROWS . ' per upload.'];
        }

        return ['rows' => $rows, 'headers' => $map, 'error' => null];
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

    /**
     * Check every row. Nothing is saved.
     *
     * @return array{results: array<int, array>, summary: array}
     *   result: row, name, field, client, priority, errors[], warnings[], employee[], pay[]
     */
    public static function validate(array $rows, User $user): array
    {
        $lookups = self::lookups($user);
        $existing = self::existingValues($rows);
        $inFile = [];
        foreach ($rows as $r => $v) {
            foreach (['phone' => self::normalisePhone($v['phone_number']), 'nia' => $v['nia_number'], 'acc' => $v['account_number'],
                      'tin' => $v['tin_number'], 'ssnit' => $v['ssnit_number'], 'gnia' => $v['guarantor_nia']] as $k => $val) {
                if ($val !== null) {
                    $inFile[$k][mb_strtolower($val)][] = $r;
                }
            }
        }
        $workflow = EmployeeCreator::workflowFor($user);

        $results = [];
        foreach ($rows as $r => $v) {
            $e = [];
            $w = [];
            $emp = [];
            $pay = [];

            foreach (self::REQUIRED as $key) {
                if ($v[$key] === null) {
                    $e[] = array_search($key, self::COLUMNS) . ' is required.';
                }
            }

            $emp['name'] = $v['full_name'];
            if ($v['full_name'] !== null && mb_strlen($v['full_name']) > 255) $e[] = 'Full Name is too long (max 255).';

            $g = strtolower((string) $v['gender']);
            if ($v['gender'] !== null && ! in_array($g, ['male', 'female'], true)) $e[] = 'Gender must be Male or Female.';
            $emp['gender'] = $g ?: null;

            // Phone: normalised to 233XXXXXXXXX, unique in ISSOBS and in this file.
            $phone = self::normalisePhone($v['phone_number']);
            if ($v['phone_number'] !== null) {
                if (! preg_match('/^233\d{9}$/', $phone)) {
                    $e[] = 'Phone Number must be a Ghana number, e.g. 233241234567.';
                } elseif (isset($existing['phone'][$phone])) {
                    $e[] = 'Phone Number already belongs to FWSS ' . $existing['phone'][$phone] . '.';
                } elseif (count($inFile['phone'][mb_strtolower($phone)] ?? []) > 1) {
                    $e[] = 'Phone Number is repeated in this file (rows ' . implode(', ', $inFile['phone'][mb_strtolower($phone)]) . ').';
                }
            }
            $emp['phone_number'] = $phone;

            if ($v['momo_network'] !== null) {
                $n = strtoupper($v['momo_network']);
                if (! isset(self::NETWORKS[$n])) $e[] = 'MoMo Network must be MTN, TELECEL or AIRTELTIGO.';
                $emp['channel'] = self::NETWORKS[$n] ?? null;
            }

            foreach (['date_of_birth' => 'Date of Birth', 'date_of_joining' => 'Date of Joining'] as $key => $label) {
                $emp[$key] = null;
                if ($v[$key] !== null) {
                    $d = self::parseDate($v[$key]);
                    if (! $d) $e[] = "$label is not a valid date.";
                    $emp[$key] = $d;
                }
            }
            if ($emp['date_of_birth'] && Carbon::parse($emp['date_of_birth'])->gt(now()->subYears(16))) {
                $w[] = 'Date of Birth makes this person younger than 16.';
            }

            self::uniqueCheck($v['nia_number'], 'nia', 'Ghana Card (NIA) No.', $existing, $inFile, $e);
            $emp['nia_number'] = $v['nia_number'];
            if ($v['nia_number'] !== null && mb_strlen($v['nia_number']) > 50) $e[] = 'Ghana Card (NIA) No. is too long (max 50).';

            $emp['address'] = $v['address'];
            if ($v['address'] !== null && mb_strlen($v['address']) > 500) $e[] = 'Address is too long (max 500).';

            if ($v['marital_status'] !== null) {
                $m = strtolower($v['marital_status']);
                if (! in_array($m, ['single', 'married', 'divorced', 'widowed'], true)) $e[] = 'Marital Status must be Single, Married, Divorced or Widowed.';
                $emp['marital_status'] = $m;
            }
            if ($v['worker_type'] !== null) {
                $t = strtolower($v['worker_type']);
                if (! in_array($t, ['employee', 'contractor'], true)) $e[] = 'Worker Type must be Employee or Contractor.';
                $emp['worker_type'] = $t;
            }

            $emp['department_id'] = self::resolve($v['department'], $lookups['departments'], 'Department', $e);
            $emp['role_id'] = self::resolve($v['role'], $lookups['roles'], 'Role', $e);
            if ($v['department'] === null) $w[] = 'No Department given.';
            if ($v['role'] === null) $w[] = 'No Role given.';

            // Field office + client must be within what this user may assign.
            $fieldId = self::resolve($v['field_office'], $lookups['fields'], 'Field Office', $e);
            if ($fieldId && ! in_array($fieldId, $lookups['allowed_fields'], true)) {
                $e[] = 'Field Office "' . $lookups['fields'][$fieldId] . '" is not one you can assign.';
                $fieldId = null;
            }
            $emp['field_id'] = $fieldId;

            $clientId = null;
            if ($v['client'] !== null) {
                $clientId = self::resolve($v['client'], $lookups['clients'], 'Client', $e, 'is not an active, approved client you can assign');
                if ($clientId && $fieldId && (int) $lookups['client_field'][$clientId] !== $fieldId) {
                    $w[] = 'Client belongs to another field office (' . ($lookups['fields'][$lookups['client_field'][$clientId]] ?? '?') . ').';
                }
            } else {
                $w[] = 'No Client given.';
            }
            $emp['client_id'] = $clientId;

            $emp['location'] = $v['location'];
            if ($v['location'] !== null && mb_strlen($v['location']) > 255) $e[] = 'Location is too long (max 255).';

            foreach (['basic_salary' => 'Basic Salary', 'allowances' => 'Allowances'] as $key => $label) {
                $emp[$key] = null;
                if ($v[$key] !== null) {
                    $num = str_replace([',', 'GH₵', 'GHS', ' '], '', $v[$key]);
                    if (! is_numeric($num) || (float) $num < 0) $e[] = "$label must be a number of 0 or more.";
                    else $emp[$key] = round((float) $num, 2);
                }
            }
            if ($v['basic_salary'] === null) $w[] = 'No Basic Salary given.';

            // Tax / SSNIT: "Yes" = the form's ticked checkbox ("on").
            foreach ([['deduct_tax', 'tin_number', 'tax_button', 'tin', 'Deduct Tax', 'TIN Number'],
                      ['deduct_ssnit', 'ssnit_number', 'ssnit_button', 'ssnit', 'Deduct SSNIT', 'SSNIT Number']] as [$flag, $num, $button, $k, $flagLabel, $numLabel]) {
                $yes = null;
                if ($v[$flag] !== null) {
                    $f = strtolower($v[$flag]);
                    if (! in_array($f, ['yes', 'no', 'y', 'n'], true)) $e[] = "$flagLabel must be Yes or No.";
                    $yes = in_array($f, ['yes', 'y'], true);
                }
                $emp[$button] = $yes ? 'on' : null;
                if ($yes && $v[$num] === null) $e[] = "$numLabel is required when $flagLabel is Yes.";
                self::uniqueCheck($v[$num], $k, $numLabel, $existing, $inFile, $e);
                // As on the form: the number is only kept when the deduction is switched on.
                $emp[$num] = $yes ? $v[$num] : null;
                if (! $yes && $v[$num] !== null) $w[] = "$numLabel is ignored because $flagLabel is not Yes.";
            }

            $type = ucfirst(strtolower((string) $v['payment_type']));
            if ($v['payment_type'] !== null && ! in_array($type, ['Cash', 'Bank'], true)) $e[] = 'Payment Type must be Cash or Bank.';
            $emp['payment_type'] = $type ?: null;

            $pay['bank_id'] = $v['bank'] !== null ? self::resolve($v['bank'], $lookups['banks'], 'Bank', $e) : null;
            if ($type === 'Bank' && $v['bank'] === null) $e[] = 'Bank is required when Payment Type is Bank.';
            if ($type === 'Bank' && $v['account_number'] === null) $e[] = 'Account Number is required when Payment Type is Bank.';
            self::uniqueCheck($v['account_number'], 'acc', 'Account Number', $existing, $inFile, $e);
            $pay['acc_number'] = $v['account_number'];
            $pay['branch'] = $v['branch'];
            $pay['branch_code'] = $v['branch_code'];

            $emp['gurantor_name'] = $v['guarantor_name'];
            $emp['gurantor_number'] = $v['guarantor_phone'];
            if ($v['guarantor_phone'] !== null && mb_strlen($v['guarantor_phone']) > 20) $e[] = 'Guarantor Phone is too long (max 20).';
            $emp['gurantor_address'] = $v['guarantor_address'];
            self::uniqueCheck($v['guarantor_nia'], 'gnia', 'Guarantor NIA No.', $existing, $inFile, $e);
            $emp['gurantor_nia_number'] = $v['guarantor_nia'];
            $emp['relationship'] = $v['relationship'];

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

    private static function uniqueCheck(?string $value, string $key, string $label, array $existing, array $inFile, array &$e): void
    {
        if ($value === null) {
            return;
        }
        $lc = mb_strtolower($value);
        if (isset($existing[$key][$lc])) {
            $e[] = "$label already exists in ISSOBS (FWSS " . $existing[$key][$lc] . ').';
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

    /**
     * Column definitions for the template: header => [required, kind, width, note].
     * kind: text | date | money | list:<NamedRange>. Same order as COLUMNS.
     */
    private const TEMPLATE_COLUMNS = [
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

    /**
     * The blank template, built from scratch for this user: dropdowns list only the
     * field offices, clients and banks they can assign. (Built in code rather than by
     * editing a stored file, so every dropdown is defined exactly once.)
     */
    public static function templateFor(User $user): Spreadsheet
    {
        $first = 3;
        $last = $first + self::MAX_ROWS - 1;
        $headers = array_keys(self::TEMPLATE_COLUMNS);
        $n = count($headers);
        $L = fn (int $i) => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
        $lastCol = $L($n);
        $checkCol = $L($n + 1);
        $colOf = array_combine(array_values(self::COLUMNS), array_map($L, range(1, $n)));

        $fieldNames = Field::pluck('name', 'id');
        $lists = [
            'Gender' => ['Male', 'Female'],
            'MoMoNetwork' => ['MTN', 'TELECEL', 'AIRTELTIGO'],
            'MaritalStatus' => ['Single', 'Married', 'Divorced', 'Widowed'],
            'WorkerType' => ['Employee', 'Contractor'],
            'YesNo' => ['Yes', 'No'],
            'PaymentType' => ['Cash', 'Bank'],
            'FieldOffice' => array_map(fn ($id) => $id . ' | ' . ($fieldNames[$id] ?? 'Field ' . $id), EmployeeCreator::allowedFieldIds($user)),
            'Departments' => Department::orderBy('name')->get(['id', 'name'])->map(fn ($d) => $d->id . ' | ' . $d->name)->all(),
            'Roles' => Role::orderBy('name')->get(['id', 'name'])->map(fn ($r) => $r->id . ' | ' . $r->name)->all(),
            'Clients' => EmployeeCreator::allowedClients($user)->map(fn ($c) => $c->id . ' | ' . trim($c->name . ' ' . $c->business_name))->all(),
            'Banks' => Bank::orderBy('name')->get(['id', 'name'])->map(fn ($b) => $b->id . ' | ' . $b->name)->all(),
        ];
        $loose = ['FieldOffice', 'Departments', 'Roles', 'Clients', 'Banks']; // typing an ID is allowed too

        $book = new Spreadsheet();
        $book->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $navy = 'FF1F3864';
        $red = 'FFC00000';
        $fill = fn ($range, $argb, $sheet) => $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($argb);

        /* ---- Instructions ---- */
        $ins = $book->getActiveSheet()->setTitle('Instructions');
        $ins->getColumnDimension('A')->setWidth(3);
        $ins->getColumnDimension('B')->setWidth(110);
        $ins->setShowGridlines(false);
        $lines = [
            ['ISSOBS - Bulk employee upload template', 'title'],
            ['Prepared for ' . $user->name . ' on ' . now()->format('d M Y') . '. The lists only show the field offices and clients you can assign.', 'note'],
            ['', null],
            ['How to use', 'h'],
            ['1.  Fill in the "Employees" sheet, one employee per row, starting at row 3.', null],
            ['2.  Red column headers are required. Hover over a header to see what it expects.', null],
            ['3.  Watch the grey "Row check" column: every filled row should say OK before you upload.', null],
            ['4.  In ISSOBS go to Employees > Bulk upload, choose this file and who to ASSIGN TO, then Preview.', null],
            ['5.  Nothing is saved at the preview step. Fix any rows shown in red (or download the error report), then Confirm.', null],
            ['', null],
            ['Rules checked on upload', 'h'],
            ['-  Phone, Ghana Card, account, TIN and SSNIT numbers must not already exist in ISSOBS or repeat in this file.', null],
            ['-  Field office and client must be ones you can assign. Payment Type = Bank needs Bank and Account Number.', null],
            ['-  Deduct Tax = Yes needs a TIN; Deduct SSNIT = Yes needs an SSNIT number.', null],
            ['-  Employees get the same approval status as the single "Add employee" form for your role. Payment priority is set automatically.', null],
            ['', null],
            ['Tips', 'h'],
            ['-  Number columns such as phone and account numbers are formatted as text so Excel keeps leading zeros.', null],
            ['-  Do not rename, reorder or delete columns. Photos are added later from each employee profile. Up to ' . self::MAX_ROWS . ' employees per file.', null],
        ];
        foreach ($lines as $i => [$text, $kind]) {
            $c = 'B' . ($i + 1);
            $ins->setCellValue($c, $text);
            $ins->getStyle($c)->getAlignment()->setWrapText(true);
            if ($kind === 'title') $ins->getStyle($c)->getFont()->setBold(true)->setSize(16)->getColor()->setARGB($navy);
            if ($kind === 'h') $ins->getStyle($c)->getFont()->setBold(true)->setSize(12)->getColor()->setARGB($red);
            if ($kind === 'note') $ins->getStyle($c)->getFont()->setItalic(true);
        }

        /* ---- Lists ---- */
        $ls = $book->createSheet()->setTitle('Lists');
        $i = 1;
        foreach ($lists as $name => $values) {
            $col = $L($i++);
            $ls->setCellValue($col . '1', $name);
            $ls->getStyle($col . '1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $fill($col . '1', $navy, $ls);
            foreach (array_values($values) as $r => $v) {
                $ls->setCellValueExplicit($col . ($r + 2), $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $ls->getColumnDimension($col)->setWidth($name === 'Clients' ? 40 : 18);
            $book->addNamedRange(new NamedRange($name, $ls, '$' . $col . '$2:$' . $col . '$' . max(2, count($values) + 1)));
        }

        /* ---- Employees ---- */
        $ws = $book->createSheet(1)->setTitle('Employees');
        $ws->setCellValue('A1', 'Fill in one employee per row from row 3. Red headers are required. Do not rename, move or delete columns. The last column checks each row for you.');
        $ws->mergeCells('A1:' . $checkCol . '1');
        $ws->getStyle('A1')->getFont()->setBold(true)->getColor()->setARGB($navy);
        foreach ($headers as $idx => $header) {
            [$required, $kind, $width, $note] = self::TEMPLATE_COLUMNS[$header];
            $col = $L($idx + 1);
            $ws->setCellValue($col . '2', $header . ($required ? ' *' : ''));
            $ws->getColumnDimension($col)->setWidth($width);
            $fill($col . '2', $required ? $red : $navy, $ws);
            if ($note !== '') {
                $ws->getComment($col . '2')->getText()->createTextRun($note);
                $ws->getComment($col . '2')->setWidth('240pt')->setHeight('60pt');
            }

            $range = $col . $first . ':' . $col . $last;
            if ($kind === 'text') {
                $ws->getStyle($range)->getNumberFormat()->setFormatCode('@');
            } elseif ($kind === 'date') {
                $ws->getStyle($range)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $ws->setDataValidation($range, (new DataValidation())->setType(DataValidation::TYPE_DATE)
                    ->setOperator(DataValidation::OPERATOR_BETWEEN)->setFormula1('DATE(1940,1,1)')->setFormula2('DATE(2100,12,31)')
                    ->setAllowBlank(true)->setShowErrorMessage(true)->setErrorTitle('Date')->setError('Enter a real date, e.g. 14/03/1990.'));
            } elseif ($kind === 'money') {
                $ws->getStyle($range)->getNumberFormat()->setFormatCode('#,##0.00');
                $ws->setDataValidation($range, (new DataValidation())->setType(DataValidation::TYPE_DECIMAL)
                    ->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL)->setFormula1('0')
                    ->setAllowBlank(true)->setShowErrorMessage(true)->setErrorTitle('Numbers only')->setError('Enter an amount of 0 or more.'));
            } else {
                $name = substr($kind, 5);
                $ws->setDataValidation($range, (new DataValidation())->setType(DataValidation::TYPE_LIST)
                    ->setErrorStyle(in_array($name, $loose, true) ? DataValidation::STYLE_WARNING : DataValidation::STYLE_STOP)
                    ->setAllowBlank(true)->setShowDropDown(true)->setShowErrorMessage(true)
                    ->setErrorTitle('Choose from the list')->setError('Pick a value from the list.')
                    ->setFormula1($name)); // named range; stored without "=" as Excel expects
            }
        }
        $ws->getStyle('A2:' . $lastCol . '2')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $ws->getStyle('A2:' . $checkCol . '2')->getAlignment()->setWrapText(true)->setVertical('center')->setHorizontal('center');
        $ws->getRowDimension(2)->setRowHeight(34);
        $fill('A' . $first . ':' . $lastCol . $last, 'FFFFF2CC', $ws);   // cells to fill in

        // Row check (not uploaded): same logic as the importer's basic checks.
        $ws->setCellValue($checkCol . '2', 'Row check (automatic)');
        $fill($checkCol . '2', 'FFE7E6E6', $ws);
        $ws->getStyle($checkCol . '2')->getFont()->setBold(true);
        $ws->getColumnDimension($checkCol)->setWidth(30);
        $c = $colOf;
        for ($r = $first; $r <= $last; $r++) {
            $ws->setCellValue($checkCol . $r,
                "=IF(COUNTA(A{$r}:{$lastCol}{$r})=0,\"\","
                . "IF(OR({$c['full_name']}{$r}=\"\",{$c['gender']}{$r}=\"\",{$c['phone_number']}{$r}=\"\",{$c['field_office']}{$r}=\"\",{$c['payment_type']}{$r}=\"\"),\"Missing required field\","
                . "IF(AND({$c['payment_type']}{$r}=\"Bank\",OR({$c['bank']}{$r}=\"\",{$c['account_number']}{$r}=\"\")),\"Bank and account number required\","
                . "IF(AND({$c['deduct_tax']}{$r}=\"Yes\",{$c['tin_number']}{$r}=\"\"),\"TIN required (Deduct Tax = Yes)\","
                . "IF(AND({$c['deduct_ssnit']}{$r}=\"Yes\",{$c['ssnit_number']}{$r}=\"\"),\"SSNIT number required\","
                . "IF(COUNTIF(\${$c['phone_number']}\${$first}:\${$c['phone_number']}\${$last},{$c['phone_number']}{$r})>1,\"Phone repeated in this file\",\"OK\"))))))");
        }
        $fill($checkCol . $first . ':' . $checkCol . $last, 'FFE7E6E6', $ws);
        $ws->getStyle($checkCol . $first . ':' . $checkCol . $last)->getFont()->setBold(true);
        $ws->freezePane('B3');

        $book->setActiveSheetIndexByName('Employees');

        return $book;
    }

    /** The uploaded rows with an "Errors" column, for rows that could not be imported. */
    public static function errorReport(string $path, array $results): Spreadsheet
    {
        $source = IOFactory::load($path);
        $sheet = $source->getSheetByName('Employees') ?? $source->getActiveSheet();

        $book = new Spreadsheet();
        $out = $book->getActiveSheet()->setTitle('Employees');
        $out->setCellValue('A1', 'Rows that were NOT imported. Fix them here (the "Errors" column explains why), delete the Errors and Row columns, and upload again.');
        $out->getStyle('A1')->getFont()->setBold(true)->getColor()->setARGB('FFC00000');

        $highestCol = $sheet->getHighestDataColumn(2);
        $headers = $sheet->rangeToArray('A2:' . $highestCol . '2', null, false, false)[0];
        $headers = array_values(array_filter($headers, fn ($h) => $h !== null && ! str_starts_with((string) $h, 'Row check')));
        $out->fromArray(array_merge($headers, ['Errors', 'Sheet row']), null, 'A2');
        $out->getStyle('A2:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers) + 2) . '2')->getFont()->setBold(true);

        $r = 3;
        foreach ($results as $res) {
            if (! $res['errors']) {
                continue;
            }
            $values = $sheet->rangeToArray('A' . $res['row'] . ':' . $highestCol . $res['row'], null, false, false)[0];
            $values = array_slice($values, 0, count($headers));
            foreach ($values as $i => $v) {
                $out->setCellValueExplicit(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1) . $r, $v === null ? '' : (string) $v,
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $errCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers) + 1);
            $out->setCellValue($errCol . $r, implode("\n", $res['errors']));
            $out->getStyle($errCol . $r)->getFont()->getColor()->setARGB('FFC00000');
            $out->getStyle($errCol . $r)->getAlignment()->setWrapText(true);
            $out->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers) + 2) . $r, $res['row']);
            $r++;
        }
        foreach (range(1, count($headers) + 2) as $i) {
            $out->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setWidth($i === count($headers) + 1 ? 60 : 18);
        }

        return $book;
    }
}
