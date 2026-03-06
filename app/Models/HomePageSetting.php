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
        'keywords',
        'main_value_block',
        'values_grid',
    ];

    protected function casts(): array
    {
        return [
            'services' => 'array',
            'partners' => 'array',
            'keywords' => 'array',
            'main_value_block' => 'array',
            'values_grid' => 'array',
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
            'keywords' => null,
            'main_value_block' => null,
            'values_grid' => null,
        ]);
    }
}
