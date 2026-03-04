<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['image']) && is_array($data['image'])) {
            $data['image'] = Arr::first($data['image']) ?: null;
        }
        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['image'] = ! empty($this->record->image) ? [$this->record->image] : [];
        return $data;
    }
}
