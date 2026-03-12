<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepositCurrency extends Model
{
    protected $fillable = [
        'deposit_id',
        'currency_code',
        'min_amount',
        'max_amount',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'deposit_id' => 'integer',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(DepositCondition::class)->orderBy('sort_order');
    }
}
