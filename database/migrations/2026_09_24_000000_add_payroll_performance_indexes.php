<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the payroll screens and the salaries report.
 *
 * None of these tables had an index beyond the primary key, so every
 * "salaries for month X" query was a full table scan and the report's
 * joiner/leaver self-join was quadratic.
 *
 * Plain (non-unique) indexes only, so this is safe on data that already
 * contains duplicates. Once the "Duplicate payslips" check on the report
 * shows none, consider making salaries(employee_id, salary_month) UNIQUE
 * so the database itself prevents double payroll entries.
 *
 * Idempotent: indexes that already exist are skipped, so it is safe to re-run
 * after a partial failure (MySQL cannot roll back DDL).
 *
 * String columns are indexed on a 32-character PREFIX on MySQL/MariaDB. A full
 * varchar(255) is up to 1,020 bytes in utf8mb4, which exceeds MyISAM's 1,000-byte
 * key limit (error 1071) and older InnoDB's 767-byte limit. Status values
 * ('pending', 'approved', 'Active', ...) are far shorter than 32 characters,
 * so the prefix finds exactly the same rows.
 */
return new class extends Migration
{
    private array $indexes = [
        'salaries' => [
            'salaries_month_status_idx' => ['salary_month', 'payment_status'],
            'salaries_employee_month_idx' => ['employee_id', 'salary_month'],
            'salaries_client_month_idx' => ['client_id', 'salary_month'],
            'salaries_field_month_idx' => ['field_id', 'salary_month'],
        ],
        'invoices' => [
            'invoices_client_month_idx' => ['client_id', 'invoice_month'],
            'invoices_month_idx' => ['invoice_month'],
        ],
        'receipts' => [
            'receipts_invoice_idx' => ['invoice_id'],
        ],
        'categories' => [
            'categories_month_client_idx' => ['category_month', 'client_id'],
        ],
        'salary_top_ups' => [
            'salary_top_ups_month_idx' => ['salary_month'],
            'salary_top_ups_salary_idx' => ['salary_id'],
        ],
        'employees' => [
            'employees_status_approval_field_idx' => ['status', 'ho_status', 'field_id'],
        ],
        'payment_infos' => [
            'payment_infos_employee_idx' => ['employee_id'],
        ],
        'nrrit' => [
            'nrrit_status_month_idx' => ['status_month'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($indexes as $name => $columns) {
                if (Schema::hasIndex($table, $name) || Schema::hasIndex($table, $columns)) {
                    continue;
                }
                $this->createIndex($table, $name, $columns);
            }
        }
    }

    /** Characters of a string column to index (32 x 4 bytes = 128 bytes in utf8mb4). */
    private const STRING_PREFIX = 32;

    private function createIndex(string $table, string $name, array $columns): void
    {
        $driver = DB::connection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));

            return;
        }

        $types = collect(Schema::getColumns($table))->pluck('type_name', 'name')->map(fn ($t) => strtolower($t));
        $isString = fn ($column) => in_array($types[$column] ?? '', ['varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext'], true);

        $parts = array_map(
            fn ($column) => '`'.$column.'`'.($isString($column) ? '('.self::STRING_PREFIX.')' : ''),
            $columns
        );

        $this->withoutStrictDates(fn () => DB::statement(
            sprintf('ALTER TABLE `%s` ADD INDEX `%s` (%s)', DB::getTablePrefix().$table, $name, implode(', ', $parts))
        ));
    }

    /**
     * On MyISAM (and MySQL's COPY algorithm) ADD/DROP INDEX rebuilds the table and re-validates
     * every existing row against the session sql_mode. Laravel connects in strict mode, so legacy
     * rows holding '0000-00-00 00:00:00' (e.g. employees.created_at) abort the rebuild with
     * error 1292. Relax only the date checks, only around this statement, then restore the
     * original mode. No data is modified.
     */
    private function withoutStrictDates(callable $callback): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $callback();

            return;
        }

        $original = (string) DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;
        $relaxed = implode(',', array_diff(
            explode(',', $original),
            ['NO_ZERO_DATE', 'NO_ZERO_IN_DATE', 'STRICT_TRANS_TABLES', 'STRICT_ALL_TABLES', 'TRADITIONAL']
        ));

        DB::statement('SET SESSION sql_mode = '.DB::getPdo()->quote($relaxed));
        try {
            $callback();
        } finally {
            DB::statement('SET SESSION sql_mode = '.DB::getPdo()->quote($original));
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach (array_keys($indexes) as $name) {
                if (Schema::hasIndex($table, $name)) {
                    $this->withoutStrictDates(fn () => Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name)));
                }
            }
        }
    }
};