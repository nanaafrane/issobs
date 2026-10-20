<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * employees.assit_status / assit_date — the assistant's approval step on a new
 * or edited employee (pending / approved, and when). The code has used these
 * since July 2026 (EmployeeController, EmployeeCreator, the pending list) but
 * no migration created them, so a database where they were not added by hand
 * fails with "Unknown column 'assit_status'".
 *
 * Safe on any database: each column is only added if it is missing; existing
 * data is not touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        $missing = array_values(array_filter(['assit_status', 'assit_date'], fn ($c) => ! Schema::hasColumn('employees', $c)));
        if (! $missing) {
            return;
        }

        $this->withZeroDatesAllowed(function () use ($missing) {
            Schema::table('employees', function (Blueprint $table) use ($missing) {
                if (in_array('assit_status', $missing, true)) {
                    $table->string('assit_status')->nullable()->after('bran_date');
                }
                if (in_array('assit_date', $missing, true)) {
                    $table->date('assit_date')->nullable()->after(in_array('assit_status', $missing, true) ? 'assit_status' : 'bran_date');
                }
            });
        });
    }

    public function down(): void
    {
        $present = array_values(array_filter(['assit_status', 'assit_date'], fn ($c) => Schema::hasColumn('employees', $c)));
        if (! $present) {
            return;
        }

        $this->withZeroDatesAllowed(function () use ($present) {
            Schema::table('employees', fn (Blueprint $table) => $table->dropColumn($present));
        });
    }

    /**
     * Older rows may have created_at = '0000-00-00 00:00:00'; MySQL versions that
     * rebuild the table to add a column reject those under strict mode (error 1292).
     * Allow them for this connection only while the columns are added.
     */
    private function withZeroDatesAllowed(callable $change): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $change();

            return;
        }

        $mode = DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;
        DB::statement('SET SESSION sql_mode = ?', [implode(',', array_diff(explode(',', $mode), ['NO_ZERO_DATE', 'NO_ZERO_IN_DATE']))]);
        try {
            $change();
        } finally {
            DB::statement('SET SESSION sql_mode = ?', [$mode]);
        }
    }
};
