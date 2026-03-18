<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('site_settings', 'about_project_seo_title')) {
                $table->string('about_project_seo_title')->nullable();
            }

            if (! Schema::hasColumn('site_settings', 'about_project_seo_description')) {
                $table->text('about_project_seo_description')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table) {
            if (Schema::hasColumn('site_settings', 'about_project_seo_title')) {
                $table->dropColumn('about_project_seo_title');
            }

            if (Schema::hasColumn('site_settings', 'about_project_seo_description')) {
                $table->dropColumn('about_project_seo_description');
            }
        });
    }
};

