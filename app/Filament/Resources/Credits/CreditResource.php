<?php

namespace App\Filament\Resources\Credits;

use App\Filament\Resources\Credits\Pages\CreateCredit;
use App\Filament\Resources\Credits\Pages\EditCredit;
use App\Filament\Resources\Credits\Pages\ListCredits;
use App\Filament\Resources\Credits\Schemas\CreditForm;
use App\Filament\Resources\Credits\Tables\CreditsTable;
use App\Models\Credit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CreditResource extends Resource
{
    protected static ?string $model = Credit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Кредиты';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return 'Кредит';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Кредиты';
    }

    public static function form(Schema $schema): Schema
    {
        return CreditForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CreditsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCredits::route('/'),
            'create' => CreateCredit::route('/create'),
            'edit' => EditCredit::route('/{record}/edit'),
        ];
    }
}
