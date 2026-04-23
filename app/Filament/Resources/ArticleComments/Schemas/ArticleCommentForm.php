<?php

namespace App\Filament\Resources\ArticleComments\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ArticleCommentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Имя')
                    ->required()
                    ->maxLength(255),

                Textarea::make('body')
                    ->label('Комментарий')
                    ->required()
                    ->rows(8)
                    ->maxLength(5000),

                Toggle::make('is_published')
                    ->label('Опубликован')
                    ->default(false),
            ]);
    }
}

