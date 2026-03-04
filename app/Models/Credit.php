<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Credit extends Model
{
    protected $fillable = [
        'bank_id',
        'name',
        'slug',
        'rate',
        'psk',
        'max_amount',
        'term_months',
        'income_proof_required',
        'age_min',
        'age_max',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'bank_id' => 'integer',
            'rate' => 'decimal:2',
            'psk' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'term_months' => 'integer',
            'income_proof_required' => 'boolean',
            'age_min' => 'integer',
            'age_max' => 'integer',
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

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CreditCategory::class, 'credit_credit_category');
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
