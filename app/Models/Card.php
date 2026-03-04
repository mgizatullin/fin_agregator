<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Card extends Model
{
    protected $fillable = [
        'bank_id',
        'name',
        'slug',
        'credit_limit',
        'grace_period',
        'annual_fee',
        'psk',
        'rate',
        'cashback',
        'issue_cost',
        'atm_withdrawal',
        'card_type',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'bank_id' => 'integer',
            'credit_limit' => 'decimal:2',
            'grace_period' => 'integer',
            'annual_fee' => 'decimal:2',
            'psk' => 'decimal:2',
            'rate' => 'decimal:2',
            'issue_cost' => 'decimal:2',
            'atm_withdrawal' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $card): void {
            if (blank($card->name)) {
                return;
            }

            if ($card->isDirty('name') || blank($card->slug)) {
                $card->slug = static::generateUniqueSlug($card->name, $card->id);
            }
        });
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CardCategory::class, 'card_card_category');
    }

    protected static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'card';
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
