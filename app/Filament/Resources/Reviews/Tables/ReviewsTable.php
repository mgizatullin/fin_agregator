<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Models\Bank;
use App\Models\Review;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['bank', 'reviewable']))
            ->columns([
                IconColumn::make('is_published')
                    ->label('Опубликован')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('service')
                    ->label('Услуга')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('bank.name')
                    ->label('Банк')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('body')
                    ->label('Текст')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('rating')
                    ->label('Оценка')
                    ->badge()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('Опубликован')
                    ->placeholder('Все')
                    ->trueLabel('Опубликованные')
                    ->falseLabel('На модерации'),
                SelectFilter::make('bank_id')
                    ->label('Банк')
                    ->options(function (): array {
                        return Bank::query()
                            ->whereHas('reviewsAsBank')
                            ->withCount('reviewsAsBank')
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (Bank $b) => [$b->id => $b->name . ' (' . $b->reviews_as_bank_count . ')'])
                            ->toArray();
                    })
                    ->searchable(),
                SelectFilter::make('service')
                    ->label('Услуга')
                    ->options(function (): array {
                        return Review::query()
                            ->select('service')
                            ->selectRaw('count(*) as cnt')
                            ->whereNotNull('service')
                            ->where('service', '!=', '')
                            ->groupBy('service')
                            ->orderBy('service')
                            ->get()
                            ->mapWithKeys(fn ($r) => [$r->service => $r->service . ' (' . $r->cnt . ')'])
                            ->toArray();
                    })
                    ->searchable(),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->recordUrl(fn (Review $record): string => route('filament.admin.resources.reviews.edit', $record))
            ->recordActions([
                EditAction::make(),
                Action::make('openOnSite')
                    ->label('На сайт')
                    ->url(fn (Review $record): string => self::reviewableUrl($record))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-arrow-top-right-on-square'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('Опубликовать выбранные')
                        ->icon('heroicon-o-eye')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each->update(['is_published' => true]);
                        }),
                    BulkAction::make('unpublish')
                        ->label('Снять с публикации')
                        ->icon('heroicon-o-eye-slash')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each->update(['is_published' => false]);
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private static function reviewableUrl(Review $record): string
    {
        $reviewable = $record->reviewable;
        if (! $reviewable) {
            return '#';
        }
        $slug = $reviewable->slug ?? null;
        if (! $slug) {
            return '#';
        }
        return match ($record->reviewable_type) {
            \App\Models\Credit::class => url_section('kredity/' . $slug) . '#product-reviews',
            \App\Models\Card::class => url_section('karty/' . $slug) . '#product-reviews',
            \App\Models\Loan::class => url_section('zaimy/' . $slug) . '#product-reviews',
            \App\Models\Bank::class => url_section('banki/' . $slug) . '#product-reviews',
            \App\Models\Deposit::class => url_section('vklady/' . $slug) . '#deposit-reviews',
            default => '#',
        };
    }
}
