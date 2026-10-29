<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Subscription payments made through the system (Hubtel). Manual renewals stay in
 * config/subscription.php; both count towards the due date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20)->default('hubtel');
            $table->string('client_reference', 32)->unique();   // ours, sent to Hubtel
            $table->string('provider_reference')->nullable();    // Hubtel checkoutId / transaction id
            $table->unsignedSmallInteger('months')->default(1);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('GHS');
            $table->string('status', 20)->default('pending');    // pending | paid | failed
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload')->nullable();                 // last raw response, for support
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
