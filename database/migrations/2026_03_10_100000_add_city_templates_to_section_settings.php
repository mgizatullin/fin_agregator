<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('section_settings', function (Blueprint $table) {
            $table->text('seo_title_template')->nullable()->after('seo_description');
            $table->text('seo_description_template')->nullable()->after('seo_title_template');
            $table->text('h1_template')->nullable()->after('seo_description_template');
            $table->text('content_template')->nullable()->after('h1_template');
        });
    }

    public function down(): void
    {
        Schema::table('section_settings', function (Blueprint $table) {
            $table->dropColumn([
                'seo_title_template',
                'seo_description_template',
                'h1_template',
                'content_template',
            ]);
        });
    }
};
