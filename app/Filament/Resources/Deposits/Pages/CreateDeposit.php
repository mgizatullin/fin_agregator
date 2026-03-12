<?php

namespace App\Filament\Resources\Deposits\Pages;

use App\Filament\Resources\Deposits\DepositResource;
use App\Services\DepositConditionsMapper\DepositConditionsMapper;
use Filament\Resources\Pages\CreateRecord;

class CreateDeposit extends CreateRecord
{
    protected static string $resource = DepositResource::class;

    /** @var array<int>|null */
    protected ?array $categoryIdsToSync = null;

    /** @var array|null */
    protected ?array $currenciesDataToSync = null;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        if (array_key_exists('description', $data)) {
            $data['description'] = description_ensure_html($data['description'] ?? '');
        }
        $raw = $data['categories'] ?? [];
        $this->categoryIdsToSync = collect($raw)->map(fn ($v) => is_object($v) ? (int) $v->getKey() : (int) $v)->filter()->values()->all();
        unset($data['categories']);
        $this->currenciesDataToSync = $data['currencies'] ?? [];
        unset($data['currencies']);
        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->categoryIdsToSync !== null) {
            $this->record->categories()->sync($this->categoryIdsToSync);
            $this->categoryIdsToSync = null;
        }
        if ($this->currenciesDataToSync !== null) {
            DepositConditionsMapper::fromFormStructure($this->record, $this->currenciesDataToSync);
            $this->currenciesDataToSync = null;
        }
    }
}
