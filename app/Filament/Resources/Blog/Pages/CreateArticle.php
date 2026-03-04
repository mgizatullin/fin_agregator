<?php

namespace App\Filament\Resources\Blog\Pages;

use App\Filament\Resources\Blog\BlogResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateArticle extends CreateRecord
{
    protected static string $resource = BlogResource::class;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);
        if (isset($data['image']) && is_array($data['image'])) {
            $data['image'] = Arr::first($data['image']) ?: null;
        }
        return $data;
    }
}
