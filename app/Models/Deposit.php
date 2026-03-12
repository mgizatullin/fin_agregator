<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Deposit extends Model
{
    protected $fillable = [
        'bank_id',
        'name',
        'slug',
        'deposit_type',
        'capitalization',
        'online_opening',
        'monthly_interest_payment',
        'replenishment',
        'partial_withdrawal',
        'early_termination',
        'auto_prolongation',
        'insurance',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'bank_id' => 'integer',
            'deposit_type' => 'string',
            'capitalization' => 'boolean',
            'online_opening' => 'boolean',
            'monthly_interest_payment' => 'boolean',
            'replenishment' => 'boolean',
            'partial_withdrawal' => 'boolean',
            'early_termination' => 'boolean',
            'auto_prolongation' => 'boolean',
            'insurance' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $deposit): void {
            if (blank($deposit->name)) {
                return;
            }
            if ($deposit->isDirty('name') || blank($deposit->slug)) {
                $deposit->slug = static::generateUniqueSlug($deposit->name, $deposit->id);
            }
        });
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(DepositCategory::class, 'deposit_deposit_category');
    }

    public function currencies(): HasMany
    {
        return $this->hasMany(DepositCurrency::class)->orderBy('sort_order');
    }

    protected static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'deposit';
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
