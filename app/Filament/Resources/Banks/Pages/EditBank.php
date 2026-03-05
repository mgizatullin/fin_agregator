<?php

namespace App\Filament\Resources\Banks\Pages;

use App\Filament\Resources\Banks\BankResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBank extends EditRecord
{
    protected static string $resource = BankResource::class;

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
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('description', $data)) {
            $data['description'] = description_ensure_html($data['description'] ?? '');
        }
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (array_key_exists('description', $data)) {
            $data['description'] = description_ensure_html($data['description'] ?? '');
        }
        return parent::handleRecordUpdate($record, $data);
    }
}
