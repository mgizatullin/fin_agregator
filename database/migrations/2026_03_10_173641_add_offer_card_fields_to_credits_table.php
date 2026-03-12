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
        Schema::table('credits', function (Blueprint $table) {
            $table->decimal('review_rating', 3, 2)->nullable()->after('name');
            $table->unsignedInteger('review_count')->nullable()->after('review_rating');
            $table->decimal('min_amount', 15, 2)->nullable()->after('max_amount');
            $table->unsignedInteger('min_term_months')->nullable()->after('term_months');
            $table->unsignedInteger('max_term_months')->nullable()->after('min_term_months');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->dropColumn([
                'review_rating',
                'review_count',
                'min_amount',
                'min_term_months',
                'max_term_months',
            ]);
        });
    }
};
