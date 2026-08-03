<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // 'field'     => used by the 5 field units (Tema, Kumasi, Botwe, Koforidua, Takoradi)
            // 'corporate' => used only by Head Office
            $table->enum('scope', ['field', 'corporate'])->default('field');
            // lets you report on hostel/accommodation spend across all locations in one query
            $table->boolean('is_accommodation')->default(false);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();

            $table->unique(['name', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_types');
    }
};
