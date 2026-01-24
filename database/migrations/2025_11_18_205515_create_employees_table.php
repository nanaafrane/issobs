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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('gender')->nullable();
            $table->string('phone_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nia_number')->nullable();
            $table->string('address')->nullable();
            $table->string('marital_status')->nullable();
            $table->integer('department_id')->nullable();
            $table->integer('role_id')->nullable();
            $table->date('date_of_joining')->nullable();
            $table->string('worker_type')->nullable();
            $table->integer('field_id')->nullable();
            $table->integer('client_id')->nullable();
            $table->string('location')->nullable();
            $table->string('image')->nullable();
            $table->string('status')->nullable();
            $table->string('payment_type')->nullable();
            $table->integer('user_id');
            $table->integer('payment_infos_id')->nullable();
            $table->integer('salary_id')->nullable();
            $table->string('gurantor_name')->nullable();
            $table->string('gurantor_number')->nullable();
            $table->string('gurantor_address')->nullable();
            $table->string('gurantor_nia_number')->nullable();
            $table->string('gurantor_nia_image')->nullable();
            $table->string('relationship')->nullable();
            $table->decimal('basic_salary', 15, 2)->nullable();
            $table->decimal('allowances', 15, 2)->nullable();
            $table->string('tax_button')->nullable();
            $table->string('ssnit_button')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
