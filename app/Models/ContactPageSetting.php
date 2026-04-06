<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactPageSetting extends Model
{
    protected $fillable = [
        'title',
        'phone',
    ];

    public static function getInstance(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'title' => 'Успех - это командная игра. Давайте работать вместе!',
                'phone' => '+7 (800) 000-00-00',
            ],
        );
    }
}
