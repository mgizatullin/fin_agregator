<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Credit extends Model
{
    protected $fillable = [
        'bank_id',
        'name',
        'review_rating',
        'review_count',
        'slug',
        'rate',
        'rate_min',
        'rate_max',
        'psk',
        'max_amount',
        'min_amount',
        'term_months',
        'min_term_months',
        'max_term_months',
        'income_proof_required',
        'age_min',
        'age_max',
        'decision',
        'receive_method',
        'payment_type',
        'penalty',
        'no_collateral',
        'no_guarantors',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'bank_id' => 'integer',
            'review_rating' => 'decimal:2',
            'review_count' => 'integer',
            'rate' => 'decimal:2',
            'rate_min' => 'decimal:2',
            'rate_max' => 'decimal:2',
            'psk' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'min_amount' => 'decimal:2',
            'term_months' => 'integer',
            'min_term_months' => 'integer',
            'max_term_months' => 'integer',
            'income_proof_required' => 'boolean',
            'age_min' => 'integer',
            'age_max' => 'integer',
            'no_collateral' => 'boolean',
            'no_guarantors' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $credit): void {
            if (blank($credit->name)) {
                return;
            }

            if ($credit->isDirty('name') || blank($credit->slug)) {
                $credit->slug = static::generateUniqueSlug($credit->name, $credit->id);
            }
        });
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable')->latest();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CreditCategory::class, 'credit_credit_category');
    }

    public function receiveMethods(): BelongsToMany
    {
        return $this->belongsToMany(CreditReceiveMethod::class, 'credit_credit_receive_method');
    }

    protected static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'credit';
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
