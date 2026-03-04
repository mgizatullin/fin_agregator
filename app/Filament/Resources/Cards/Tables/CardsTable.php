<?php

namespace App\Filament\Resources\Cards\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CardsTable
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

                TextColumn::make('credit_limit')
                    ->label('Кредитный лимит')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('grace_period')
                    ->label('Льготный период (дней)')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('annual_fee')
                    ->label('Годовое обслуживание')
                    ->numeric()
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

                IconColumn::make('atm_withdrawal')
                    ->label('Снятие в банкомате')
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
                TernaryFilter::make('atm_withdrawal')
                    ->label('Снятие в банкомате'),
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
