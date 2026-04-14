<?php

namespace App\Filament\Resources\Specialists;

use App\Filament\Resources\Specialists\Pages\CreateSpecialist;
use App\Filament\Resources\Specialists\Pages\EditSpecialist;
use App\Filament\Resources\Specialists\Pages\ListSpecialists;
use App\Filament\Resources\Specialists\Schemas\SpecialistForm;
use App\Filament\Resources\Specialists\Tables\SpecialistsTable;
use App\Models\Specialist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SpecialistResource extends Resource
{
    protected static ?string $model = Specialist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Специалисты';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 11;

    public static function getModelLabel(): string
    {
        return 'Специалист';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Специалисты';
    }

    public static function form(Schema $schema): Schema
    {
        return SpecialistForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SpecialistsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSpecialists::route('/'),
            'create' => CreateSpecialist::route('/create'),
            'edit' => EditSpecialist::route('/{record}/edit'),
        ];
    }
}

