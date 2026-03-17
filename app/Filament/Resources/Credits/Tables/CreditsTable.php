<?php

namespace App\Filament\Resources\Credits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CreditsTable
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

                TextColumn::make('rate_min')
                    ->label('Ставка мин')
                    ->numeric()
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('rate_max')
                    ->label('Ставка макс')
                    ->numeric()
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('psk')
                    ->label('ПСК')
                    ->numeric()
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('max_amount')
                    ->label('Макс. сумма')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('term_months')
                    ->label('Срок (мес.)')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('income_proof_required')
                    ->label('Подтверждение дохода')
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
                TernaryFilter::make('income_proof_required')
                    ->label('Подтверждение дохода'),
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
