<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'bank_id',
        'city_id',
        'address',
        'phone',
        'working_hours',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'bank_id' => 'integer',
            'city_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
