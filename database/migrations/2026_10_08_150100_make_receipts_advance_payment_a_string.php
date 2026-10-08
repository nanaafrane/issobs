<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * receipts.advance_payment was created as a boolean (tinyint) but the code
 * has always written the word "advance" into it. Strict MySQL rejects that
 * write; non-strict MySQL silently stores 0, so the flag was effectively
 * never saved. Make it a short string and normalise what is there:
 *   '1' / 'advance'  -> 'advance'
 *   '0' / ''         -> NULL
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->string('advance_payment', 20)->nullable()->change();
        });

        DB::table('receipts')->whereIn('advance_payment', ['1', 'advance'])->update(['advance_payment' => 'advance']);
        DB::table('receipts')->whereIn('advance_payment', ['0', ''])->update(['advance_payment' => null]);
    }

    public function down(): void
    {
        // Left as a string: converting back to boolean would lose nothing useful but
        // would break every write of "advance" again.
    }
};
