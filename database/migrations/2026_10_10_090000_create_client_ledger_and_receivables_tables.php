<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Client account, done properly.
 *
 *  client_ledger_entries  Every invoice, receipt, WHT/VAT/deduction and credit
 *                         application is posted here once. Entries are never
 *                         edited or deleted: a change posts a reversal (dated
 *                         the day of the change) and a replacement, so a
 *                         statement already sent never changes silently.
 *                         Fill it after migrating with: php artisan client-ledger:sync
 *  receipt_voids          A voided receipt's full record (reason, who, when, what it
 *                         paid) — the receipt itself leaves the receipts table so
 *                         every existing report stays correct without changes.
 *  statement_sends        Who emailed which statement to whom.
 *  clients.email          Where statements are sent.
 *  clients.auto_apply_credit  Use unused credit on new invoices automatically.
 *
 * Safe to re-run: MySQL cannot roll back table changes, so if a previous run
 * stopped half-way, anything already created is skipped.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_ledger_entries')) {
        Schema::create('client_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->date('entry_date');
            $table->string('kind', 20);              // invoice | receipt | wht | vat | deduction | credit_applied | reversal
            $table->string('source_key', 60);        // invoice:12, receipt:7:money, receipt:7:wht, allocation:33 ...
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
            $table->unsignedBigInteger('receipt_id')->nullable()->index();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('description');
            $table->string('details', 500)->nullable();
            $table->unsignedBigInteger('reverses_id')->nullable()->index();   // the entry this one cancels
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'entry_date', 'id']);
            $table->index(['client_id', 'source_key']);
        });
        }

        if (! Schema::hasTable('receipt_voids')) {
        Schema::create('receipt_voids', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receipt_id')->index();     // the number it had (FWSSR…)
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->decimal('amount', 15, 2)->default(0);           // money received on it
            $table->string('reason', 500);
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->json('snapshot');                               // receipt, allocations, collection, bank lines
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('statement_sends')) {
        Schema::create('statement_sends', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->string('sent_to', 500);
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->decimal('closing_balance', 15, 2)->default(0);
            $table->boolean('with_pdf')->default(false);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
        }

        $this->withZeroDatesAllowed(function () {
            Schema::table('clients', function (Blueprint $table) {
                if (! Schema::hasColumn('clients', 'email')) {
                    $table->string('email')->nullable()->after('phone_number');
                }
                if (! Schema::hasColumn('clients', 'auto_apply_credit')) {
                    $table->boolean('auto_apply_credit')->default(true)->after('email');
                }
            });
        });
    }

    public function down(): void
    {
        $this->withZeroDatesAllowed(function () {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn(['email', 'auto_apply_credit']);
            });
        });
        Schema::dropIfExists('statement_sends');
        Schema::dropIfExists('receipt_voids');
        Schema::dropIfExists('client_ledger_entries');
    }

    /**
     * Some older rows have created_at = '0000-00-00 00:00:00'. On MySQL/MariaDB
     * versions that rebuild the table to add a column, strict mode re-checks
     * every row and rejects those dates (error 1292). Allow them for this
     * connection while the column is added, then restore the mode. No data
     * is changed.
     */
    private function withZeroDatesAllowed(callable $change): void
    {
        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            $change();

            return;
        }

        $mode = DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;
        $relaxed = implode(',', array_diff(explode(',', $mode), ['NO_ZERO_DATE', 'NO_ZERO_IN_DATE']));
        DB::statement('SET SESSION sql_mode = ?', [$relaxed]);
        try {
            $change();
        } finally {
            DB::statement('SET SESSION sql_mode = ?', [$mode]);
        }
    }
};
