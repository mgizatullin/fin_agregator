<?php

namespace App\Filament\Resources\Cards\Pages;

use App\Filament\Resources\Cards\CardResource;
use App\Support\CardData;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateCard extends CreateRecord
{
    protected static string $resource = CardResource::class;

    /** @var array<int>|null */
    protected ?array $categoryIdsToSync = null;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        if (array_key_exists('description', $data)) {
            $data['description'] = description_ensure_html($data['description'] ?? '');
        }

        if (isset($data['image']) && is_array($data['image'])) {
            $data['image'] = Arr::first($data['image']) ?: null;
        }

        $data['conditions_items'] = CardData::normalizeDetailItems($data['conditions_items'] ?? []);
        $data['rates_items'] = CardData::normalizeDetailItems($data['rates_items'] ?? []);
        $data['cashback_details_items'] = CardData::normalizeDetailItems($data['cashback_details_items'] ?? []);

        $raw = $data['categories'] ?? [];
        $this->categoryIdsToSync = collect($raw)->map(fn ($v) => is_object($v) ? (int) $v->getKey() : (int) $v)->filter()->values()->all();
        unset($data['categories']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->categoryIdsToSync !== null) {
            $this->record->categories()->sync($this->categoryIdsToSync);
            $this->categoryIdsToSync = null;
        }
    }
}
