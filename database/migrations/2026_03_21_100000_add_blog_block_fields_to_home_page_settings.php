<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_page_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('home_page_settings', 'blog_block_title')) {
                $table->string('blog_block_title')->nullable()->after('partners');
            }

            if (! Schema::hasColumn('home_page_settings', 'blog_block_description')) {
                $table->text('blog_block_description')->nullable()->after('blog_block_title');
            }

            if (! Schema::hasColumn('home_page_settings', 'blog_block_link_text')) {
                $table->string('blog_block_link_text')->nullable()->after('blog_block_description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('home_page_settings', function (Blueprint $table) {
            foreach (['blog_block_title', 'blog_block_description', 'blog_block_link_text'] as $column) {
                if (Schema::hasColumn('home_page_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
