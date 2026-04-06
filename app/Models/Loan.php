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
        'min_amount',
        'term_days',
        'term_days_min',
        'term_no_interest',
        'term_no_interest_min',
        'psk',
        'psk_min',
        'rate',
        'rate_min',
        'category',
        'website',
        'rating',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_amount' => 'decimal:2',
            'min_amount' => 'decimal:2',
            'term_days' => 'integer',
            'term_days_min' => 'integer',
            'term_no_interest' => 'integer',
            'term_no_interest_min' => 'integer',
            'psk' => 'decimal:2',
            'psk_min' => 'decimal:2',
            'rate' => 'decimal:2',
            'rate_min' => 'decimal:2',
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

    /**
     * Заголовок страницы займа: «Займ {название} от {МФО}», без дублирующего «Займ »,
     * если в названии уже есть слово «займ» (без учёта регистра).
     */
    public function pageHeadline(): string
    {
        $name = trim((string) ($this->name ?? ''));
        $mfoName = trim((string) ($this->company_name ?? ''));

        $prefix = ($name !== '' && mb_stripos($name, 'займ') === false) ? 'Займ ' : '';
        $core = $name !== '' ? $prefix.$name : 'Займ';
        $suffix = $mfoName !== '' ? ' от '.$mfoName : '';

        return trim($core.$suffix);
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
