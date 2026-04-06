<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('section_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('section_settings', 'faq_title')) {
                $table->string('faq_title')->nullable()->after('content_template');
            }

            if (! Schema::hasColumn('section_settings', 'faq_description')) {
                $table->text('faq_description')->nullable()->after('faq_title');
            }

            if (! Schema::hasColumn('section_settings', 'faq_items')) {
                $table->json('faq_items')->nullable()->after('faq_description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('section_settings', function (Blueprint $table): void {
            foreach (['faq_title', 'faq_description', 'faq_items'] as $column) {
                if (Schema::hasColumn('section_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
