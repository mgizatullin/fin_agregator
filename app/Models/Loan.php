<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Loan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'company_name',
        'logo',
        'description',
        'max_amount',
        'term_days',
        'term_no_interest',
        'psk',
        'rate',
        'category',
        'website',
        'rating',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_amount' => 'decimal:2',
            'term_days' => 'integer',
            'term_no_interest' => 'integer',
            'psk' => 'decimal:2',
            'rate' => 'decimal:2',
            'rating' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $loan): void {
            if (blank($loan->name)) {
                return;
            }

            if ($loan->isDirty('name') || blank($loan->slug)) {
                $loan->slug = static::generateUniqueSlug($loan->name, $loan->id);
            }
        });
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable')->latest();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(LoanCategory::class, 'loan_loan_category');
    }

    protected static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'loan';
        $slug = $baseSlug;
        $counter = 1;

        while (
            static::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
