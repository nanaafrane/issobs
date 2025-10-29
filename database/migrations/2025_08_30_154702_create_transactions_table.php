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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('client_id')->nullable();
            $table->integer('invoice_id')->nullable();
            $table->decimal('invoice_amount')->nullable();
            $table->integer('receipt_id')->nullable();
            $table->decimal('receipt_amount')->nullable();
            $table->decimal('balance')->nullable();
            $table->string('status')->nullable();
            $table->string('checks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
