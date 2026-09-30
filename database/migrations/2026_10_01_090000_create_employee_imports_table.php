<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log of bulk employee uploads: who uploaded which file, what was checked and what was created.
 * (A new table only - the employees table is not altered.)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_imports')) {
            return;
        }
        Schema::create('employee_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('status')->default('previewed'); // previewed | completed
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->json('created_ids')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_imports');
    }
};
