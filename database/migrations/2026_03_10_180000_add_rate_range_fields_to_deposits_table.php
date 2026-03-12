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
            $table->decimal('min_rate', 8, 2)->nullable()->after('rate');
            $table->decimal('max_rate', 8, 2)->nullable()->after('min_rate');
        });

        DB::table('deposits')
            ->whereNotNull('rate')
            ->update([
                'min_rate' => DB::raw('rate'),
                'max_rate' => DB::raw('rate'),
            ]);
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropColumn([
                'min_rate',
                'max_rate',
            ]);
        });
    }
};
