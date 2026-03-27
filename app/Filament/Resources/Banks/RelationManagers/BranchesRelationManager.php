<?php

namespace App\Filament\Resources\Banks\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BranchesRelationManager extends RelationManager
{
    protected static string $relationship = 'branches';

    protected static ?string $title = 'Отделения';

    protected static ?string $modelLabel = 'Отделение';

    protected static ?string $pluralModelLabel = 'Отделения';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('region')
                    ->label('Регион')
                    ->required()
                    ->maxLength(255),

                Select::make('city_id')
                    ->label('Город')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('address')
                    ->label('Адрес')
                    ->required()
                    ->maxLength(255),

                TextInput::make('phone')
                    ->label('Телефон')
                    ->tel()
                    ->maxLength(255),

                TextInput::make('working_hours')
                    ->label('Время работы')
                    ->maxLength(255),

                TextInput::make('latitude')
                    ->label('Широта')
                    ->numeric()
                    ->required(false)
                    ->placeholder('55.7558')
                    ->step('0.000001'),

                TextInput::make('longitude')
                    ->label('Долгота')
                    ->numeric()
                    ->required(false)
                    ->placeholder('37.6173')
                    ->step('0.000001'),

                Toggle::make('is_active')
                    ->label('Активно')
                    ->default(true)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('region')
                    ->label('Регион')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('city.name')
                    ->label('Город')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('address')
                    ->label('Адрес')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('working_hours')
                    ->label('Время работы')
                    ->toggleable(),

                TextColumn::make('latitude')
                    ->label('Широта')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('longitude')
                    ->label('Долгота')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('city_id')
                    ->label('Город')
                    ->relationship('city', 'name'),
                TernaryFilter::make('is_active')
                    ->label('Активно'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('address');
    }
}
