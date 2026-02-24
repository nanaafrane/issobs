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
        Schema::create('hubtel', function (Blueprint $table) {
            $table->id();
            $table->string('responseCode')->nullable();
            $table->string('ClientReference')->nullable();
            $table->string('TransactionId')->nullable();
            $table->decimal('Amount')->nullable();
            $table->decimal('fees')->nullable();
            $table->string('Channel')->nullable();
            $table->string('customerNumber')->nullable();
            $table->string('Description')->nullable();
            $table->string('transactionStatus')->nullable();
            $table->string('CreatedAt')->nullable();
            $table->integer('Salary_id')->nullable();
            $table->integer('staff_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hubtel');
    }
};
