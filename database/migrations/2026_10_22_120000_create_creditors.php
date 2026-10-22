<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creditors: people or institutions that lend the company money (a director's loan,
 * a shareholder, a lender) — a liability, paid back later.
 *
 *  creditors          who, type, opening balance owed, and the running balance owed.
 *  creditor_entries   every movement: money received into a company account, paid on the
 *                     company's behalf (no company account moves), repaid, interest,
 *                     corrections. Never deleted: a mistake is reversed.
 *  bank_transactions  creditor_entry_id: the line a receipt / repayment posted.
 *  expenses           creditor_entry_id: an approved expense a creditor paid for the company.
 * Safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('creditors')) {
            Schema::create('creditors', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150)->unique();
                $table->string('kind', 12)->default('director');      // director | shareholder | lender | other
                $table->string('phone', 40)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('notes', 500)->nullable();
                $table->decimal('opening_balance', 15, 2)->default(0); // owed when set up
                $table->date('opening_date')->nullable();
                $table->decimal('balance', 15, 2)->default(0);         // owed now = opening + entries
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('creditor_entries')) {
            Schema::create('creditor_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('creditor_id')->index();
                $table->date('entry_date');
                $table->string('kind', 16);                 // received | on_behalf | repaid | reversal
                $table->decimal('owed_change', 15, 2);      // + company owes more, − owes less
                $table->unsignedBigInteger('bank_id')->nullable();   // company account the money came into / left
                $table->string('method', 12)->nullable();   // transfer | cheque | cash | momo
                $table->string('reference', 60)->nullable();
                $table->string('description', 500)->nullable();
                $table->decimal('interest', 15, 2)->nullable();      // repayments: interest paid on top (an expense)
                $table->unsignedBigInteger('interest_payment_id')->nullable();
                $table->unsignedBigInteger('reverses_id')->nullable()->index();
                $table->timestamp('reversed_at')->nullable();
                $table->unsignedBigInteger('reversed_by')->nullable();
                $table->string('reversal_reason', 500)->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
                $table->index(['creditor_id', 'entry_date']);
            });
        }

        $this->withZeroDatesAllowed(function () {
            Schema::table('bank_transactions', function (Blueprint $table) {
                if (! Schema::hasColumn('bank_transactions', 'creditor_entry_id')) {
                    $table->unsignedBigInteger('creditor_entry_id')->nullable()->after('payment_id')->index();
                }
            });
            Schema::table('expenses', function (Blueprint $table) {
                if (! Schema::hasColumn('expenses', 'creditor_entry_id')) {
                    $table->unsignedBigInteger('creditor_entry_id')->nullable()->index();
                }
            });
        });
    }

    public function down(): void
    {
        $this->withZeroDatesAllowed(function () {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropIndex(['creditor_entry_id']);
                $table->dropColumn('creditor_entry_id');
            });
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->dropIndex(['creditor_entry_id']);
                $table->dropColumn('creditor_entry_id');
            });
        });
        Schema::dropIfExists('creditor_entries');
        Schema::dropIfExists('creditors');
    }

    private function withZeroDatesAllowed(callable $change): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $change();

            return;
        }

        $mode = DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;
        DB::statement('SET SESSION sql_mode = ?', [implode(',', array_diff(explode(',', $mode), ['NO_ZERO_DATE', 'NO_ZERO_IN_DATE']))]);
        try {
            $change();
        } finally {
            DB::statement('SET SESSION sql_mode = ?', [$mode]);
        }
    }
};
