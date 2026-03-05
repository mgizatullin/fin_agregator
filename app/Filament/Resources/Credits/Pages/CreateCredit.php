<?php

namespace App\Filament\Resources\Credits\Pages;

use App\Filament\Resources\Credits\CreditResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCredit extends CreateRecord
{
    protected static string $resource = CreditResource::class;

    /** @var array<int>|null */
    protected ?array $categoryIdsToSync = null;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        if (array_key_exists('description', $data)) {
            $data['description'] = description_ensure_html($data['description'] ?? '');
        }
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
