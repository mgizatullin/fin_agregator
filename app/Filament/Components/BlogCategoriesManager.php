<?php

namespace App\Filament\Components;

use App\Models\Category;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Illuminate\Support\Str;

class BlogCategoriesManager extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Category::query())
            ->columns([
                TextColumn::make('name')
                    ->label('Заголовок')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('URL')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('description')
                    ->label('Описание')
                    ->limit(50)
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Добавить категорию')
                    ->form($this->getCategoryFormSchema())
                    ->using(function (array $data): Model {
                        return Category::create($data);
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->label('Редактировать')
                    ->form($this->getCategoryFormSchema()),
                DeleteAction::make()
                    ->label('Удалить'),
            ])
            ->defaultSort('name');
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    protected function getCategoryFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('Заголовок')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (?string $state, callable $set) => $set('slug', Str::slug((string) $state))),

            TextInput::make('slug')
                ->label('URL')
                ->required()
                ->maxLength(255)
                ->unique(Category::class, 'slug', ignoreRecord: true)
                ->helperText('Формируется автоматически из заголовка, можно изменить вручную.'),

            Textarea::make('description')
                ->label('Описание')
                ->rows(4)
                ->columnSpanFull(),

            Toggle::make('is_active')
                ->label('Активна')
                ->default(true),
        ];
    }

    public function render()
    {
        return view('filament.components.section-categories-manager');
    }
}
