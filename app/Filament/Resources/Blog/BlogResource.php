<?php

namespace App\Filament\Resources\Blog;

use App\Filament\Resources\Blog\Pages\CreateArticle;
use App\Filament\Resources\Blog\Pages\EditArticle;
use App\Filament\Resources\Blog\Pages\ListBlog;
use App\Filament\Resources\Articles\Schemas\ArticleForm;
use App\Filament\Resources\Articles\Tables\ArticlesTable;
use App\Models\Article;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BlogResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Блог';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'blog';

    public static function getModelLabel(): string
    {
        return 'Статья';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Статьи';
    }

    public static function form(Schema $schema): Schema
    {
        return ArticleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArticlesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlog::route('/'),
            'create' => CreateArticle::route('/create'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
