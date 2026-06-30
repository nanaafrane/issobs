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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('business_name')->nullable();
            $table->string('branch')->nullable();
            $table->string('address')->nullable();
            $table->string('gps')->nullable();
            $table->string('map')->nullable();
            $table->integer('field_id')->nullable();
            $table->string('status')->nullable();
            $table->date('status_date')->nullable();
            $table->date('start_date')->nullable();
            $table->decimal('rate')->nullable();
            $table->string('guards')->nullable();
            $table->string('scope_of_work')->nullable();
            $table->string('state_institution')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('coll_status')->nullable();
            $table->date('coll_date')->nullable();
            $table->string('bran_status')->nullable();
            $table->date('bran_date')->nullable();
            $table->integer('user_id1')->nullable();
            $table->string('ho_status')->nullable();
            $table->date('ho_date')->nullable();
            $table->integer('user_id2')->nullable();
            $table->integer('category_id')->nullable();
            $table->string('category_name')->nullable();
            $table->date('category_month')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
