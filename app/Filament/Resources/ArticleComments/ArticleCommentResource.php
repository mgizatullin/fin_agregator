<?php

namespace App\Filament\Resources\ArticleComments;

use App\Filament\Resources\ArticleComments\Pages\ListArticleComments;
use App\Filament\Resources\ArticleComments\Pages\EditArticleComment;
use App\Filament\Resources\ArticleComments\Schemas\ArticleCommentForm;
use App\Models\ArticleComment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Schemas\Schema;

class ArticleCommentResource extends Resource
{
    protected static ?string $model = ArticleComment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleOvalLeftEllipsis;

    protected static ?string $navigationLabel = 'Комментарии';

    protected static string|\UnitEnum|null $navigationGroup = 'Контент';

    protected static ?int $navigationSort = 11;

    public static function getModelLabel(): string
    {
        return 'Комментарий';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Комментарии';
    }

    public static function form(Schema $schema): Schema
    {
        return ArticleCommentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return \App\Filament\Resources\ArticleComments\Tables\ArticleCommentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticleComments::route('/'),
            'edit' => EditArticleComment::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

