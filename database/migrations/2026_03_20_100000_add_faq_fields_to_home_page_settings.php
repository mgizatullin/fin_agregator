<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_page_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('home_page_settings', 'faq_title')) {
                $table->string('faq_title')->nullable()->after('partners');
            }
            if (! Schema::hasColumn('home_page_settings', 'faq_description')) {
                $table->text('faq_description')->nullable()->after('faq_title');
            }
            if (! Schema::hasColumn('home_page_settings', 'faq_items')) {
                $table->json('faq_items')->nullable()->after('faq_description');
            }
        });

        if (
            Schema::hasColumn('home_page_settings', 'keywords')
            && Schema::hasColumn('home_page_settings', 'faq_items')
        ) {
            DB::table('home_page_settings')
                ->select(['id', 'keywords', 'faq_title', 'faq_items'])
                ->orderBy('id')
                ->get()
                ->each(function ($row) {
                    $faqItems = json_decode($row->faq_items ?? 'null', true);
                    $keywords = json_decode($row->keywords ?? 'null', true);

                    if (! empty($faqItems) || empty($keywords) || ! is_array($keywords)) {
                        return;
                    }

                    $mappedItems = collect($keywords)
                        ->map(function ($item) {
                            $phrase = is_array($item) ? ($item['phrase'] ?? null) : $item;

                            return filled($phrase)
                                ? ['question' => (string) $phrase, 'answer' => '']
                                : null;
                        })
                        ->filter()
                        ->values()
                        ->all();

                    if (empty($mappedItems)) {
                        return;
                    }

                    DB::table('home_page_settings')
                        ->where('id', $row->id)
                        ->update([
                            'faq_title' => $row->faq_title ?: 'Вопросы и ответы',
                            'faq_items' => json_encode($mappedItems, JSON_UNESCAPED_UNICODE),
                        ]);
                });
        }
    }

    public function down(): void
    {
        Schema::table('home_page_settings', function (Blueprint $table) {
            foreach (['faq_title', 'faq_description', 'faq_items'] as $column) {
                if (Schema::hasColumn('home_page_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
