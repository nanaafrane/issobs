<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Salary payment runs ("first payment", "second payment", ... of a payroll month).
 *
 *  payment_runs     one numbered, dated payment of a payroll month, opened and closed by
 *                   a Finance Manager, with the funds released for it (optional).
 *  salary_payments  what was actually paid, in which run: one row per approved salary or
 *                   top-up, plus "difference" rows when a net salary went up after it was
 *                   paid. Amounts are fixed at the moment of payment; what is OWED always
 *                   comes from the salary's current net, so a net changed after payment
 *                   shows as a difference instead of silently rewriting history.
 *
 * New tables only: existing tables are not altered. Salaries approved before this
 * migration have no payment rows; the report shows them as "Before payment runs".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_runs')) {
            Schema::create('payment_runs', function (Blueprint $table) {
                $table->id();
                $table->date('salary_month');                        // payroll month (1st)
                $table->unsignedSmallInteger('number');              // 1, 2, 3 ... within the month
                $table->date('run_date');
                $table->string('status', 10)->default('open');       // open | closed
                $table->decimal('funds_released', 15, 2)->nullable();
                $table->string('reference', 120)->nullable();         // bank / transfer reference
                $table->string('notes', 255)->nullable();
                $table->boolean('auto_opened')->default(false);       // opened by an approval, not by hand
                $table->unsignedBigInteger('opened_by')->nullable();
                $table->unsignedBigInteger('closed_by')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
                $table->unique(['salary_month', 'number']);
                $table->index(['salary_month', 'status']);
            });
        }

        if (! Schema::hasTable('salary_payments')) {
            Schema::create('salary_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payment_run_id')->index();
                $table->date('salary_month')->index();
                $table->unsignedBigInteger('salary_id')->nullable()->index();
                $table->unsignedBigInteger('salary_top_up_id')->nullable()->index();
                $table->string('kind', 12);                          // salary | topup | difference
                $table->string('source', 12);                        // approve | momo | topup | difference
                $table->decimal('amount', 15, 2);
                $table->decimal('net_at_payment', 15, 2)->nullable(); // the salary's net when paid
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
        Schema::dropIfExists('payment_runs');
    }
};
