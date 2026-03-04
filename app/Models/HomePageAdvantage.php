<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomePageAdvantage extends Model
{
    protected $fillable = [
        'home_page_setting_id',
        'title',
        'description',
        'image',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function homePageSetting(): BelongsTo
    {
        return $this->belongsTo(HomePageSetting::class);
    }
}
