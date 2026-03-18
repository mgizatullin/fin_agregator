<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialist extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'position',
        'short_description',
        'photo',
    ];
}

