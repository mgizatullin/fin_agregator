<?php

namespace App\Filament\Resources\Specialists\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SpecialistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Имя')
                    ->required()
                    ->maxLength(255),

                TextInput::make('position')
                    ->label('Должность')
                    ->maxLength(255),

                Textarea::make('short_description')
                    ->label('Короткое описание')
                    ->rows(4)
                    ->columnSpanFull(),

                FileUpload::make('photo')
                    ->label('Фото')
                    ->image()
                    ->disk('public')
                    ->visibility('public')
                    ->directory('editorial/specialists')
                    ->imagePreviewHeight(120)
                    ->maxSize(2048)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}

