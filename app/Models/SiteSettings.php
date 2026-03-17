<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSettings extends Model
{
    protected $table = 'site_settings';

    protected $fillable = [
        'navigation',
        'footer_menu_1',
        'footer_menu_2',
        'footer_heading_1',
        'footer_heading_2',
        'copyright',
        'custom_scripts',
        'logo',
        'footer_under_logo',
        'social_twitter',
        'social_facebook',
        'social_github',
        'social_instagram',
        'social_youtube',
        'social_zen',
        'social_telegram',
    ];

    protected function casts(): array
    {
        return [
            'navigation' => 'array',
            'footer_menu_1' => 'array',
            'footer_menu_2' => 'array',
        ];
    }

    public static function getInstance(): self
    {
        return static::firstOrCreate([], ['navigation' => null]);
    }
}
