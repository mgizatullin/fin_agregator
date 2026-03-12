<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Review extends Model
{
    protected $appends = ['reviewable_label'];

    protected $fillable = [
        'reviewable_type',
        'reviewable_id',
        'bank_id',
        'service',
        'title',
        'body',
        'rating',
        'name',
        'email',
        'phone',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'bank_id' => 'integer',
            'rating' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function getReviewableLabelAttribute(): string
    {
        $r = $this->reviewable;
        if (! $r) {
            return '—';
        }
        $type = match ($this->reviewable_type) {
            \App\Models\Credit::class => 'Кредит',
            \App\Models\Card::class => 'Карта',
            \App\Models\Loan::class => 'Займ',
            \App\Models\Bank::class => 'Банк',
            \App\Models\Deposit::class => 'Вклад',
            default => class_basename($this->reviewable_type),
        };
        return $type . ': ' . ($r->name ?? $r->title ?? '—');
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }
}
