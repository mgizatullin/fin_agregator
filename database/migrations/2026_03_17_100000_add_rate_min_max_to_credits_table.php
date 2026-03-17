<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('credits')) {
            return;
        }
        Schema::table('credits', function (Blueprint $table) {
            if (! Schema::hasColumn('credits', 'rate_min')) {
                $table->decimal('rate_min', 8, 2)->nullable()->after('rate');
            }
            if (! Schema::hasColumn('credits', 'rate_max')) {
                $table->decimal('rate_max', 8, 2)->nullable()->after('rate_min');
            }
        });
        DB::table('credits')->whereNotNull('rate')->update([
            'rate_min' => DB::raw('rate'),
            'rate_max' => DB::raw('rate'),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('credits')) {
            return;
        }
        Schema::table('credits', function (Blueprint $table) {
            if (Schema::hasColumn('credits', 'rate_min')) {
                $table->dropColumn('rate_min');
            }
            if (Schema::hasColumn('credits', 'rate_max')) {
                $table->dropColumn('rate_max');
            }
        });
    }
};
