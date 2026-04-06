<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    public const TYPE_CREDIT_CALCULATOR = 'credit_calculator';

    public const TYPE_DEPOSIT_CALCULATOR = 'deposit_calculator';

    public const TYPE_CURRENCY_RATES = 'currency_rates';

    protected $fillable = [
        'slug',
        'title',
        'seo_title',
        'seo_description',
        'type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_CREDIT_CALCULATOR => 'Калькулятор кредитов',
            self::TYPE_DEPOSIT_CALCULATOR => 'Калькулятор вкладов',
            self::TYPE_CURRENCY_RATES => 'Курс валют',
            default => $this->type,
        };
    }

    /** Публичный URL страницы «Курсы валют ЦБ» или null, если сервис не заведён или выключен. */
    public static function publicUrlForCurrencyRates(): ?string
    {
        $slug = static::query()
            ->where('type', self::TYPE_CURRENCY_RATES)
            ->where('is_active', true)
            ->value('slug');

        return $slug !== null && $slug !== ''
            ? url('/services/'.$slug)
            : null;
    }
}
