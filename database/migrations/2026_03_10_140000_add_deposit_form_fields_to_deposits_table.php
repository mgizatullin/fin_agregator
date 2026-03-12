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
        Schema::table('deposits', function (Blueprint $table) {
            $table->decimal('max_amount', 15, 2)->nullable()->after('min_amount');
            $table->string('deposit_type', 100)->nullable()->after('max_amount');
            $table->boolean('capitalization')->default(false)->after('deposit_type');
            $table->boolean('online_opening')->default(false)->after('capitalization');
            $table->boolean('monthly_interest_payment')->default(false)->after('online_opening');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropColumn([
                'max_amount',
                'deposit_type',
                'capitalization',
                'online_opening',
                'monthly_interest_payment',
            ]);
        });
    }
};
