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
        Schema::create('nrrit', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id')->nullable();
            $table->string('name')->nullable();
            $table->date('status_month')->nullable();
            $table->string('status')->nullable();
            $table->string('status1')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nrrit');
    }
};
