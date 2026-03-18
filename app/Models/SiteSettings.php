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
        'about_project_description_1',
        'about_project_description_2',
        'about_project_facts',
        'about_project_team_title',
        'about_project_team_description',
        'about_project_team_items',
        'about_project_approach_title',
        'about_project_approach_description',
        'about_project_approach_items',
        'about_project_reviews_title',
        'about_project_reviews_description',
        'about_project_reviews_items',
        'about_project_seo_title',
        'about_project_seo_description',
        'about_project_page_title',
        'about_project_page_subtitle',
    ];

    protected function casts(): array
    {
        return [
            'navigation' => 'array',
            'footer_menu_1' => 'array',
            'footer_menu_2' => 'array',
            'about_project_facts' => 'array',
            'about_project_team_items' => 'array',
            'about_project_approach_items' => 'array',
            'about_project_reviews_items' => 'array',
        ];
    }

    public static function getInstance(): self
    {
        return static::firstOrCreate([], ['navigation' => null]);
    }
}
