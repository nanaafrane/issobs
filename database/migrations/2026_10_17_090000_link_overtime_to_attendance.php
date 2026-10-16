<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Overtime <-> attendance.
 *
 *  attendances.reason       the shift reason, from the same list overtime uses (Absent, Sick, Off Duty, Pass, ...)
 *  attendances.overtime_id  set while an overtime entry owns this attendance row
 *  overtime_attendance_links  what each overtime entry wrote to attendance, and what was there before,
 *                             so deleting or rejecting the overtime puts the sheet back exactly as it was.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('reason', 40)->nullable()->after('status');
            $table->unsignedBigInteger('overtime_id')->nullable()->index()->after('source');
        });

        Schema::create('overtime_attendance_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('overtime_id')->index();
            $table->string('role', 10);                 // absent (guard named on the entry) | cover (O.T guard)
            $table->foreignId('employee_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->date('work_date');
            $table->string('shift', 10);
            $table->unsignedBigInteger('attendance_id')->nullable();
            $table->string('state', 10);                // linked | matched | conflict | skipped
            $table->text('previous')->nullable();       // JSON of the attendance row it replaced (null = it created the row)
            $table->string('message')->nullable();
            $table->timestamps();
            $table->unique(['overtime_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_attendance_links');
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['overtime_id']);
            $table->dropColumn(['reason', 'overtime_id']);
        });
    }
};
