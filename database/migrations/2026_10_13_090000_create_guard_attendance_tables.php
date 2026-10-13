<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
 */
return new class extends Migration
{
    public function up(): void
    {
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

        Schema::table('clients', function (Blueprint $table) {
            $table->string('attendance_token', 40)->nullable()->unique();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('attendance_radius_m')->default(200);
        });

        Schema::table('client_employee', function (Blueprint $table) {
            $table->string('shift', 10)->nullable();    // day | night | rotate
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('attendance_pin')->nullable();
            $table->string('attendance_device', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['attendance_pin', 'attendance_device']);
        });
        Schema::table('client_employee', function (Blueprint $table) {
            $table->dropColumn('shift');
        });
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique(['attendance_token']);
            $table->dropColumn(['attendance_token', 'latitude', 'longitude', 'attendance_radius_m']);
        });
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('client_shift_plans');
    }
};
