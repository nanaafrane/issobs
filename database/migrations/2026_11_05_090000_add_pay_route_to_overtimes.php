<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Overtime: what kind it is and how it is paid (App\Support\OvertimePay).
 *
 *  overtimes.type            off_day | replacement | shortage | other
 *  overtimes.pay_by          what the officer asked: cash (a cash / MoMo pay-out) | salary (a payroll pay-out)
 *  overtimes.pay_note        why a shortage is paid in cash (an exception)
 *  overtimes.checks          warnings worked out on save (no weekly off day, no own shift, ...)
 *  salaries.overtime_from_entries   the part of salaries.overtime that came from overtime entries
 *  overtime_settings         the default amount per shift (history: one row per change)
 *
 * Existing entries: pay_by = salary when they are on a payroll pay-out, cash otherwise;
 * type from their reason.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->withoutStrictDates(function () {
            if (! Schema::hasColumn('overtimes', 'pay_by')) {
                Schema::table('overtimes', function (Blueprint $table) {
                    $table->string('type', 20)->nullable()->after('reason');
                    $table->string('pay_by', 10)->default('cash')->after('amount');
                    $table->string('pay_note')->nullable()->after('pay_by');
                    $table->json('checks')->nullable()->after('pay_note');
                });
            }
            if (! Schema::hasColumn('salaries', 'overtime_from_entries')) {
                Schema::table('salaries', function (Blueprint $table) {
                    $table->decimal('overtime_from_entries', 15, 2)->nullable()->after('overtime');
                });
            }
        });

        if (! Schema::hasTable('overtime_settings')) {
            Schema::create('overtime_settings', function (Blueprint $table) {
                $table->id();
                $table->decimal('default_amount', 10, 2)->default(30);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
        }

        // Type of the entries already there, from their reason.
        DB::table('overtimes')->whereNull('type')->whereNotNull('absent_employee_id')->update(['type' => 'replacement']);
        DB::table('overtimes')->whereNull('type')->whereNull('absent_employee_id')->where('reason', 'Shortage')->update(['type' => 'shortage']);
        DB::table('overtimes')->whereNull('type')->update(['type' => 'other']);
        if (Schema::hasTable('overtime_payouts') && Schema::hasColumn('overtimes', 'payout_id')) {
            DB::table('overtimes')->whereIn('payout_id', DB::table('overtime_payouts')->where('method', 'payroll')->select('id'))->update(['pay_by' => 'salary']);
        }
    }

    /** Old tables may hold 0000-00-00 dates: relax only the date checks around the ALTERs. */
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

    public function down(): void
    {
        Schema::dropIfExists('overtime_settings');
        if (Schema::hasColumn('salaries', 'overtime_from_entries')) {
            Schema::table('salaries', fn (Blueprint $t) => $t->dropColumn('overtime_from_entries'));
        }
        if (Schema::hasColumn('overtimes', 'pay_by')) {
            Schema::table('overtimes', fn (Blueprint $t) => $t->dropColumn(['type', 'pay_by', 'pay_note', 'checks']));
        }
    }
};
