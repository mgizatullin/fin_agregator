<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->unsignedInteger('min_term_days')->nullable()->after('rate');
            $table->unsignedInteger('max_term_days')->nullable()->after('min_term_days');
        });

        DB::table('deposits')->update([
            'min_term_days' => DB::raw('COALESCE(min_term_months, term_months) * 30'),
            'max_term_days' => DB::raw('COALESCE(max_term_months, term_months) * 30'),
        ]);
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropColumn([
                'min_term_days',
                'max_term_days',
            ]);
        });
    }
};
