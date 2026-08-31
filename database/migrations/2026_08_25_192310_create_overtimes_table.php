<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('overtimes', function (Blueprint $table) {
            $table->id();

            $table->date('entry_date');
            $table->enum('shift', ['day', 'night']);

            $table->unsignedBigInteger('field_id')->nullable();

            // Absent / covered guard - often not a real employee (e.g. "SHORTAGE")
            $table->unsignedBigInteger('absent_employee_id')->nullable();
            $table->string('absent_employee_note')->nullable();

            // Client / guard post
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('client_site_note')->nullable();

            // Guard who performed the overtime shift (required)
            $table->unsignedBigInteger('ot_employee_id');

            // Supervisor on duty who authorised / confirms the entry
            $table->unsignedBigInteger('officer_id')->nullable();

            $table->string('reason')->nullable(); // Shortage, Absent, Sick, Terminated, Other...
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('phone_number')->nullable();
            $table->text('notes')->nullable();

            // 3-stage approval chain, same shape as Expense
            $table->unsignedBigInteger('user_1')->nullable(); // creator
            $table->string('status_1')->nullable();
            $table->dateTime('date_1')->nullable();

            $table->unsignedBigInteger('user_2')->nullable(); // branch/field manager
            $table->string('status_2')->nullable();
            $table->dateTime('date_2')->nullable();

            $table->unsignedBigInteger('user_3')->nullable(); // finance/HO
            $table->string('status_3')->nullable();
            $table->dateTime('date_3')->nullable();

            // Link to the mirrored Expense row (set by OvertimeObserver)
            $table->unsignedBigInteger('expense_id')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['entry_date', 'shift', 'field_id']);
            $table->index(['client_id']);
            $table->index(['ot_employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtimes');
    }
};
