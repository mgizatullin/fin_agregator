<?php

namespace App\Filament\Resources\Deposits\Tables;

use App\Models\Deposit;
use App\Services\DepositConditionsMapper\DepositCurrencySummary;
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

                TextColumn::make('rate_range')
                    ->label('Ставка')
                    ->state(function (Deposit $record): string {
                        $best = DepositCurrencySummary::bestOfferForDeposit($record);
                        return $best !== null ? rtrim(rtrim(number_format($best['rate'], 2, '.', ''), '0'), '.') . '%' : '—';
                    })
                    ->suffix('')
                    ->toggleable(),

                TextColumn::make('term_range')
                    ->label('Срок (дн.)')
                    ->state(function (Deposit $record): string {
                        $best = DepositCurrencySummary::bestOfferForDeposit($record);
                        return $best !== null ? (string) $best['term_days'] : '—';
                    })
                    ->toggleable(),

                TextColumn::make('min_amount')
                    ->label('Мин. сумма')
                    ->state(function (Deposit $record): string {
                        $best = DepositCurrencySummary::bestOfferForDeposit($record);
                        return $best !== null && $best['amount_min'] !== null ? number_format((float) $best['amount_min'], 0, '.', ' ') : '—';
                    })
                    ->toggleable(),

                TextColumn::make('deposit_type')
                    ->label('Тип вклада')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('capitalization')
                    ->label('Капитализация')
                    ->boolean()
                    ->toggleable(),

                IconColumn::make('online_opening')
                    ->label('Открытие онлайн')
                    ->boolean()
                    ->toggleable(),

                IconColumn::make('monthly_interest_payment')
                    ->label('Выплата % ежемесячно')
                    ->boolean()
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
