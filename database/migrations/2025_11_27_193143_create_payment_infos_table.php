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
        Schema::create('payment_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->string('acc_number')->nullable();
            $table->string('branch')->nullable();
            $table->string('branch_code')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('tin_number')->nullable();
            $table->string('ssnit_number')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_infos');
    }
};
