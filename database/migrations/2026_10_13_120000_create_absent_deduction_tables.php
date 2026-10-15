<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Absent deduction from guard attendance.
 *
 *  absent_deduction_settings  How a day is priced (one row per change; the newest row is in force).
 *  absent_deduction_changes   Every time salaries.absent was set from attendance: days, rate, old and new amount.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('absent_deduction_settings')) Schema::create('absent_deduction_settings', function (Blueprint $table) {
            $table->id();
            $table->string('basis', 20)->default('basic');          // basic | basic_allowances
            $table->string('divisor', 20)->default('month_days');   // month_days | 30 | 26
            $table->boolean('auto_on_create')->default(true);       // fill when a month's salaries are added
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        if (! Schema::hasTable('absent_deduction_changes')) Schema::create('absent_deduction_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_id')->index();
            $table->foreignId('employee_id');
            $table->date('salary_month');
            $table->unsignedInteger('absent_days');
            $table->decimal('daily_rate', 15, 2);
            $table->decimal('old_amount', 15, 2)->nullable();
            $table->decimal('new_amount', 15, 2);
            $table->string('basis', 20);
            $table->string('divisor', 20);
            $table->string('trigger', 20);                           // salaries_added | applied
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absent_deduction_changes');
        Schema::dropIfExists('absent_deduction_settings');
    }
};
