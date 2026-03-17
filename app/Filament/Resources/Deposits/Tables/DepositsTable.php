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
            ->modifyQueryUsing(fn ($query) => $query->with(['currencies.conditions']))
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

                TextColumn::make('currencies_list')
                    ->label('Валюты')
                    ->state(function (Deposit $record): string {
                        $codes = $record->currencies->pluck('currency_code')->filter()->values();
                        return $codes->isNotEmpty() ? $codes->implode(', ') : '—';
                    })
                    ->toggleable(),

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
                        $currency = $record->currencies->firstWhere('currency_code', 'RUB') ?? $record->currencies->first();
                        if (! $currency) {
                            return '—';
                        }
                        $active = $currency->conditions->where('is_active', true)->values();
                        $amountMins = $active->pluck('amount_min')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);
                        if ($amountMins->isEmpty()) {
                            return '—';
                        }
                        return number_format($amountMins->min(), 0, '.', ' ');
                    })
                    ->toggleable(),

                TextColumn::make('max_amount')
                    ->label('Макс. сумма')
                    ->state(function (Deposit $record): string {
                        $currency = $record->currencies->firstWhere('currency_code', 'RUB') ?? $record->currencies->first();
                        if (! $currency) {
                            return '—';
                        }
                        $active = $currency->conditions->where('is_active', true)->values();
                        $cappingMaxes = $active
                            ->filter(fn ($c) => $c->amount_max !== null && ($c->amount_min === null || (float) $c->amount_max > (float) $c->amount_min))
                            ->pluck('amount_max')
                            ->map(fn ($v) => (float) $v);
                        $highestCap = $cappingMaxes->isNotEmpty() ? $cappingMaxes->max() : null;
                        $hasUnboundedTierAboveCap = $highestCap !== null && $active->contains(
                            fn ($c) => $c->amount_max === null && ($c->amount_min === null || (float) $c->amount_min >= $highestCap)
                        );
                        if ($hasUnboundedTierAboveCap || $highestCap === null) {
                            return '—';
                        }
                        return number_format($highestCap, 0, '.', ' ');
                    })
                    ->toggleable(),

                TextColumn::make('deposit_type')
                    ->label('Тип вклада')
                    ->sortable()
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
