<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bank Deposit as one record per bank slip, with MoMo.
 *
 *  bank_deposits  one row per slip taken to the bank: date, slip number, who
 *                 deposited, cash + MoMo withdrawn, the MoMo withdrawal charge,
 *                 gross (cash + MoMo) and total (= amount on the slip). A wrong
 *                 deposit is reversed, never deleted (reversed_at / reason).
 *  collections    which slip took each receipt's cash and each receipt's MoMo
 *                 (they can go on different days), and a note for collections
 *                 marked "already banked" outside the system.
 *
 * Existing rows are untouched: old deposits keep their figures, old collections
 * marked Deposited stay closed. Safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->withZeroDatesAllowed(function () {
            Schema::table('bank_deposits', function (Blueprint $table) {
                $add = fn (string $c) => ! Schema::hasColumn('bank_deposits', $c);
                if ($add('momo_amount')) {
                    $table->decimal('momo_amount', 15, 2)->nullable()->after('cheque_amount');
                }
                if ($add('momo_charge')) {
                    $table->decimal('momo_charge', 15, 2)->nullable()->after('momo_amount');
                }
                if ($add('gross_amount')) {
                    $table->decimal('gross_amount', 15, 2)->nullable()->after('momo_charge');   // cash + cheque + MoMo, before the charge
                }
                if ($add('deposit_date')) {
                    $table->date('deposit_date')->nullable()->after('total');
                }
                if ($add('slip_number')) {
                    $table->string('slip_number', 60)->nullable()->after('deposit_date');
                }
                if ($add('depositor')) {
                    $table->string('depositor', 120)->nullable()->after('slip_number');
                }
                if ($add('notes')) {
                    $table->string('notes', 500)->nullable()->after('depositor');
                }
                if ($add('reversed_at')) {
                    $table->timestamp('reversed_at')->nullable()->after('notes');
                }
                if ($add('reversed_by')) {
                    $table->unsignedBigInteger('reversed_by')->nullable()->after('reversed_at');
                }
                if ($add('reversal_reason')) {
                    $table->string('reversal_reason', 500)->nullable()->after('reversed_by');
                }
            });

            Schema::table('collections', function (Blueprint $table) {
                if (! Schema::hasColumn('collections', 'cash_deposit_id')) {
                    $table->unsignedBigInteger('cash_deposit_id')->nullable()->after('status')->index();
                }
                if (! Schema::hasColumn('collections', 'momo_deposit_id')) {
                    $table->unsignedBigInteger('momo_deposit_id')->nullable()->after('cash_deposit_id')->index();
                }
                if (! Schema::hasColumn('collections', 'deposit_note')) {
                    $table->string('deposit_note', 500)->nullable()->after('momo_deposit_id');
                }
            });
        });
    }

    public function down(): void
    {
        $this->withZeroDatesAllowed(function () {
            Schema::table('collections', function (Blueprint $table) {
                $table->dropIndex(['cash_deposit_id']);
                $table->dropIndex(['momo_deposit_id']);
                $table->dropColumn(['cash_deposit_id', 'momo_deposit_id', 'deposit_note']);
            });
            Schema::table('bank_deposits', function (Blueprint $table) {
                $table->dropColumn(['momo_amount', 'momo_charge', 'gross_amount', 'deposit_date', 'slip_number',
                    'depositor', 'notes', 'reversed_at', 'reversed_by', 'reversal_reason']);
            });
        });
    }

    /**
     * Older rows may have created_at = '0000-00-00 00:00:00'; MySQL versions that
     * rebuild the table to add a column reject those under strict mode (error 1292).
     * Allow them for this connection only while the columns are added.
     */
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
