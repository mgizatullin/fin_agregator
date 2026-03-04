<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class CreditCategory extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'subtitle',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $category): void {
            if (blank($category->title)) {
                return;
            }
            if ($category->isDirty('title') || blank($category->slug)) {
                $category->slug = static::generateUniqueSlug($category->title, $category->id);
            }
        });
    }

    public function credits(): BelongsToMany
    {
        return $this->belongsToMany(Credit::class, 'credit_credit_category');
    }

    protected static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'category';
        $slug = $baseSlug;
        $counter = 1;
        while (
            static::query()
                ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }
        return $slug;
    }
}
