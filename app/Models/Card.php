<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Card extends Model
{
    protected $fillable = [
        'bank_id',
        'name',
        'slug',
        'credit_limit',
        'psk_text',
        'grace_period',
        'grace_period_text',
        'annual_fee',
        'annual_fee_text',
        'psk',
        'rate',
        'cashback',
        'cashback_text',
        'decision_text',
        'issue_cost',
        'atm_withdrawal',
        'atm_withdrawal_text',
        'card_type',
        'image',
        'conditions_text',
        'rates_text',
        'cashback_details_text',
        'conditions_items',
        'rates_items',
        'cashback_details_items',
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
            'conditions_items' => 'array',
            'rates_items' => 'array',
            'cashback_details_items' => 'array',
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

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable')->latest();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CardCategory::class, 'card_card_category');
    }

    /**
     * Заголовок страницы карты: «Карта {название} от {банк}», без дублирующего «Карта »,
     * если в названии уже есть слово «карта» (без учёта регистра).
     */
    public function pageHeadline(): string
    {
        $name = trim((string) ($this->name ?? ''));
        $bankName = trim((string) ($this->bank?->name ?? ''));

        $prefix = ($name !== '' && mb_stripos($name, 'карта') === false) ? 'Карта ' : '';
        $core = $name !== '' ? $prefix.$name : 'Карта';
        $suffix = $bankName !== '' ? ' от '.$bankName : '';

        return trim($core.$suffix);
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
