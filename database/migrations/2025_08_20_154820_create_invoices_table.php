<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->integer('client_id')->nullable();
            $table->decimal('nhil')->nullable();
            $table->decimal('getfund')->nullable();
            $table->decimal('chrl')->nullable();
            $table->decimal('sub_amount')->nullable();
            $table->decimal('vat_amount')->nullable();
            $table->decimal('sub_total')->nullable();
            $table->decimal('total')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->date('invoice_month')->nullable();
            $table->string('status')->nullable();
            $table->integer('user_id')->nullable();
            $table->decimal('balance')->default(0.00)->nullable();
            $table->decimal('wht_amount')->nullable();
            $table->decimal('amount_received')->nullable();
            $table->decimal('vat7_value')->nullable();
            $table->decimal('vat7_amount')->nullable();
            $table->timestamps();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
