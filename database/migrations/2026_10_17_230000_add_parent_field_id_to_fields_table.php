<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * fields.parent_field_id — a field office can be a sub-office of another
 * (Field::parent()/children(); used by Expenses and Overtime to group an office
 * with its sub-offices). The code has used this column since 10 Aug 2026 but no
 * migration created it, so a fresh install or a database where it was not added
 * by hand fails with "Unknown column 'parent_field_id'".
 *
 * Safe on any database:
 *  - if the column already exists (added by hand), nothing is changed;
 *  - otherwise it is added, and — matching the rule the rest of the code already
 *    applies ("Tema also sees Shai Hills") — Shai Hills is set as a sub-office of
 *    Tema when both offices exist. Change it later on the fields table if needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('fields', 'parent_field_id')) {
            return;
        }

        $this->withZeroDatesAllowed(function () {
            Schema::table('fields', function (Blueprint $table) {
                $table->unsignedBigInteger('parent_field_id')->nullable()->after('name')->index();
            });
        });

        $tema = DB::table('fields')->whereRaw('LOWER(TRIM(name)) = ?', ['tema'])->value('id');
        $shai = DB::table('fields')->whereRaw("LOWER(REPLACE(name, ' ', '')) LIKE ?", ['%shaihill%'])->value('id');
        if ($tema && $shai && $tema !== $shai) {
            DB::table('fields')->where('id', $shai)->update(['parent_field_id' => $tema]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('fields', 'parent_field_id')) {
            return;
        }

        $this->withZeroDatesAllowed(function () {
            Schema::table('fields', function (Blueprint $table) {
                $table->dropIndex(['parent_field_id']);
                $table->dropColumn('parent_field_id');
            });
        });
    }

    /**
     * Some older rows may have created_at = '0000-00-00 00:00:00'; MySQL versions that
     * rebuild the table to add a column reject those under strict mode (error 1292).
     * Allow them for this connection only while the column is added. No data changes.
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
