<?php

namespace App\Filament\Resources\Loans\Pages;

use App\Filament\Resources\Loans\LoanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditLoan extends EditRecord
{
    protected static string $resource = LoanResource::class;

    /** @var array<int>|null */
    protected ?array $categoryIdsToSync = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function mutateFormDataBeforeFill(array $data): array
    {
        $data['categories'] = $this->record->categories()->pluck('id')->all();
        if (isset($data['description']) && is_string($data['description'])) {
            $data['description'] = description_to_html($data['description']);
        }

        // Для старых записей (где "от" поля ещё пустые) подставляем "от" = текущему "до".
        $data['min_amount'] = $data['min_amount'] ?? null;
        if (blank($data['min_amount']) && filled($this->record->max_amount)) {
            $data['min_amount'] = $this->record->max_amount;
        }

        $data['term_days_min'] = $data['term_days_min'] ?? null;
        if (blank($data['term_days_min']) && filled($this->record->term_days)) {
            $data['term_days_min'] = $this->record->term_days;
        }

        $data['term_no_interest_min'] = $data['term_no_interest_min'] ?? null;
        if (blank($data['term_no_interest_min']) && filled($this->record->term_no_interest)) {
            $data['term_no_interest_min'] = $this->record->term_no_interest;
        }

        $data['psk_min'] = $data['psk_min'] ?? null;
        if (blank($data['psk_min']) && filled($this->record->psk)) {
            $data['psk_min'] = $this->record->psk;
        }

        $data['rate_min'] = $data['rate_min'] ?? null;
        if (blank($data['rate_min']) && filled($this->record->rate)) {
            $data['rate_min'] = $this->record->rate;
        }

        return $data;
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('description', $data)) {
            $data['description'] = description_ensure_html($data['description'] ?? '');
        }
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
