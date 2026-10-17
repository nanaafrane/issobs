<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per money form that was saved (receipt, apply credit, invoice).
 * Each form carries a one-time code; if the same form arrives twice — the
 * internet dropped after the server saved it and the cashier pressed the
 * button again — the second arrival is recognised and shown the first result
 * instead of saving a duplicate. Rows older than 60 days are pruned nightly.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('form_submissions')) {
            return;
        }

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('submission_key', 64)->unique();
            $table->string('form', 40);                 // receipt | apply_credit | invoice ...
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('result_url', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
