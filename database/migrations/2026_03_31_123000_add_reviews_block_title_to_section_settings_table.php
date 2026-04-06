<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('section_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('section_settings', 'reviews_block_title')) {
                $table->string('reviews_block_title')->nullable()->after('faq_items');
            }
        });
    }

    public function down(): void
    {
        Schema::table('section_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('section_settings', 'reviews_block_title')) {
                $table->dropColumn('reviews_block_title');
            }
        });
    }
};
