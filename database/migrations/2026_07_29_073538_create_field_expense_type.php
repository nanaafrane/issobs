<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Records which expense types are *typically* used at which field office,
        // seeded from the expenses.docx reference list. This is used purely to
        // power a soft "heads up, this type isn't usually used here" warning on
        // the create form -- it does NOT restrict selection (per requirement:
        // dropdown stays fully open, unusual picks are flagged, not blocked).
        Schema::create('field_expense_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')->nullable();
            $table->foreignId('expense_type_id')->nullable();
            $table->timestamps();

            $table->unique(['field_id', 'expense_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_expense_type');
    }
};
