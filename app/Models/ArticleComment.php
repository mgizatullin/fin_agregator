<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleComment extends Model
{
    protected $fillable = [
        'article_id',
        'name',
        'body',
        'is_published',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'article_id' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}

