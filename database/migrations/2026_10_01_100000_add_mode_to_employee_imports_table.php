<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Bulk uploads log both kinds of upload: new employees ("create") and changes ("update", with an audit of every change). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_imports', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_imports', 'mode')) {
                $table->string('mode')->default('create')->after('user_id');
            }
            if (! Schema::hasColumn('employee_imports', 'changes')) {
                $table->json('changes')->nullable()->after('created_ids'); // employee id => [field => [old, new]]
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_imports', function (Blueprint $table) {
            $table->dropColumn(['mode', 'changes']);
        });
    }
};
