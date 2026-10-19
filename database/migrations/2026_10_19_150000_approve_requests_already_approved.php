<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Repairs terminations that were approved but did not count in HR movements:
 *  - the request stayed "waiting" because head office approved the employee another way
 *    (saving the edit form, a bulk update) - approved now, dated when head office approved;
 *  - the history did not show the employee employed at the time (an older termination with
 *    no re-instatement after it, or a hire never approved) - the gap is filled, marked
 *    estimated.
 * Runs the (re-runnable) history fill-in, which does both. Check one employee with
 * `php artisan employees:movements-why <id>`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_movements')) {
            Artisan::call('employees:backfill-movements');
        }
    }

    public function down(): void
    {
    }
};
