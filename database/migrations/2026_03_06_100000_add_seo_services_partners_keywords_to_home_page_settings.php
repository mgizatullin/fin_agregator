<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_page_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('home_page_settings', 'seo_title')) {
                $table->string('seo_title')->nullable();
            }
            if (! Schema::hasColumn('home_page_settings', 'seo_description')) {
                $table->text('seo_description')->nullable();
            }
            if (! Schema::hasColumn('home_page_settings', 'seo_keywords')) {
                $table->text('seo_keywords')->nullable();
            }
            if (! Schema::hasColumn('home_page_settings', 'services')) {
                $table->json('services')->nullable();
            }
            if (! Schema::hasColumn('home_page_settings', 'partners')) {
                $table->json('partners')->nullable();
            }
            if (! Schema::hasColumn('home_page_settings', 'keywords')) {
                $table->json('keywords')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('home_page_settings', function (Blueprint $table) {
            $columns = ['seo_title', 'seo_description', 'seo_keywords', 'services', 'partners', 'keywords'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('home_page_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
