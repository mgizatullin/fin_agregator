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
            if (! Schema::hasColumn('site_settings', 'about_project_page_title')) {
                $table->string('about_project_page_title')->nullable();
            }

            if (! Schema::hasColumn('site_settings', 'about_project_page_subtitle')) {
                $table->text('about_project_page_subtitle')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table) {
            if (Schema::hasColumn('site_settings', 'about_project_page_title')) {
                $table->dropColumn('about_project_page_title');
            }

            if (Schema::hasColumn('site_settings', 'about_project_page_subtitle')) {
                $table->dropColumn('about_project_page_subtitle');
            }
        });
    }
};

