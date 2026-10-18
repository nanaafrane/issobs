<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * employee_movements: one row per hire, termination, re-instatement or office transfer.
 *
 * Replaces `nrrit` as the source of the HR movements report. `nrrit` is left as it is
 * (nothing reads or writes it any more); its rows are copied in by the backfill.
 *
 *  - effective_date   the day the movement takes effect (for a termination: the last day
 *                     employed). Null only for old records whose date is unknown.
 *  - date_estimated   the date was guessed by the backfill (month only, or creation date).
 *  - status           pending (waiting for head office) | approved | declined | cancelled.
 *                     Only approved movements count in any report.
 *  - field/client/role/department_id  where the employee was AT THE TIME, so a later
 *                     transfer never moves past movements to another office.
 *  - previous_status  the employee's status before a termination / re-instatement request,
 *                     put back if head office declines it.
 *
 * New table only. Safe to re-run: the table is created once and the backfill skips
 * employees / nrrit rows already copied.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_movements')) {
            Schema::create('employee_movements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id')->index();
                $table->string('type', 15);                          // hire | termination | reinstatement | transfer
                $table->string('status', 10)->default('approved');   // pending | approved | declined | cancelled
                $table->date('effective_date')->nullable();
                $table->boolean('date_estimated')->default(false);
                $table->string('reason', 30)->nullable();            // terminations: see EmployeeMovements::REASONS
                $table->string('note', 255)->nullable();
                $table->string('previous_status', 30)->nullable();
                $table->unsignedBigInteger('field_id')->nullable();
                $table->unsignedBigInteger('from_field_id')->nullable(); // transfers
                $table->unsignedBigInteger('client_id')->nullable();
                $table->unsignedBigInteger('role_id')->nullable();
                $table->unsignedBigInteger('department_id')->nullable();
                $table->string('source', 20)->nullable();            // form | bulk_upload | approval | terminate | reinstate | edit | backfill | nrrit
                $table->unsignedBigInteger('legacy_nrrit_id')->nullable()->unique();
                $table->unsignedBigInteger('requested_by')->nullable();
                $table->timestamp('requested_at')->nullable();
                $table->unsignedBigInteger('decided_by')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();

                $table->index(['type', 'status', 'effective_date']);
                $table->index(['employee_id', 'type', 'status']);
            });
        }

        // Copy history in: a hire for every employee, nrrit terminations / re-instatements,
        // and a termination for anyone Terminated without one.
        Artisan::call('employees:backfill-movements');
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_movements');
    }
};
