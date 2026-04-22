<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ParserRun extends Model
{
    protected $fillable = [
        'parser_key',
        'mode',
        'params',
        'stats',
        'result_json',
        'log_output',
    ];

    protected function casts(): array
    {
        return [
            'parser_key' => 'string',
            'mode' => 'string',
            'params' => 'array',
            'stats' => 'array',
            'result_json' => 'string',
            'log_output' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return Builder<self>
     */
    public function scopeForKey(Builder $query, string $parserKey): Builder
    {
        return $query->where('parser_key', $parserKey);
    }
}

