<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
 */
return new class extends Migration
{
    public function up(): void
    {
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

        Schema::table('clients', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone_number');
            $table->boolean('auto_apply_credit')->default(true)->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['email', 'auto_apply_credit']);
        });
        Schema::dropIfExists('statement_sends');
        Schema::dropIfExists('receipt_voids');
        Schema::dropIfExists('client_ledger_entries');
    }
};
