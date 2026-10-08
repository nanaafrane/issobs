<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cheques and transfers now go straight into the bank chosen on the receipt,
 * so every bank ledger line records how the money arrived:
 *   cheque | transfer   posted by a receipt (receipt_id set)
 *   deposit             cash (and old, never-banked cheques) via Bank Deposit
 *   expense             money paid out
 *
 * The bank tables' money columns are widened from decimal(8,2) — which tops
 * out at 999,999.99 — to decimal(15,2): a bank running total passes that
 * quickly once cheques and transfers post directly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->string('channel', 20)->nullable()->after('receipt_id');
            $table->index(['receipt_id', 'channel']);
        });

        DB::table('bank_transactions')->whereNotNull('deposit_id')->update(['channel' => 'deposit']);
        DB::table('bank_transactions')->whereNull('channel')->whereNotNull('expense_id')->update(['channel' => 'expense']);
        // Receipt postings written before this migration were transfers (cheques went through deposits).
        DB::table('bank_transactions')->whereNull('channel')->whereNotNull('receipt_id')->update(['channel' => 'transfer']);

        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->decimal('credit', 15, 2)->nullable()->change();
            $table->decimal('debit', 15, 2)->nullable()->change();
            $table->decimal('balance', 15, 2)->nullable()->change();
        });
        Schema::table('banks', function (Blueprint $table) {
            $table->decimal('total', 15, 2)->nullable()->change();
        });
        Schema::table('bank_deposits', function (Blueprint $table) {
            $table->decimal('cash_amount', 15, 2)->nullable()->change();
            $table->decimal('cheque_amount', 15, 2)->nullable()->change();
            $table->decimal('total', 15, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropIndex(['receipt_id', 'channel']);
            $table->dropColumn('channel');
        });
        // Money columns are left at decimal(15,2): narrowing could truncate balances.
    }
};
