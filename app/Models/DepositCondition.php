<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositCondition extends Model
{
    protected $fillable = [
        'deposit_currency_id',
        'term_days_min',
        'term_days_max',
        'amount_min',
        'amount_max',
        'rate',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'deposit_currency_id' => 'integer',
            'term_days_min' => 'integer',
            'term_days_max' => 'integer',
            'amount_min' => 'decimal:2',
            'amount_max' => 'decimal:2',
            'rate' => 'decimal:3',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function depositCurrency(): BelongsTo
    {
        return $this->belongsTo(DepositCurrency::class);
    }
}
