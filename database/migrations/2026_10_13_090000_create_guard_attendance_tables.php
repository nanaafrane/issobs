<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Guard attendance (replaces the monthly Excel time sheet).
 *
 *  client_shift_plans  How many guards a client needs on the day shift and on the night shift,
 *                      and the shift times. One row per change, effective from a month, so
 *                      editing the plan never rewrites earlier months' shortages.
 *  attendances         One row per guard, per date, per shift (day/night). A night shift that
 *                      runs past midnight belongs to the date it started on.
 *  clients             attendance_token (secret in the QR poster), site coordinates + radius.
 *  client_employee     shift each guard works at that client (day / night / rotate).
 *  employees           attendance PIN (hashed) and the phone the guard scans with.
 *
 * Safe to re-run: every step checks first, so a run that stopped half-way (MySQL cannot
 * roll back DDL, and MyISAM has no transactions) finishes the remaining steps instead of
 * failing on "table already exists". Altering the old tables rebuilds them, which
 * re-checks legacy '0000-00-00' dates under strict mode (error 1292); those statements run
 * with the date checks relaxed for the session only. No data is modified.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_shift_plans')) {
        Schema::create('client_shift_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->index();
            $table->date('effective_month');            // always the 1st of a month
            $table->unsignedInteger('day_guards')->default(0);
            $table->unsignedInteger('night_guards')->default(0);
            $table->string('day_start', 5)->default('06:00');
            $table->string('day_end', 5)->default('18:00');
            $table->string('night_start', 5)->default('18:00');
            $table->string('night_end', 5)->default('06:00');
            $table->string('note')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            $table->unique(['client_id', 'effective_month']);
        });
        }

        if (! Schema::hasTable('attendances')) {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->index();
            $table->foreignId('employee_id');
            $table->date('work_date');
            $table->string('shift', 10);                // day | night
            $table->string('status', 10);               // present | absent | off | sick | pass | pending
            $table->string('source', 10);               // scan | manual
            $table->dateTime('checked_in_at')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('accuracy_m')->nullable();
            $table->unsignedInteger('distance_m')->nullable();
            $table->string('device_id', 64)->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('flags')->nullable();          // JSON list of reasons a scan was held for review
            $table->string('note')->nullable();
            $table->unsignedBigInteger('marked_by')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'work_date', 'shift']);
            $table->index(['client_id', 'work_date']);
            $table->index('status');
        });
        }

        $this->withoutStrictDates(function () {
            if (! Schema::hasColumn('clients', 'attendance_token')) {
                Schema::table('clients', function (Blueprint $table) {
                    $table->string('attendance_token', 40)->nullable()->unique();
                });
            }
            foreach (['latitude' => fn (Blueprint $t) => $t->decimal('latitude', 10, 7)->nullable(),
                      'longitude' => fn (Blueprint $t) => $t->decimal('longitude', 10, 7)->nullable(),
                      'attendance_radius_m' => fn (Blueprint $t) => $t->unsignedInteger('attendance_radius_m')->default(200)] as $column => $add) {
                if (! Schema::hasColumn('clients', $column)) {
                    Schema::table('clients', $add);
                }
            }

            if (! Schema::hasColumn('client_employee', 'shift')) {
                Schema::table('client_employee', function (Blueprint $table) {
                    $table->string('shift', 10)->nullable();    // day | night | rotate
                });
            }

            if (! Schema::hasColumn('employees', 'attendance_pin')) {
                Schema::table('employees', function (Blueprint $table) {
                    $table->string('attendance_pin')->nullable();
                });
            }
            if (! Schema::hasColumn('employees', 'attendance_device')) {
                Schema::table('employees', function (Blueprint $table) {
                    $table->string('attendance_device', 64)->nullable();
                });
            }
        });
    }

    /** Run DDL on legacy tables with the zero-date checks off for this session only. */
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
        $this->withoutStrictDates(function () {
            foreach (['attendance_pin', 'attendance_device'] as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    Schema::table('employees', fn (Blueprint $t) => $t->dropColumn($column));
                }
            }
            if (Schema::hasColumn('client_employee', 'shift')) {
                Schema::table('client_employee', fn (Blueprint $t) => $t->dropColumn('shift'));
            }
            if (Schema::hasColumn('clients', 'attendance_token')) {
                Schema::table('clients', function (Blueprint $t) {
                    $t->dropUnique(['attendance_token']);
                    $t->dropColumn('attendance_token');
                });
            }
            foreach (['latitude', 'longitude', 'attendance_radius_m'] as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    Schema::table('clients', fn (Blueprint $t) => $t->dropColumn($column));
                }
            }
        });
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('client_shift_plans');
    }
};
