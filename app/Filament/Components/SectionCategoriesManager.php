<?php

namespace App\Filament\Components;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Component;

class SectionCategoriesManager extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    /**
     * @var class-string<Model>
     */
    public string $modelClass;

    public function mount(string $modelClass): void
    {
        $this->modelClass = $modelClass;
    }

    public function table(Table $table): Table
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $this->modelClass;

        return $table
            ->query(fn (): Builder => $modelClass::query())
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('subtitle')
                    ->label('Подзаголовок')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Добавить категорию')
                    ->form($this->getCategoryFormSchema())
                    ->using(function (array $data, HasActions $livewire): Model {
                        $data['description'] = description_ensure_html($data['description'] ?? '');
                        $modelClass = $livewire->modelClass;
                        $data['sort_order'] = $modelClass::max('sort_order') + 1;
                        $data['slug'] = $data['slug'] ?? Str::slug($data['title'] ?? '');
                        return $modelClass::create($data);
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->label('Редактировать')
                    ->form($this->getCategoryFormSchema())
                    ->mutateRecordDataUsing(function (array $data): array {
                        if (isset($data['description']) && is_string($data['description'])) {
                            $data['description'] = description_to_html($data['description']);
                        }
                        return $data;
                    }),
                DeleteAction::make()
                    ->label('Удалить'),
            ]);
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    protected function getCategoryFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->label('Заголовок')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (?string $state, callable $set) => $set('slug', Str::slug((string) $state))),

            TextInput::make('slug')
                ->label('Slug')
                ->maxLength(255)
                ->helperText('Формируется автоматически, можно изменить вручную.'),

            TextInput::make('subtitle')
                ->label('Подзаголовок')
                ->maxLength(255),

            RichEditor::make('description')
                ->label('Описание')
                ->toolbarButtons([
                    ['bold', 'italic', 'link'],
                    ['h2', 'h3'],
                    ['bulletList', 'orderedList'],
                ])
                ->extraInputAttributes(['style' => 'min-height: 300px'])
                ->columnSpanFull(),

            TextInput::make('h1_template')
                ->label('Шаблон H1')
                ->maxLength(255)
                ->placeholder('Кредиты онлайн на карту[if city.p] в {city.p}[/if]')
                ->helperText('Переменные: {service_name}, {category_name}, {city}, {city.g}, {city.p}. Условия: [if city]...[/if]'),

            TextInput::make('seo_title_template')
                ->label('Шаблон SEO Title')
                ->maxLength(255),

            Textarea::make('seo_description_template')
                ->label('Шаблон SEO Description')
                ->rows(3),
        ];
    }

    public function render()
    {
        return view('filament.components.section-categories-manager');
    }
}

