<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Collections can be kept and spent where they are received:
 *  banks.field_id   the office a cash box (e.g. "Tema office cash") or MoMo wallet belongs to.
 *  banks.kind       gains "momo" — the company's mobile-money wallet (no new column).
 * Safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('banks', 'field_id')) {
            return;
        }
        $this->withZeroDatesAllowed(fn () => Schema::table('banks', function (Blueprint $table) {
            $table->unsignedBigInteger('field_id')->nullable()->after('custodian')->index();
        }));
    }

    public function down(): void
    {
        if (! Schema::hasColumn('banks', 'field_id')) {
            return;
        }
        $this->withZeroDatesAllowed(fn () => Schema::table('banks', function (Blueprint $table) {
            $table->dropIndex(['field_id']);
            $table->dropColumn('field_id');
        }));
    }

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
