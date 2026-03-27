<?php

namespace App\Filament\Resources\Loans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class LoansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('URL-код')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('company_name')
                    ->label('Компания')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('max_amount')
                    ->label('Макс. сумма')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('term_days')
                    ->label('Срок (дней)')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('psk')
                    ->label('ПСК')
                    ->numeric()
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('rate')
                    ->label('Ставка')
                    ->numeric()
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активен'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
