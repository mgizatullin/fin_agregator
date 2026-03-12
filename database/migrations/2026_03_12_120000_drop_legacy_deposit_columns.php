<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Удаление плоских полей ставок/сроков/сумм из deposits.
     * Все данные берутся из deposit_currencies и deposit_conditions.
     */
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropColumn([
                'rate',
                'min_rate',
                'max_rate',
                'term_months',
                'min_term_months',
                'max_term_months',
                'min_term_days',
                'max_term_days',
                'term_days',
                'min_amount',
                'max_amount',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->decimal('rate', 8, 2)->nullable()->after('slug');
            $table->decimal('min_rate', 8, 2)->nullable()->after('rate');
            $table->decimal('max_rate', 8, 2)->nullable()->after('min_rate');
            $table->unsignedInteger('term_months')->nullable()->after('max_rate');
            $table->unsignedInteger('min_term_months')->nullable()->after('term_months');
            $table->unsignedInteger('max_term_months')->nullable()->after('min_term_months');
            $table->unsignedInteger('min_term_days')->nullable()->after('max_term_months');
            $table->unsignedInteger('max_term_days')->nullable()->after('min_term_days');
            $table->unsignedInteger('term_days')->nullable()->after('max_term_days');
            $table->decimal('min_amount', 15, 2)->nullable()->after('term_days');
            $table->decimal('max_amount', 15, 2)->nullable()->after('min_amount');
        });
    }
};
