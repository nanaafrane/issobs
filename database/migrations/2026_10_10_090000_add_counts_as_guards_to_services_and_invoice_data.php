<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * invoice_data.quantity is both the pricing multiplier (quantity x unit_price)
 * and, in every report, the number of guards billed. A deduction line such as
 * "ABSENT DEDUCTION" needs a quantity (the absent units) but those units are not
 * guards, so reports were showing extra guards that were never added.
 *
 * counts_as_guards says whether a line's quantity is headcount. It lives on the
 * service (the setting) and is copied onto each invoice line when the invoice is
 * saved, so renaming or re-flagging a service later never rewrites old invoices.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('counts_as_guards')->default(true)->after('name');
        });

        Schema::table('invoice_data', function (Blueprint $table) {
            $table->boolean('counts_as_guards')->default(true)->after('service_name');
        });

        // Back-fill: any service / invoice line whose name contains "absent" is a deduction.
        // Review `SELECT DISTINCT service_name FROM invoice_data` before running in production.
        DB::table('services')
            ->whereRaw('LOWER(name) LIKE ?', ['%absent%'])
            ->update(['counts_as_guards' => false]);

        DB::table('invoice_data')
            ->whereRaw('LOWER(service_name) LIKE ?', ['%absent%'])
            ->update(['counts_as_guards' => false]);
    }

    public function down(): void
    {
        Schema::table('invoice_data', function (Blueprint $table) {
            $table->dropColumn('counts_as_guards');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('counts_as_guards');
        });
    }
};
