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
        Schema::create('salaries_addups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_id')->nullable();
            $table->date('salary_month')->nullable();
            $table->integer('field_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('payment_type')->nullable();
            $table->decimal('top_up_amount', 10, 2)->nullable();
            $table->string('reason')->nullable();
            $table->string('status')->nullable();
            $table->date('status_date')->nullable();
            $table->integer('user_id1')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries_addups');
    }
};
