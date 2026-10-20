<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Money leaving the banks, and cash on hand.
 *
 *  banks               kind: bank | cash ("Main Cash" — cash on hand, same ledger as a bank);
 *                      custodian (who holds a cash account); opening balance + date; active flag
 *                      (an account with history is closed, never deleted).
 *  bank_transactions   entry_date (the day the money moved: cheque / payment date) and links to
 *                      the transfer or payment that posted the line.
 *  account_transfers   money moved between our own accounts (e.g. cheques from several banks
 *                      to fund Main Cash). One row per cheque/transfer; rows entered together
 *                      share a batch.
 *  account_payments    money paid out to someone else (cheque / transfer from a bank, cash from
 *                      a cash account), with payee, category and reference. Cheques stay
 *                      "issued" until cleared.
 *  expenses.payment_id the payment that paid an approved expense (and expenses.expense_date,
 *                      used by the expense screens but never created by a migration, where missing).
 *
 * Existing balances are kept: opening_balance is set so that
 *   banks.total = opening_balance + money in − money out on the ledger.
 * Safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->withZeroDatesAllowed(function () {
            Schema::table('banks', function (Blueprint $table) {
                if (! Schema::hasColumn('banks', 'kind')) {
                    $table->string('kind', 10)->default('bank')->after('name');
                }
                if (! Schema::hasColumn('banks', 'custodian')) {
                    $table->string('custodian', 120)->nullable()->after('acc_number');
                }
                if (! Schema::hasColumn('banks', 'opening_balance')) {
                    $table->decimal('opening_balance', 15, 2)->default(0)->after('total');
                }
                if (! Schema::hasColumn('banks', 'opening_date')) {
                    $table->date('opening_date')->nullable()->after('opening_balance');
                }
                if (! Schema::hasColumn('banks', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('opening_date');
                }
            });

            Schema::table('bank_transactions', function (Blueprint $table) {
                if (! Schema::hasColumn('bank_transactions', 'entry_date')) {
                    $table->date('entry_date')->nullable()->after('bank_id');
                }
                if (! Schema::hasColumn('bank_transactions', 'transfer_id')) {
                    $table->unsignedBigInteger('transfer_id')->nullable()->after('deposit_id')->index();
                }
                if (! Schema::hasColumn('bank_transactions', 'payment_id')) {
                    $table->unsignedBigInteger('payment_id')->nullable()->after('transfer_id')->index();
                }
            });

            Schema::table('expenses', function (Blueprint $table) {
                // Used by the expense screens since 2026 but never created by a migration
                // (live databases had it added by hand): add it only where it is missing.
                if (! Schema::hasColumn('expenses', 'expense_date')) {
                    $table->date('expense_date')->nullable()->after('amount');
                }
                if (! Schema::hasColumn('expenses', 'payment_id')) {
                    $table->unsignedBigInteger('payment_id')->nullable()->index();
                }
            });
        });

        if (! $this->hasIndex('bank_transactions', 'bank_transactions_bank_id_entry_date_index')) {
            $this->withZeroDatesAllowed(fn () => Schema::table('bank_transactions', fn (Blueprint $t) => $t->index(['bank_id', 'entry_date'], 'bank_transactions_bank_id_entry_date_index')));
        }

        if (! Schema::hasTable('account_transfers')) {
            Schema::create('account_transfers', function (Blueprint $table) {
                $table->id();
                $table->string('batch', 36)->index();
                $table->unsignedBigInteger('from_bank_id')->index();
                $table->unsignedBigInteger('to_bank_id')->index();
                $table->decimal('amount', 15, 2);
                $table->string('method', 12);                 // cheque | transfer | withdrawal | deposit
                $table->string('reference', 60)->nullable();  // cheque number / transfer ref
                $table->date('transfer_date');
                $table->string('notes', 500)->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamp('reversed_at')->nullable();
                $table->unsignedBigInteger('reversed_by')->nullable();
                $table->string('reversal_reason', 500)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('account_payments')) {
            Schema::create('account_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bank_id')->index();
                $table->string('payee', 150);
                $table->string('method', 12);                 // cheque | transfer | cash | momo
                $table->string('reference', 60)->nullable();
                $table->decimal('amount', 15, 2);
                $table->date('payment_date')->index();
                $table->string('category', 30)->index();
                $table->string('description', 500)->nullable();
                $table->string('status', 12)->default('paid'); // paid | issued (cheque) | cleared | cancelled
                $table->date('cleared_date')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->unsignedBigInteger('cancelled_by')->nullable();
                $table->string('cancel_reason', 500)->nullable();
                $table->timestamps();
            });
        }

        $this->withZeroDatesAllowed(fn () => $this->backfill());
    }

    private function backfill(): void
    {
        // Existing lines: the day they were entered.
        DB::table('bank_transactions')->whereNull('entry_date')->where('created_at', '>', '1000-01-01')
            ->update(['entry_date' => DB::raw('DATE(created_at)')]);
        // Lines with no real date (created_at 0000-00-00 or empty): the earliest known date.
        $earliest = DB::table('bank_transactions')->whereNotNull('entry_date')->where('entry_date', '>', '1000-01-01')->min('entry_date') ?? now()->toDateString();
        DB::table('bank_transactions')->where(fn ($q) => $q->whereNull('entry_date')->orWhere('entry_date', '<', '1000-01-01'))
            ->update(['entry_date' => $earliest]);
        DB::table('banks')->whereNull('kind')->update(['kind' => 'bank']);

        // Keep every balance exactly as it is: whatever the ledger does not explain
        // becomes the opening balance (only for accounts not set up yet).
        foreach (DB::table('banks')->whereNull('opening_date')->get() as $bank) {
            $moved = DB::table('bank_transactions')->where('bank_id', $bank->id)
                ->selectRaw('COALESCE(SUM(credit),0) - COALESCE(SUM(debit),0) AS net, MIN(entry_date) AS first')->first();
            DB::table('banks')->where('id', $bank->id)->update([
                'opening_balance' => round((float) $bank->total - (float) $moved->net, 2),
                'opening_date' => $moved->first && ! str_starts_with((string) $moved->first, '0000') ? $moved->first : ($bank->created_at && ! str_starts_with((string) $bank->created_at, '0000') ? substr((string) $bank->created_at, 0, 10) : now()->toDateString()),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('account_payments');
        Schema::dropIfExists('account_transfers');
        $this->withZeroDatesAllowed(function () {
            if (Schema::hasColumn('expenses', 'payment_id')) {
                Schema::table('expenses', function (Blueprint $table) {
                    $table->dropIndex(['payment_id']);
                    $table->dropColumn('payment_id');
                });
            }
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->dropIndex('bank_transactions_bank_id_entry_date_index');
                $table->dropIndex(['transfer_id']);
                $table->dropIndex(['payment_id']);
                $table->dropColumn(['entry_date', 'transfer_id', 'payment_id']);
            });
            Schema::table('banks', function (Blueprint $table) {
                $table->dropColumn(['kind', 'custodian', 'opening_balance', 'opening_date', 'is_active']);
            });
        });
    }

    private function hasIndex(string $table, string $name): bool
    {
        try {
            return collect(Schema::getIndexes($table))->contains(fn ($i) => $i['name'] === $name);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Older rows may have created_at = '0000-00-00 00:00:00'; MySQL versions that
     * rebuild the table to add a column reject those under strict mode (error 1292).
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
