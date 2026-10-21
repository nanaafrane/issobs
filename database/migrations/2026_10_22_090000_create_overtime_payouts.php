<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Overtime is paid through pay-outs, not one bank payment per daily entry.
 *
 *  overtime_payouts   one per office and period: method (cash through the office officer,
 *                     MoMo from head office, or through payroll), the one expense and the one
 *                     payment that fund it, who holds the cash, returned money, status.
 *  overtimes          payout_id, and the disbursement of each entry to its guard
 *                     (when, how, reference, by whom).
 *
 * Existing data: the expense each approved entry used to create on its own is removed when it
 * was never paid (the entry is simply "owed" now). Where such an expense was already paid from
 * Bank & cash, it is kept and its entry is marked paid by that payment.
 * Safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('overtime_payouts')) {
            Schema::create('overtime_payouts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('field_id')->index();
                $table->date('period_from');
                $table->date('period_to');
                $table->string('method', 10);                       // cash | momo | payroll
                $table->date('payroll_month')->nullable();          // payroll pay-outs: the salary month
                $table->unsignedBigInteger('bank_id')->nullable();  // account it was paid from
                $table->unsignedBigInteger('payment_id')->nullable()->index();
                $table->unsignedBigInteger('expense_id')->nullable()->index();
                $table->string('held_by', 120)->nullable();         // officer given the cash
                $table->decimal('total', 15, 2)->default(0);        // entries on it when created
                $table->decimal('returned', 15, 2)->default(0);     // money handed back for entries not paid
                $table->string('status', 10)->default('open');      // open | closed | cancelled
                $table->string('notes', 500)->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->unsignedBigInteger('cancelled_by')->nullable();
                $table->string('cancel_reason', 500)->nullable();
                $table->timestamps();
            });
        }

        $this->withZeroDatesAllowed(function () {
            Schema::table('overtimes', function (Blueprint $table) {
                if (! Schema::hasColumn('overtimes', 'payout_id')) {
                    $table->unsignedBigInteger('payout_id')->nullable()->after('expense_id')->index();
                }
                if (! Schema::hasColumn('overtimes', 'disbursed_at')) {
                    $table->date('disbursed_at')->nullable()->after('payout_id');
                }
                if (! Schema::hasColumn('overtimes', 'disbursed_method')) {
                    $table->string('disbursed_method', 10)->nullable()->after('disbursed_at');   // cash | momo | payroll | payment (old)
                }
                if (! Schema::hasColumn('overtimes', 'disbursed_ref')) {
                    $table->string('disbursed_ref', 80)->nullable()->after('disbursed_method');
                }
                if (! Schema::hasColumn('overtimes', 'disbursed_by')) {
                    $table->unsignedBigInteger('disbursed_by')->nullable()->after('disbursed_ref');
                }
            });
        });

        $this->withZeroDatesAllowed(fn () => $this->convertOldExpenses());
    }

    /**
     * Old one-expense-per-entry rows: unpaid ones go, paid ones mark their entry paid.
     * The link is overtimes.expense_id (the old code could not always save expenses.overtime_id,
     * which was not a fillable field), and expenses.overtime_id where it is set.
     */
    private function convertOldExpenses(): void
    {
        if (! Schema::hasColumn('overtimes', 'expense_id')) {
            return;
        }
        $hasPayment = Schema::hasColumn('expenses', 'payment_id');
        $links = DB::table('overtimes')->whereNotNull('expense_id')->pluck('expense_id', 'id')->all();   // overtime id => expense id
        if (Schema::hasColumn('expenses', 'overtime_id')) {
            foreach (DB::table('expenses')->whereNotNull('overtime_id')->pluck('overtime_id', 'id') as $expenseId => $overtimeId) {
                $links[$overtimeId] ??= $expenseId;
            }
        }

        foreach ($links as $overtimeId => $expenseId) {
            $e = DB::table('expenses')->where('id', $expenseId)->first();
            if (! $e || $e->deleted_at) {
                DB::table('overtimes')->where('id', $overtimeId)->where('expense_id', $expenseId)->update(['expense_id' => null]);
                continue;
            }
            $paymentId = $hasPayment ? $e->payment_id : null;
            if ($paymentId) {
                $paidOn = DB::table('account_payments')->where('id', $paymentId)->value('payment_date');
                DB::table('overtimes')->where('id', $overtimeId)->whereNull('disbursed_at')->update([
                    'disbursed_at' => $paidOn ?: now()->toDateString(),
                    'disbursed_method' => 'payment',
                    'disbursed_ref' => 'Payment #' . $paymentId,
                ]);
                continue;
            }
            DB::table('expenses')->where('id', $expenseId)->update(['deleted_at' => now()]);
            DB::table('overtimes')->where('id', $overtimeId)->update(['expense_id' => null]);
        }
    }

    public function down(): void
    {
        $this->withZeroDatesAllowed(function () {
            Schema::table('overtimes', function (Blueprint $table) {
                $table->dropIndex(['payout_id']);
                $table->dropColumn(['payout_id', 'disbursed_at', 'disbursed_method', 'disbursed_ref', 'disbursed_by']);
            });
        });
        Schema::dropIfExists('overtime_payouts');
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
