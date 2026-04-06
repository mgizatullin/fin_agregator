<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HomePageSetting extends Model
{
    protected $fillable = [
        'hero_title',
        'hero_description',
        'advantages_block_title',
        'about_title',
        'about_description',
        'about_image',
        'seo_title',
        'seo_description',
        'services',
        'partners',
        'blog_block_title',
        'blog_block_description',
        'blog_block_link_text',
        'faq_title',
        'faq_description',
        'faq_items',
        'main_value_block',
        'values_grid',
        'case_services_title',
        'case_services_description',
        'case_services_items',
    ];

    protected function casts(): array
    {
        return [
            'services' => 'array',
            'partners' => 'array',
            'faq_items' => 'array',
            'main_value_block' => 'array',
            'values_grid' => 'array',
            'case_services_items' => 'array',
        ];
    }

    public function advantages(): HasMany
    {
        return $this->hasMany(HomePageAdvantage::class)->orderBy('sort_order');
    }

    public static function instance(): self
    {
        return static::firstOrCreate([], [
            'hero_title' => '',
            'hero_description' => '',
            'advantages_block_title' => '',
            'about_title' => '',
            'about_description' => '',
            'about_image' => null,
            'seo_title' => null,
            'seo_description' => null,
            'services' => null,
            'partners' => null,
            'blog_block_title' => null,
            'blog_block_description' => null,
            'blog_block_link_text' => null,
            'faq_title' => null,
            'faq_description' => null,
            'faq_items' => null,
            'main_value_block' => null,
            'values_grid' => null,
            'case_services_title' => null,
            'case_services_description' => null,
            'case_services_items' => null,
        ]);
    }
}
