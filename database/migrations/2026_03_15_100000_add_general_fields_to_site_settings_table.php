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
            if (! Schema::hasColumn('site_settings', 'copyright')) {
                $table->string('copyright')->nullable()->after('footer_heading_2');
            }
            if (! Schema::hasColumn('site_settings', 'custom_scripts')) {
                $table->text('custom_scripts')->nullable()->after('copyright');
            }
            if (! Schema::hasColumn('site_settings', 'logo')) {
                $table->string('logo')->nullable()->after('custom_scripts');
            }
            if (! Schema::hasColumn('site_settings', 'social_twitter')) {
                $table->string('social_twitter')->nullable()->after('logo');
            }
            if (! Schema::hasColumn('site_settings', 'social_facebook')) {
                $table->string('social_facebook')->nullable()->after('social_twitter');
            }
            if (! Schema::hasColumn('site_settings', 'social_github')) {
                $table->string('social_github')->nullable()->after('social_facebook');
            }
            if (! Schema::hasColumn('site_settings', 'social_instagram')) {
                $table->string('social_instagram')->nullable()->after('social_github');
            }
            if (! Schema::hasColumn('site_settings', 'social_youtube')) {
                $table->string('social_youtube')->nullable()->after('social_instagram');
            }
            if (! Schema::hasColumn('site_settings', 'social_zen')) {
                $table->string('social_zen')->nullable()->after('social_youtube');
            }
            if (! Schema::hasColumn('site_settings', 'social_telegram')) {
                $table->string('social_telegram')->nullable()->after('social_zen');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }
        Schema::table('site_settings', function (Blueprint $table) {
            $columns = [
                'copyright', 'custom_scripts', 'logo',
                'social_twitter', 'social_facebook', 'social_github',
                'social_instagram', 'social_youtube', 'social_zen', 'social_telegram',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('site_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
