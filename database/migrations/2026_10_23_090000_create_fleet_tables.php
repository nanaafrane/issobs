<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fleet: vehicles, fuel and maintenance.
 *
 *  vehicles               the register every fuel / maintenance line points at (reg. no., office, driver).
 *  fuel_entries           one fill-up (the FUEL REQUEST AND USAGE REPORT sheet), 3-stage approval like overtime.
 *  fuel_payouts           approved fuel for an office and period paid in one go: one expense (type Fuel)
 *                         and one payment (to the station, or cash), like overtime pay-outs.
 *  maintenance_types      user-defined (Roadworthy, Insurance, Tyres…), each posting to an expense item;
 *                         a type can repeat every N months / N km, and can be a document with an expiry.
 *  vehicle_maintenances   one job / renewal on one vehicle; approved -> one expense, paid from Payments.
 *
 * Safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles')) {
            Schema::create('vehicles', function (Blueprint $table) {
                $table->id();
                $table->string('reg_no', 30)->unique();
                $table->string('name', 120)->nullable();                 // make / model, e.g. Toyota Land Cruiser
                $table->string('kind', 20)->default('car');              // car | pickup | van | bus | motorbike | other
                $table->string('fuel_type', 10)->default('diesel');      // diesel | super | other
                $table->unsignedBigInteger('field_id')->nullable()->index();
                $table->unsignedBigInteger('driver_employee_id')->nullable();
                $table->string('driver_name', 120)->nullable();
                $table->decimal('tank_litres', 8, 2)->nullable();
                $table->unsignedInteger('opening_odometer')->nullable();
                $table->string('status', 10)->default('active');         // active | off_road | disposed
                $table->string('notes', 500)->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('fuel_payouts')) {
            Schema::create('fuel_payouts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('field_id')->index();
                $table->date('period_from');
                $table->date('period_to');
                $table->string('station', 100)->nullable();              // only entries from this station, when set
                $table->string('payee', 150);
                $table->unsignedBigInteger('bank_id')->nullable();
                $table->unsignedBigInteger('payment_id')->nullable()->index();
                $table->unsignedBigInteger('expense_id')->nullable()->index();
                $table->decimal('total', 15, 2)->default(0);
                $table->decimal('litres', 12, 2)->default(0);
                $table->string('status', 10)->default('paid');           // paid | cancelled
                $table->string('notes', 500)->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->unsignedBigInteger('cancelled_by')->nullable();
                $table->string('cancel_reason', 500)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('fuel_entries')) {
            Schema::create('fuel_entries', function (Blueprint $table) {
                $table->id();
                $table->date('entry_date');
                $table->unsignedBigInteger('vehicle_id');
                $table->unsignedBigInteger('field_id')->nullable()->index();
                $table->unsignedBigInteger('driver_employee_id')->nullable();
                $table->string('driver_name', 120)->nullable();
                $table->string('purpose', 150)->nullable();              // trip destination, e.g. DEPLOYMENT & PATROL
                $table->string('fuel_type', 10)->default('diesel');
                $table->decimal('litres', 10, 2)->default(0);
                $table->decimal('price_per_litre', 10, 3)->nullable();
                $table->decimal('amount_requested', 12, 2)->nullable();
                $table->decimal('amount', 12, 2)->default(0);            // fuel issued (GH₵)
                $table->string('station', 100)->nullable();
                $table->unsignedInteger('odometer')->nullable();         // km reading at the pump
                $table->string('receipt_no', 60)->nullable();
                $table->string('notes', 500)->nullable();
                $table->unsignedBigInteger('user_1')->nullable();
                $table->string('status_1')->nullable();
                $table->dateTime('date_1')->nullable();
                $table->unsignedBigInteger('user_2')->nullable();
                $table->string('status_2')->nullable();
                $table->dateTime('date_2')->nullable();
                $table->unsignedBigInteger('user_3')->nullable();
                $table->string('status_3')->nullable();
                $table->dateTime('date_3')->nullable();
                $table->unsignedBigInteger('payout_id')->nullable()->index();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['vehicle_id', 'entry_date']);
                $table->index(['entry_date', 'field_id']);
            });
        }

        if (! Schema::hasTable('maintenance_types')) {
            Schema::create('maintenance_types', function (Blueprint $table) {
                $table->id();
                $table->string('name', 80)->unique();
                $table->unsignedBigInteger('expense_type_id')->nullable();  // the expense item it is paid under
                $table->boolean('is_document')->default(false);             // a certificate / policy with an expiry date
                $table->unsignedSmallInteger('repeat_months')->nullable();  // due again after N months
                $table->unsignedInteger('repeat_km')->nullable();           // due again after N km
                $table->boolean('is_active')->default(true);
                $table->string('notes', 300)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('vehicle_maintenances')) {
            Schema::create('vehicle_maintenances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vehicle_id');
                $table->unsignedBigInteger('maintenance_type_id')->index();
                $table->unsignedBigInteger('field_id')->nullable()->index();
                $table->date('service_date');
                $table->string('vendor', 150)->nullable();                 // garage, mechanic, insurer, DVLA
                $table->string('reference', 80)->nullable();               // invoice, policy or certificate no.
                $table->string('description', 500)->nullable();
                $table->decimal('amount', 12, 2)->default(0);
                $table->unsignedInteger('odometer')->nullable();
                $table->date('valid_until')->nullable();                   // when it is due again / expires
                $table->unsignedInteger('next_due_km')->nullable();
                $table->unsignedBigInteger('user_1')->nullable();
                $table->string('status_1')->nullable();
                $table->dateTime('date_1')->nullable();
                $table->unsignedBigInteger('user_2')->nullable();
                $table->string('status_2')->nullable();
                $table->dateTime('date_2')->nullable();
                $table->unsignedBigInteger('user_3')->nullable();
                $table->string('status_3')->nullable();
                $table->dateTime('date_3')->nullable();
                $table->unsignedBigInteger('expense_id')->nullable()->index();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['vehicle_id', 'service_date']);
            });
        }

        $this->seedTypes();
    }

    /** The common types, each tied to the expense item the Main Cash sheet already uses for it. */
    private function seedTypes(): void
    {
        $types = [
            // name => [expense item, document?, repeat months, repeat km]
            'Roadworthy' => ['License & Registration', true, 12, null],
            'Insurance' => ['Insurance', true, 12, null],
            'Vehicle registration / licence' => ['License & Registration', true, 12, null],
            'Tyres' => ['Vehicle Repairs & Maintenance', false, null, 40000],
            'Servicing (oil & filters)' => ['Lubricant', false, 3, 5000],
            'Battery' => ['Vehicle Repairs & Maintenance', false, null, null],
            'Brakes' => ['Vehicle Repairs & Maintenance', false, null, null],
            'Repairs (mechanical / electrical)' => ['Vehicle Repairs & Maintenance', false, null, null],
            'Body work & accident repairs' => ['Accident Car', false, null, null],
            'Motorbike repairs' => ['Motor Repairs', false, null, null],
        ];
        foreach ($types as $name => [$item, $doc, $months, $km]) {
            if (DB::table('maintenance_types')->where('name', $name)->exists()) {
                continue;
            }
            $typeId = DB::table('expense_types')->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($item)])->orderByRaw("CASE WHEN scope = 'field' THEN 0 ELSE 1 END")->value('id')
                ?? DB::table('expense_types')->insertGetId(['name' => $item, 'scope' => 'field', 'is_accommodation' => false, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('maintenance_types')->insert([
                'name' => $name, 'expense_type_id' => $typeId, 'is_document' => $doc, 'repeat_months' => $months, 'repeat_km' => $km,
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenances');
        Schema::dropIfExists('maintenance_types');
        Schema::dropIfExists('fuel_entries');
        Schema::dropIfExists('fuel_payouts');
        Schema::dropIfExists('vehicles');
    }
};
