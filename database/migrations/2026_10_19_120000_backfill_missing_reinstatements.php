<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Staff who are Active again but whose history ends in a termination (taken back on before
 * re-instatement had its own step) get the missing re-instatement, so a new termination of
 * theirs counts in the HR movements report. Adds only what is missing; safe to re-run.
 *
 * Also clears again any hire date the history fill-in only guessed (an earlier version
 * could give an unknown joining date the termination's date, so "worked for" showed 0 days
 * or thousands of years).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_movements')) {
            DB::table('employee_movements')->where('type', 'hire')->where('source', 'backfill')->where('date_estimated', 1)
                ->whereNotNull('effective_date')->update(['effective_date' => null, 'updated_at' => now()]);
            Artisan::call('employees:backfill-movements');
        }
    }

    public function down(): void
    {
        // Added rows are marked source "backfill" with a note; nothing to undo automatically.
    }
};
