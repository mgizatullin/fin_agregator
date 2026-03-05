<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_page_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('home_page_settings', 'main_value_block')) {
                $table->json('main_value_block')->nullable();
            }
            if (! Schema::hasColumn('home_page_settings', 'values_grid')) {
                $table->json('values_grid')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('home_page_settings', function (Blueprint $table) {
            if (Schema::hasColumn('home_page_settings', 'main_value_block')) {
                $table->dropColumn('main_value_block');
            }
            if (Schema::hasColumn('home_page_settings', 'values_grid')) {
                $table->dropColumn('values_grid');
            }
        });
    }
};
