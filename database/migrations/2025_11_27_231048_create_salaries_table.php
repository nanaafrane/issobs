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
            $table->string('salary_month')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('field_id')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('location')->nullable();
            $table->string('status1')->nullable();
            $table->string('status2')->nullable();
            $table->string('account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('branch')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('payment_status')->default('pending');
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('allowances', 15, 2)->default(0);
            $table->decimal('overtime', 15, 2)->default(0);
            $table->decimal('reimbursements', 15, 2)->default(0 );
            $table->decimal('transport_allowance', 15, 2)->default(0);
            $table->decimal('ssnit_tier2_5', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('ssnit_tier1_0_5', 15, 2)->default(0);
            $table->decimal('welfare', 15, 2)->default(0);
            $table->decimal('maintenance', 15, 2)->default(0);
            $table->decimal('absent', 15, 2)->default(0);
            $table->decimal('boot', 15, 2)->default(0);
            $table->decimal('iou', 15, 2)->default(0);
            $table->decimal('hostel', 15, 2)->default(0);
            $table->decimal('insurance', 15, 2)->default(0);
            $table->decimal('reprimand', 15, 2)->default(0);
            $table->decimal('scouter', 15, 2)->default(0);
            $table->decimal('raincoat', 15, 2)->default(0);
            $table->decimal('meal', 15, 2)->default(0);
            $table->decimal('loan', 15, 2)->default(0);
            $table->decimal('walkin', 15, 2)->default(0);
            $table->decimal('amnt_ded_cof_start_date', 15, 2)->default(0);
            $table->decimal('other_deductions', 15, 2)->default(0);
            $table->decimal('gross_salary', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->decimal('ssnit_comp_cont_13', 15, 2)->default(0);
            $table->decimal('ssnit_tobe_paid13_5', 15, 2)->default(0);
            $table->decimal('cost_to_company', 15, 2)->default(0);
            $table->unsignedBigInteger('user_id');
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
