<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One salary upload (salariesCreate): the file, what the check found, and - once
 * applied - every salary's amounts before and after, so it can be undone.
 * New table only.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('salary_uploads')) {
            return;
        }
        Schema::create('salary_uploads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();            // who uploaded (checked) it
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('status', 10)->default('checked');          // checked | applied | undone
            $table->string('months', 120)->nullable();                 // "October 2026"
            $table->json('columns')->nullable();                       // amount columns in the file
            $table->json('applied_columns')->nullable();               // the ones ticked when applied
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('ready_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->unsignedInteger('warning_rows')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->decimal('net_before', 15, 2)->nullable();
            $table->decimal('net_after', 15, 2)->nullable();
            $table->json('changes')->nullable();                       // salary id => before / after
            $table->unsignedBigInteger('applied_by')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->unsignedBigInteger('undone_by')->nullable();
            $table->timestamp('undone_at')->nullable();
            $table->json('undo_kept')->nullable();                     // rows undo left alone, and why
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_uploads');
    }
};
