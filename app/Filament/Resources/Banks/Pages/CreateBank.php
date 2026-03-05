<?php

namespace App\Filament\Resources\Banks\Pages;

use App\Filament\Resources\Banks\BankResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBank extends CreateRecord
{
    protected static string $resource = BankResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (array_key_exists('description', $data)) {
            $data['description'] = description_ensure_html($data['description'] ?? '');
        }
        return $data;
    }
}
