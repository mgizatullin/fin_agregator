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
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->unsignedInteger('grace_period')->nullable();
            $table->decimal('annual_fee', 15, 2)->nullable();
            $table->decimal('psk', 8, 2)->nullable();
            $table->decimal('rate', 8, 2)->nullable();
            $table->string('cashback')->nullable();
            $table->decimal('issue_cost', 15, 2)->nullable();
            $table->boolean('atm_withdrawal')->default(false);
            $table->string('card_type')->nullable();
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
        Schema::dropIfExists('cards');
    }
};
