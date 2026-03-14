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
            if (! Schema::hasColumn('site_settings', 'footer_menu_1')) {
                $table->json('footer_menu_1')->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'footer_menu_2')) {
                $table->json('footer_menu_2')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table) {
            if (Schema::hasColumn('site_settings', 'footer_menu_1')) {
                $table->dropColumn('footer_menu_1');
            }
            if (Schema::hasColumn('site_settings', 'footer_menu_2')) {
                $table->dropColumn('footer_menu_2');
            }
        });
    }
};

