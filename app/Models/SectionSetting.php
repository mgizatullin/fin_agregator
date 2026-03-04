<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SectionSetting extends Model
{
    protected $fillable = [
        'type',
        'title',
        'subtitle',
        'description',
        'advantages',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'advantages' => 'array',
        ];
    }

    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public static function forType(string $type): ?self
    {
        return static::query()->forType($type)->first();
    }

    public static function getOrCreateForType(string $type): self
    {
        return static::firstOrCreate(
            ['type' => $type],
            [
                'title' => null,
                'subtitle' => null,
                'description' => null,
                'advantages' => [],
                'seo_title' => null,
                'seo_description' => null,
            ]
        );
    }
}
