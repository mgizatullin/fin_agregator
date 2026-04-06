<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactRequest extends Model
{
    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'message',
        'personal_data_consent',
    ];

    protected function casts(): array
    {
        return [
            'personal_data_consent' => 'boolean',
        ];
    }
}
