<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The first employee_movements backfill dated the hire of an employee with no date of
 * joining by the day their record was created. For records keyed in (or bulk uploaded)
 * after the person joined, every termination dated before that day was not counted.
 *
 * Those guessed hire dates are removed: such employees count as employed from before any
 * period (their terminations count), and the report asks HR for the real date.
 * Only hires the backfill guessed are touched; nothing entered by a person changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_movements')) {
            return;
        }
        DB::table('employee_movements')->where('type', 'hire')->where('source', 'backfill')->where('date_estimated', 1)
            ->whereNotNull('effective_date')->update(['effective_date' => null, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Nothing to restore: the removed dates were guesses.
    }
};
