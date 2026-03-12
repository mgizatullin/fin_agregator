<?php

namespace App\Filament\Resources\Reviews;

use App\Filament\Resources\Reviews\Pages\EditReview;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\Reviews\Schemas\ReviewForm;
use App\Filament\Resources\Reviews\Tables\ReviewsTable;
use App\Models\Review;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Отзывы';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 10;

    public static function getModelLabel(): string
    {
        return 'Отзыв';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Отзывы';
    }

    public static function form(Schema $schema): Schema
    {
        return ReviewForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReviewsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
            'edit' => EditReview::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true;
    }
}
