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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('description')->nullable();
            $table->decimal('amount')->nullable();
            $table->integer('user_1')->nullable();
            $table->string('status_1')->nullable();
            $table->dateTime('date_1')->nullable();
            $table->integer('user_2')->nullable();
            $table->string('status_2')->nullable();
            $table->dateTime('date_2')->nullable();
            $table->integer('user_3')->nullable();
            $table->string('status_3')->nullable();
            $table->dateTime('date_3')->nullable();
            $table->string('image', 512)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
