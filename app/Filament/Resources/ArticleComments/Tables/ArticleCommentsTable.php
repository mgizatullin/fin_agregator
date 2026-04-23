<?php

namespace App\Filament\Resources\ArticleComments\Tables;

use App\Models\ArticleComment;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ArticleCommentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('article'))
            ->columns([
                IconColumn::make('is_published')
                    ->label('Опубликован')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('article.title')
                    ->label('Статья')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable(),

                TextColumn::make('body')
                    ->label('Комментарий')
                    ->limit(80)
                    ->wrap(),

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
            ], layout: FiltersLayout::AboveContent)
            ->recordUrl(fn (ArticleComment $record): string => route('filament.admin.resources.article-comments.edit', $record))
            ->recordActions([
                EditAction::make(),
                Action::make('openOnSite')
                    ->label('На сайт')
                    ->url(fn (ArticleComment $record): string => $record->article ? url_section('blog/' . $record->article->slug) . '#comments' : '#')
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
}

