<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Bank extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'logo',
        'logo_square',
        'description',
        'license_number',
        'license_date',
        'website',
        'phone',
        'head_office',
        'seo_title',
        'seo_description',
        'rating',
        'is_active',
        'is_online_bank',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'decimal:2',
            'license_date' => 'date',
            'is_active' => 'boolean',
            'is_online_bank' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $bank): void {
            if (blank($bank->name)) {
                return;
            }

            if ($bank->isDirty('name') || blank($bank->slug)) {
                $bank->slug = static::generateUniqueSlug($bank->name, $bank->id);
            }
        });
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable')->latest();
    }

    /** Отзывы, где банк указан в поле bank_id (для фильтров и подсчёта). */
    public function reviewsAsBank(): HasMany
    {
        return $this->hasMany(Review::class, 'bank_id');
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(BankCategory::class, 'bank_bank_category');
    }

    protected static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'bank';
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
