<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    public const TYPE_CREDIT_CALCULATOR = 'credit_calculator';

    public const TYPE_DEPOSIT_CALCULATOR = 'deposit_calculator';

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
            default => $this->type,
        };
    }
}
