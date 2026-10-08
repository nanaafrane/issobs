<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pay priority rules page (Payroll -> Pay priority rules):
 *
 *  pay_priority_aliases  spelling variants of free-text locations ("C/Coast" -> "CAPE COAST"),
 *                        editable on the page. Pre-filled from PayPriority::ALIASES, which
 *                        stays in code only as the fallback before this migration runs.
 *  pay_priority_changes  who changed which rule, when, before / after, and how many
 *                        employees it moved.
 *
 * New tables only: no existing table is altered, so the zero-date issue on
 * employees / salaries cannot occur. Safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pay_priority_aliases')) {
            Schema::create('pay_priority_aliases', function (Blueprint $table) {
                $table->id();
                $table->string('from', 60)->unique();   // normalised spelling found in data
                $table->string('to', 60);               // spelling used in rules
                $table->unsignedInteger('position')->default(0); // applied in this order
                $table->timestamps();
            });

            $now = now();
            $position = 0;
            DB::table('pay_priority_aliases')->insert(array_map(function ($from, $to) use (&$position, $now) {
                return ['from' => $from, 'to' => $to, 'position' => ++$position, 'created_at' => $now, 'updated_at' => $now];
            }, array_keys(\App\Support\PayPriority::ALIASES), array_values(\App\Support\PayPriority::ALIASES)));
        }

        if (! Schema::hasTable('pay_priority_changes')) {
            Schema::create('pay_priority_changes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action', 30);                  // created, updated, enabled, disabled, deleted, alias_added, alias_removed, recomputed
                $table->unsignedBigInteger('rule_id')->nullable();
                $table->unsignedBigInteger('client_id')->nullable();
                $table->string('summary', 255);
                $table->text('before')->nullable();            // JSON
                $table->text('after')->nullable();             // JSON
                $table->unsignedInteger('employees_changed')->nullable();
                $table->timestamp('created_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pay_priority_changes');
        Schema::dropIfExists('pay_priority_aliases');
    }
};
