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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->integer('invoice_id')->nullable();
            $table->integer('client_id')->nullable();
            $table->string('from')->nullable();
            $table->string('mode')->nullable();
            $table->decimal('dAmount')->nullable();
            $table->date('receipt_month')->nullable();
            $table->string('description')->nullable();
            $table->string('cheque_reference')->nullable();
            $table->decimal('cheque_amount')->nullable();
            $table->string('cheque_bank')->nullable();
            $table->string('transfer_reference')->nullable();
            $table->string('transfer_bank')->nullable();
            $table->decimal('transfer_amount')->nullable();
            $table->string('momo_transactin_id')->nullable();
            $table->decimal('momo_amount')->nullable();
            $table->decimal('cash_amount')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('status')->nullable();
            $table->decimal('total')->nullable();
            $table->decimal('wht_amount')->nullable();
            $table->decimal('amount_received')->nullable();
            $table->decimal('vat7_value')->nullable();
            $table->decimal('vat7_amount')->nullable();
            $table->string('image', 512)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
