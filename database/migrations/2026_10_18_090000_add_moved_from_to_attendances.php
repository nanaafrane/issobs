<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A guard sent from their own post to another client for a shift ("moved" / relief):
 * the attendance row is at the client they worked at, and moved_from_client_id is
 * their own post, so the home sheet can show "M - moved to ..." instead of a gap
 * or an absence (which would cost the guard an absent deduction).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('attendances', 'moved_from_client_id')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->unsignedBigInteger('moved_from_client_id')->nullable()->index()->after('client_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('attendances', 'moved_from_client_id')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropIndex(['moved_from_client_id']);
                $table->dropColumn('moved_from_client_id');
            });
        }
    }
};
