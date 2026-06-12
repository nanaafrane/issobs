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
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->date('salary_month')->nullable();
            $table->integer('employee_id')->nullable();
            $table->integer('department_id')->nullable();
            $table->integer('field_id')->nullable();
            $table->integer('role_id')->nullable();
            $table->integer('client_id')->nullable();
            $table->string('location')->nullable();
            $table->string('status1')->nullable();
            $table->string('status2')->nullable();
            $table->integer('hubtel_id')->nullable();
            $table->string('account_number')->nullable();
            $table->integer('bank_id')->nullable();
            $table->string('branch')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('hold_reason')->nullable();
            $table->decimal('basic_salary', 15, 2)->nullable();
            $table->decimal('allowances', 15, 2)->nullable();
            $table->decimal('airtime_allowance')->nullable();
            $table->decimal('overtime', 15, 2)->nullable();
            $table->decimal('reimbursements', 15, 2)->nullable();
            $table->decimal('transport_allowance', 15, 2)->nullable();
            $table->decimal('ssnit_tier2_5d', 15, 2)->nullable();
            $table->decimal('ssnit_tier2_5', 15, 2)->nullable();
            $table->decimal('tax', 15, 2)->nullable();
            $table->decimal('ssnit_tier1_0_5', 15, 2)->nullable();
            $table->decimal('welfare', 15, 2)->nullable();
            $table->decimal('maintenance', 15, 2)->nullable();
            $table->decimal('absent', 15, 2)->nullable();
            $table->decimal('boot', 15, 2)->nullable();
            $table->decimal('iou', 15, 2)->nullable();
            $table->decimal('hostel', 15, 2)->nullable();
            $table->decimal('insurance', 15, 2)->nullable();
            $table->decimal('reprimand', 15, 2)->nullable();
            $table->decimal('scouter', 15, 2)->nullable();
            $table->decimal('raincoat', 15, 2)->nullable();
            $table->decimal('meal', 15, 2)->nullable();
            $table->decimal('loan', 15, 2)->nullable();
            $table->decimal('walkin', 15, 2)->nullable();
            $table->decimal('amnt_ded_cof_start_date', 15, 2)->nullable();
            $table->decimal('other_deductions', 15, 2)->nullable();
            $table->decimal('gross_salary', 15, 2)->nullable();
            $table->decimal('total_deductions', 15, 2)->nullable();
            $table->decimal('net_salary', 15, 2)->nullable();
            $table->decimal('ssnit_comp_cont_13', 15, 2)->nullable();
            $table->decimal('ssnit_tobe_paid13_5', 15, 2)->nullable();
            $table->decimal('cost_to_company', 15, 2)->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('user_id1')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
