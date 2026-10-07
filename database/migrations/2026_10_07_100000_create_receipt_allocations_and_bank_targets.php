<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One receipt can now settle several invoices.
 *
 *  - receipt_allocations: one row per (receipt, invoice) pair saying how much
 *    of that receipt went to that invoice, split into cash applied, WHT,
 *    7% VAT withheld and other deductions. The invoice's outstanding amount
 *    is always: invoice.total - SUM(allocations.settled).
 *  - receipts.unapplied_amount: money received that is not (yet) applied to
 *    any invoice, i.e. the client's credit / advance payment.
 *  - receipts.cheque_to_bank_id / transfer_to_bank_id: which of OUR bank
 *    accounts the cheque will be deposited into / the transfer landed in.
 *    (cheque_bank / transfer_bank stay as the payer's bank, free text.)
 *  - bank_transactions.narration: human-readable ledger line.
 *
 * Existing receipts are backfilled with a single allocation each so every
 * receipt — old or new — reads the same way.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receipt_id')->index();
            $table->unsignedBigInteger('invoice_id')->index();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->decimal('amount_applied', 15, 2)->default(0);   // money from this receipt
            $table->decimal('wht_amount', 15, 2)->default(0);       // withholding tax credited
            $table->decimal('vat7_amount', 15, 2)->default(0);      // 7% VAT withheld credited
            $table->decimal('deduction_amount', 15, 2)->default(0); // other deductions credited
            $table->decimal('settled', 15, 2)->default(0);          // sum of the four above
            $table->string('source', 20)->default('receipt');       // receipt | credit | legacy
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'receipt_id']);
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->unsignedBigInteger('cheque_to_bank_id')->nullable()->after('cheque_bank');
            $table->unsignedBigInteger('transfer_to_bank_id')->nullable()->after('transfer_bank');
            $table->decimal('unapplied_amount', 15, 2)->default(0)->after('amount_received');
        });

        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->string('narration')->nullable()->after('balance');
        });

        $this->backfillAllocations();
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropColumn('narration');
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn(['cheque_to_bank_id', 'transfer_to_bank_id', 'unapplied_amount']);
        });

        Schema::dropIfExists('receipt_allocations');
    }

    /**
     * Legacy receipt maths: total = money received + WHT + 7% VAT, and
     * dAmount (other deductions) is kept separately. The invoice is settled
     * by total + dAmount — exactly what transactions.receipt_amount stores.
     */
    private function backfillAllocations(): void
    {
        $now = now();

        DB::table('receipts')
            ->whereNotNull('invoice_id')
            ->orderBy('id')
            ->chunkById(500, function ($receipts) use ($now) {
                $rows = [];
                foreach ($receipts as $r) {
                    $wht = (float) ($r->wht_amount ?? 0);
                    $vat = (float) ($r->vat7_value ?? 0);
                    $ded = (float) ($r->dAmount ?? 0);
                    $total = (float) ($r->total ?? 0);
                    $applied = round($total - $wht - $vat, 2);

                    $rows[] = [
                        'receipt_id' => $r->id,
                        'invoice_id' => $r->invoice_id,
                        'client_id' => $r->client_id,
                        'amount_applied' => $applied,
                        'wht_amount' => $wht,
                        'vat7_amount' => $vat,
                        'deduction_amount' => $ded,
                        'settled' => round($applied + $wht + $vat + $ded, 2),
                        'source' => 'legacy',
                        'user_id' => $r->user_id,
                        'created_at' => $r->created_at ?? $now,
                        'updated_at' => $now,
                    ];
                }
                if ($rows) {
                    DB::table('receipt_allocations')->insert($rows);
                }
            });
    }
};
