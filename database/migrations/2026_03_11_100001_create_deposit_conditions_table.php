<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deposit_currency_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('term_days_min')->nullable();
            $table->unsignedInteger('term_days_max')->nullable();
            $table->decimal('amount_min', 18, 2)->nullable();
            $table->decimal('amount_max', 18, 2)->nullable();
            $table->decimal('rate', 6, 3);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('deposit_conditions', function (Blueprint $table) {
            $table->index('deposit_currency_id');
            $table->index('term_days_min');
            $table->index('term_days_max');
            $table->index('amount_min');
            $table->index('amount_max');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_conditions');
    }
};
