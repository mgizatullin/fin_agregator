<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSettings extends Model
{
    protected $table = 'site_settings';

    protected $fillable = [
        'navigation',
    ];

    protected function casts(): array
    {
        return [
            'navigation' => 'array',
        ];
    }

    public static function getInstance(): self
    {
        return static::firstOrCreate([], ['navigation' => null]);
    }
}
