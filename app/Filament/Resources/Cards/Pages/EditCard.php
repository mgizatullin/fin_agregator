<?php

namespace App\Filament\Resources\Cards\Pages;

use App\Filament\Resources\Cards\CardResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCard extends EditRecord
{
    protected static string $resource = CardResource::class;

    /** @var array<int>|null */
    protected ?array $categoryIdsToSync = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        $raw = $data['categories'] ?? [];
        $this->categoryIdsToSync = collect($raw)->map(fn ($v) => is_object($v) ? (int) $v->getKey() : (int) $v)->filter()->values()->all();
        unset($data['categories']);
        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->categoryIdsToSync !== null) {
            $this->record->categories()->sync($this->categoryIdsToSync);
            $this->categoryIdsToSync = null;
        }
    }
}
