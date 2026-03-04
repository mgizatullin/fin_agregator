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
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('rate', 8, 2)->nullable();
            $table->unsignedInteger('term_months')->nullable();
            $table->decimal('min_amount', 15, 2)->nullable();
            $table->boolean('replenishment')->default(false);
            $table->boolean('partial_withdrawal')->default(false);
            $table->boolean('early_termination')->default(false);
            $table->boolean('auto_prolongation')->default(false);
            $table->boolean('insurance')->default(false);
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
        Schema::dropIfExists('deposits');
    }
};
