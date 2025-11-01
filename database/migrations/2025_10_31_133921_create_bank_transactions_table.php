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
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('bank_id')->nullable();
            $table->decimal('credit')->nullable();
            $table->integer('deposit_id')->nullable();
            $table->integer('receipt_id')->nullable();
            $table->decimal('debit')->nullable();
            $table->integer('expense_id')->nullable();
            $table->decimal('balance')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
