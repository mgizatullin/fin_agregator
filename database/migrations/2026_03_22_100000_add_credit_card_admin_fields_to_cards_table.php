<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            if (! Schema::hasColumn('cards', 'psk_text')) {
                $table->string('psk_text')->nullable()->after('credit_limit');
            }

            if (! Schema::hasColumn('cards', 'grace_period_text')) {
                $table->string('grace_period_text')->nullable()->after('grace_period');
            }

            if (! Schema::hasColumn('cards', 'annual_fee_text')) {
                $table->string('annual_fee_text')->nullable()->after('annual_fee');
            }

            if (! Schema::hasColumn('cards', 'cashback_text')) {
                $table->string('cashback_text')->nullable()->after('cashback');
            }

            if (! Schema::hasColumn('cards', 'decision_text')) {
                $table->string('decision_text')->nullable()->after('cashback_text');
            }

            if (! Schema::hasColumn('cards', 'image')) {
                $table->string('image')->nullable()->after('card_type');
            }

            if (! Schema::hasColumn('cards', 'conditions_text')) {
                $table->text('conditions_text')->nullable()->after('image');
            }

            if (! Schema::hasColumn('cards', 'rates_text')) {
                $table->text('rates_text')->nullable()->after('conditions_text');
            }

            if (! Schema::hasColumn('cards', 'cashback_details_text')) {
                $table->text('cashback_details_text')->nullable()->after('rates_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $columns = [
                'psk_text',
                'grace_period_text',
                'annual_fee_text',
                'cashback_text',
                'decision_text',
                'image',
                'conditions_text',
                'rates_text',
                'cashback_details_text',
            ];

            $existingColumns = array_filter(
                $columns,
                fn (string $column): bool => Schema::hasColumn('cards', $column),
            );

            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
