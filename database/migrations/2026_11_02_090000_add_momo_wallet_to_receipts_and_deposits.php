<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MoMo received on receipts goes straight into the company MoMo wallet:
 *  receipts.momo_to_bank_id         the wallet (banks.kind = momo) the client paid into.
 *  bank_deposits.wallet_id          the wallet MoMo was withdrawn from for this slip.
 *  bank_deposits.wallet_amount      how much of the slip came out of that wallet.
 * Old receipts keep momo_to_bank_id empty and go through Bank Deposit as before.
 * Safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('receipts', 'momo_to_bank_id')) {
            $this->withZeroDatesAllowed(fn () => Schema::table('receipts', function (Blueprint $table) {
                $table->unsignedBigInteger('momo_to_bank_id')->nullable()->after('momo_amount')->index();
            }));
        }
        if (! Schema::hasColumn('bank_deposits', 'wallet_id')) {
            $this->withZeroDatesAllowed(fn () => Schema::table('bank_deposits', function (Blueprint $table) {
                $table->unsignedBigInteger('wallet_id')->nullable()->after('momo_charge')->index();
                $table->decimal('wallet_amount', 15, 2)->nullable()->after('wallet_id');
            }));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bank_deposits', 'wallet_id')) {
            $this->withZeroDatesAllowed(fn () => Schema::table('bank_deposits', function (Blueprint $table) {
                $table->dropIndex(['wallet_id']);
                $table->dropColumn(['wallet_id', 'wallet_amount']);
            }));
        }
        if (Schema::hasColumn('receipts', 'momo_to_bank_id')) {
            $this->withZeroDatesAllowed(fn () => Schema::table('receipts', function (Blueprint $table) {
                $table->dropIndex(['momo_to_bank_id']);
                $table->dropColumn('momo_to_bank_id');
            }));
        }
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
