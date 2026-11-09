<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * salaries.shift: the guard's shift at his client (day | night | rotate) when the month's
 * salaries were generated - a snapshot, like client and location, so later roster changes
 * do not rewrite past months. Older salaries stay blank: the pages show the current shift
 * for those, marked "now".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('salaries', 'shift')) {
            return;
        }
        $this->withoutStrictDates(function () {
            Schema::table('salaries', function (Blueprint $table) {
                $table->string('shift', 10)->nullable()->after('location');
            });
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('salaries', 'shift')) {
            Schema::table('salaries', fn (Blueprint $t) => $t->dropColumn('shift'));
        }
    }

    /** Old tables may hold 0000-00-00 dates: relax only the date checks around the ALTER. */
    private function withoutStrictDates(callable $callback): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $callback();

            return;
        }
        $original = (string) DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;
        $relaxed = implode(',', array_diff(explode(',', $original), ['NO_ZERO_DATE', 'NO_ZERO_IN_DATE', 'STRICT_TRANS_TABLES', 'STRICT_ALL_TABLES', 'TRADITIONAL']));
        DB::statement('SET SESSION sql_mode = ' . DB::getPdo()->quote($relaxed));
        try {
            $callback();
        } finally {
            DB::statement('SET SESSION sql_mode = ' . DB::getPdo()->quote($original));
        }
    }
};
