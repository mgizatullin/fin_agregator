<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('loans')) {
            return;
        }

        Schema::table('loans', function (Blueprint $table) {
            if (! Schema::hasColumn('loans', 'min_amount')) {
                $table->decimal('min_amount', 15, 2)->nullable()->after('max_amount');
            }

            if (! Schema::hasColumn('loans', 'term_days_min')) {
                $table->unsignedInteger('term_days_min')->nullable()->after('term_days');
            }

            if (! Schema::hasColumn('loans', 'term_no_interest_min')) {
                $table->unsignedInteger('term_no_interest_min')->nullable()->after('term_no_interest');
            }

            if (! Schema::hasColumn('loans', 'psk_min')) {
                $table->decimal('psk_min', 8, 2)->nullable()->after('psk');
            }

            if (! Schema::hasColumn('loans', 'rate_min')) {
                $table->decimal('rate_min', 8, 2)->nullable()->after('rate');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('loans')) {
            return;
        }

        Schema::table('loans', function (Blueprint $table) {
            $columns = [
                'min_amount',
                'term_days_min',
                'term_no_interest_min',
                'psk_min',
                'rate_min',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('loans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

