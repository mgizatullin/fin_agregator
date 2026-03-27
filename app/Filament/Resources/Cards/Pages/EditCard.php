<?php

namespace App\Filament\Resources\Cards\Pages;

use App\Filament\Resources\Cards\CardResource;
use App\Support\CardData;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['description']) && is_string($data['description'])) {
            $data['description'] = description_to_html($data['description']);
        }

        $data['image'] = ! empty($this->record->image) && ! str_starts_with($this->record->image, 'http')
            ? [$this->record->image]
            : [];
        $data['conditions_items'] = CardData::normalizeDetailItems($data['conditions_items'] ?? ($data['conditions_text'] ?? null));
        $data['rates_items'] = CardData::normalizeDetailItems($data['rates_items'] ?? ($data['rates_text'] ?? null));
        $data['cashback_details_items'] = CardData::normalizeDetailItems($data['cashback_details_items'] ?? ($data['cashback_details_text'] ?? null));

        return $data;
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('description', $data)) {
            $data['description'] = description_ensure_html($data['description'] ?? '');
        }

        if (isset($data['image']) && is_array($data['image'])) {
            $image = Arr::first($data['image']) ?: null;
            $data['image'] = $image ?: (
                filled($this->record->image) && str_starts_with($this->record->image, 'http')
                    ? $this->record->image
                    : null
            );
        }

        $data['conditions_items'] = CardData::normalizeDetailItems($data['conditions_items'] ?? []);
        $data['rates_items'] = CardData::normalizeDetailItems($data['rates_items'] ?? []);
        $data['cashback_details_items'] = CardData::normalizeDetailItems($data['cashback_details_items'] ?? []);

        $raw = $data['categories'] ?? [];
        $this->categoryIdsToSync = collect($raw)->map(fn ($v) => is_object($v) ? (int) $v->getKey() : (int) $v)->filter()->values()->all();
        unset($data['categories']);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (array_key_exists('description', $data)) {
            $data['description'] = description_ensure_html($data['description'] ?? '');
        }

        return parent::handleRecordUpdate($record, $data);
    }

    protected function afterSave(): void
    {
        if ($this->categoryIdsToSync !== null) {
            $this->record->categories()->sync($this->categoryIdsToSync);
            $this->categoryIdsToSync = null;
        }
    }
}
