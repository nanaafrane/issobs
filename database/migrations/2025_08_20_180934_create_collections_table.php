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
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->decimal('cash_amount')->nullable();
            $table->decimal('momo_amount')->nullable();
            $table->decimal('cheque_amount')->nullable();
            $table->decimal('transfer_amount')->nullable();
            $table->string('status')->default('undeposited');
            $table->integer('expenses_id')->nullable();
            $table->decimal('expenses_amount')->nullable();
            $table->decimal('total_amount')->nullable();
            $table->integer('field_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
