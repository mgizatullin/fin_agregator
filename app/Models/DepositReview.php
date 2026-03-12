<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositReview extends Model
{
    protected $fillable = [
        'deposit_id',
        'bank_id',
        'service',
        'title',
        'body',
        'rating',
        'name',
        'email',
        'phone',
    ];

    protected function casts(): array
    {
        return [
            'deposit_id' => 'integer',
            'bank_id' => 'integer',
            'rating' => 'integer',
        ];
    }

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }
}

