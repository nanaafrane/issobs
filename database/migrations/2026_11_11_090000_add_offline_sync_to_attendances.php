<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Offline sign-in for guard attendance (docs/offline-sync.md).
 *
 * attendances +           captured_offline / synced_at: the sign-in was made with no signal and
 *                         uploaded later (checked_in_at stays the time it was made on the phone).
 * attendance_sync_items   one row per uploaded item, by the id the phone gave it, so an item sent
 *                         twice (connection dropped after the server saved it) is recorded once and
 *                         the phone gets the same answer again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'captured_offline')) {
                $table->boolean('captured_offline')->default(false);
            }
            if (! Schema::hasColumn('attendances', 'synced_at')) {
                $table->dateTime('synced_at')->nullable();
            }
        });

        if (! Schema::hasTable('attendance_sync_items')) {
            Schema::create('attendance_sync_items', function (Blueprint $table) {
                $table->id();
                $table->string('uuid', 64)->unique();
                $table->string('kind', 10);                       // scan | kiosk
                $table->unsignedBigInteger('client_id')->nullable()->index();
                $table->unsignedBigInteger('employee_id')->nullable();
                $table->unsignedBigInteger('attendance_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable(); // supervisor, for kiosk marks
                $table->string('result', 12);                     // accepted | held | rejected | skipped
                $table->text('message')->nullable();
                $table->dateTime('captured_at')->nullable();
                $table->integer('clock_skew_s')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sync_items');
        Schema::table('attendances', function (Blueprint $table) {
            foreach (['captured_offline', 'synced_at'] as $column) {
                if (Schema::hasColumn('attendances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
