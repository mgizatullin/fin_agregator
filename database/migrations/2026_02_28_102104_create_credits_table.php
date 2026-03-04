<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('rate', 8, 2)->nullable();
            $table->decimal('psk', 8, 2)->nullable();
            $table->decimal('max_amount', 15, 2)->nullable();
            $table->unsignedInteger('term_months')->nullable();
            $table->boolean('income_proof_required')->default(false);
            $table->unsignedInteger('age_min')->nullable();
            $table->unsignedInteger('age_max')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credits');
    }
};
