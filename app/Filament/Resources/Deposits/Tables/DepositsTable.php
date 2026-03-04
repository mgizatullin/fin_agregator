<?php

namespace App\Filament\Resources\Deposits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DepositsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('bank.name')
                    ->label('Банк')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('URL-код')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('rate')
                    ->label('Ставка')
                    ->numeric()
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('term_months')
                    ->label('Срок (мес.)')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('min_amount')
                    ->label('Мин. сумма')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('replenishment')
                    ->label('Пополнение')
                    ->boolean()
                    ->toggleable(),

                IconColumn::make('partial_withdrawal')
                    ->label('Частичное снятие')
                    ->boolean()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('bank_id')
                    ->label('Банк')
                    ->relationship('bank', 'name'),
                TernaryFilter::make('replenishment')
                    ->label('Пополнение'),
                TernaryFilter::make('partial_withdrawal')
                    ->label('Частичное снятие'),
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
            ->defaultSort('name');
    }
}
