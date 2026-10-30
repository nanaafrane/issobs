<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Moves between our accounts can now have several sources AND several destinations, and the
 * money can change form on the way (a cheque cashed and the cash deposited into two banks).
 *
 * account_transfers stays one row per "who funded what" pair; these columns describe the two
 * ends of each pair so the move can be shown as it was entered:
 *
 *  method        how it LEFT the source (cheque | transfer | withdrawal | deposit | handover), as before
 *  in_method     how it ARRIVED (transfer | cheque = cheque deposited | cash); null on older rows
 *  in_reference  deposit slip / transfer reference on the receiving side
 *  in_date       the day it arrived, when not the day it left (cash deposited the next day)
 *  leg_out       which source line of the move (0, 1, …)
 *  leg_in        which destination line of the move
 *
 * Safe to re-run; older rows keep working (no leg = one pair per line, as entered then).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_transfers', function (Blueprint $table) {
            if (! Schema::hasColumn('account_transfers', 'in_method')) {
                $table->string('in_method', 12)->nullable()->after('reference');
            }
            if (! Schema::hasColumn('account_transfers', 'in_reference')) {
                $table->string('in_reference', 60)->nullable()->after('in_method');
            }
            if (! Schema::hasColumn('account_transfers', 'in_date')) {
                $table->date('in_date')->nullable()->after('transfer_date');
            }
            if (! Schema::hasColumn('account_transfers', 'leg_out')) {
                $table->unsignedSmallInteger('leg_out')->nullable()->after('batch');
            }
            if (! Schema::hasColumn('account_transfers', 'leg_in')) {
                $table->unsignedSmallInteger('leg_in')->nullable()->after('leg_out');
            }
        });
    }

    public function down(): void
    {
        Schema::table('account_transfers', function (Blueprint $table) {
            foreach (['in_method', 'in_reference', 'in_date', 'leg_out', 'leg_in'] as $c) {
                if (Schema::hasColumn('account_transfers', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
