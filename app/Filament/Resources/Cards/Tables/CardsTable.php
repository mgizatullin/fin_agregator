<?php

namespace App\Filament\Resources\Cards\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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

                TextColumn::make('annual_fee_text')
                    ->label('Стоимость обслуживания')
                    ->toggleable(),

                TextColumn::make('psk_text')
                    ->label('ПСК')
                    ->toggleable(),

                TextColumn::make('atm_withdrawal_text')
                    ->label('Снятие в банкомате')
                    ->toggleable(),

                TextColumn::make('card_type')
                    ->label('Тип карты')
                    ->toggleable(),

                TextColumn::make('is_active')
                    ->label('Активен')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Да' : 'Нет')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('bank_id')
                    ->label('Банк')
                    ->relationship('bank', 'name'),
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
