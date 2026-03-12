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
            $table->unsignedInteger('min_term_months')->nullable()->after('rate');
            $table->unsignedInteger('max_term_months')->nullable()->after('min_term_months');
        });

        DB::table('deposits')
            ->whereNotNull('term_months')
            ->update([
                'min_term_months' => DB::raw('term_months'),
                'max_term_months' => DB::raw('term_months'),
            ]);
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropColumn([
                'min_term_months',
                'max_term_months',
            ]);
        });
    }
};
