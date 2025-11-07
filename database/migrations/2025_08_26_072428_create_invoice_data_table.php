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
        Schema::create('invoice_data', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_number')->nullable();
            $table->string('service_name')->nullable();
            $table->string('description')->nullable();
            $table->decimal('quantity')->nullable();
            $table->decimal('unit_price')->nullable();
            $table->decimal('amount')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_data');
    }
};
