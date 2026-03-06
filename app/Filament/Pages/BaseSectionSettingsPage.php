<?php

namespace App\Filament\Pages;

use App\Models\SectionSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Throwable;

abstract class BaseSectionSettingsPage extends Page
{
    use CanUseDatabaseTransactions;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Контент';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    protected string $view = 'filament.pages.section-settings';

    abstract protected static function sectionType(): string;

    /**
     * @return class-string<Model>
     */
    abstract protected static function categoryClass(): string;

    abstract protected static function resourceListUrl(): string;

    abstract protected static function sectionLabel(): string;

    public static function getNavigationLabel(): string
    {
        return 'Настройки раздела «' . static::sectionLabel() . '»';
    }

    public function getTitle(): string
    {
        return 'Настройки раздела «' . static::sectionLabel() . '»';
    }

    public function mount(): void
    {
        $this->fillForm();
    }

    protected function getSetting(): SectionSetting
    {
        return SectionSetting::getOrCreateForType(static::sectionType());
    }

    protected function fillForm(): void
    {
        $setting = $this->getSetting();
        $data = $setting->attributesToArray();
        $data['advantages'] = collect($setting->advantages ?? [])->map(function ($item) {
            if (! is_array($item)) {
                return ['title' => $item, 'description' => '', 'image' => []];
            }
            $img = $item['image'] ?? null;
            return [
                'title' => $item['title'] ?? $item['text'] ?? '',
                'description' => $item['description'] ?? '',
                'image' => $img ? (is_array($img) ? $img : [$img]) : [],
            ];
        })->values()->toArray();
        $categoryClass = static::categoryClass();
        $categories = $categoryClass::orderBy('sort_order')->orderBy('id')->get();
        $data['categories'] = $categories->map(fn (Model $c) => [
            'id' => $c->getKey(),
            'title' => $c->getAttribute('title'),
            'slug' => $c->getAttribute('slug'),
            'subtitle' => $c->getAttribute('subtitle'),
            'description' => description_to_html((string) ($c->getAttribute('description') ?? '')),
            'h1_template' => $c->getAttribute('h1_template'),
            'seo_title_template' => $c->getAttribute('seo_title_template'),
            'seo_description_template' => $c->getAttribute('seo_description_template'),
            'sort_order' => $c->getAttribute('sort_order'),
        ])->toArray();
        $this->form->fill($data);
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();
            $this->callHook('beforeValidate');
            $data = $this->form->getState();
            $this->callHook('afterValidate');
            $this->callHook('beforeSave');

            $setting = $this->getSetting();
            $advantages = Arr::pull($data, 'advantages', []);
            $categoriesData = Arr::pull($data, 'categories', []);

            $setting->update(array_merge(Arr::except($data, ['id', 'created_at', 'updated_at']), [
                'advantages' => collect($advantages)->map(function ($a) {
                    $img = $a['image'] ?? null;
                    $path = is_array($img) ? (Arr::first($img) ?: null) : $img;
                    return [
                        'title' => $a['title'] ?? '',
                        'description' => $a['description'] ?? '',
                        'image' => $path,
                    ];
                })->values()->toArray(),
            ]));

            $categoryClass = static::categoryClass();
            $existingIds = [];
            foreach ($categoriesData as $index => $item) {
                $payload = [
                    'title' => $item['title'] ?? '',
                    'slug' => $item['slug'] ?? null,
                    'subtitle' => $item['subtitle'] ?? null,
                    'description' => description_ensure_html($item['description'] ?? ''),
                    'h1_template' => $item['h1_template'] ?? null,
                    'seo_title_template' => $item['seo_title_template'] ?? null,
                    'seo_description_template' => $item['seo_description_template'] ?? null,
                    'sort_order' => $index,
                ];
                if (! empty($item['id'])) {
                    $cat = $categoryClass::find($item['id']);
                    if ($cat) {
                        $cat->update($payload);
                        $existingIds[] = $cat->id;
                        continue;
                    }
                }
                $payload['slug'] = $payload['slug'] ?? \Illuminate\Support\Str::slug($payload['title']);
                $newCat = $categoryClass::create($payload);
                $existingIds[] = $newCat->id;
            }
            $categoryClass::whereNotIn('id', $existingIds)->delete();

            $this->callHook('afterSave');
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction()
                ? $this->rollBackDatabaseTransaction()
                : $this->commitDatabaseTransaction();
            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();
            throw $exception;
        }
        $this->commitDatabaseTransaction();
        Notification::make()
            ->success()
            ->title('Настройки раздела сохранены')
            ->send();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->model($this->getSetting())
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $listUrl = static::resourceListUrl();
        $label = static::sectionLabel();
        return $schema
            ->components([
                Tabs::make('tabs')
                    ->tabs([
                        Tab::make('Список')
                            ->schema([
                                Section::make('Список элементов')
                                    ->description('Управление элементами раздела «' . $label . '»')
                                    ->schema([
                                        \Filament\Schemas\Components\Text::make('Перейдите к списку для добавления и редактирования элементов.')
                                            ->columnSpanFull(),
                                        \Filament\Schemas\Components\Actions::make([
                                            Action::make('goToList')
                                                ->label('Открыть список')
                                                ->url($listUrl)
                                                ->openUrlInNewTab(false)
                                                ->color('primary'),
                                        ]),
                                    ])
                                    ->columns(1),
                            ]),

                        Tab::make('Редактирование')
                            ->schema([
                                Section::make('Текст раздела')
                                    ->schema([
                                        TextInput::make('title')
                                            ->label('Заголовок')
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        TextInput::make('subtitle')
                                            ->label('Подзаголовок')
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        Textarea::make('description')
                                            ->label('Описание')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                        Repeater::make('advantages')
                                            ->label('Преимущества')
                                            ->schema([
                                                TextInput::make('title')
                                                    ->label('Заголовок')
                                                    ->maxLength(255)
                                                    ->columnSpanFull(),
                                                Textarea::make('description')
                                                    ->label('Описание')
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                                FileUpload::make('image')
                                                    ->label('Картинка')
                                                    ->image()
                                                    ->directory('section-advantages/' . static::sectionType())
                                                    ->disk('public')
                                                    ->maxSize(2048)
                                                    ->columnSpanFull(),
                                            ])
                                            ->defaultItems(0)
                                            ->addActionLabel('Добавить преимущество')
                                            ->reorderable()
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1),
                            ]),

                        Tab::make('Категории')
                            ->schema([
                                Section::make('Категории раздела')
                                    ->description('Переменные шаблонов: {service_name}, {category_name}, {city}, {city.g}, {city.p}. Условия: [if city]...[/if]')
                                    ->schema([
                                        Repeater::make('categories')
                                            ->label('Категории')
                                            ->schema([
                                                TextInput::make('title')
                                                    ->label('Название')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug((string) $state))),
                                                TextInput::make('slug')
                                                    ->label('Slug')
                                                    ->maxLength(255),
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
                                                    ->columnSpanFull(),
                                                TextInput::make('h1_template')
                                                    ->label('Шаблон H1')
                                                    ->maxLength(255)
                                                    ->placeholder('Кредиты онлайн на карту[if city.p] в {city.p}[/if]'),
                                                TextInput::make('seo_title_template')
                                                    ->label('Шаблон SEO Title')
                                                    ->maxLength(255),
                                                Textarea::make('seo_description_template')
                                                    ->label('Шаблон SEO Description')
                                                    ->rows(3),
                                            ])
                                            ->defaultItems(0)
                                            ->addActionLabel('Добавить категорию')
                                            ->reorderable()
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1),
                            ]),

                        Tab::make('SEO настройки')
                            ->schema([
                                Section::make('SEO')
                                    ->schema([
                                        TextInput::make('seo_title')
                                            ->label('SEO Title')
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        Textarea::make('seo_description')
                                            ->label('SEO Description')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1),
                            ]),

                        Tab::make('Мультигородность')
                            ->schema([
                                Section::make('Шаблоны для страниц с городом в URL')
                                    ->description('Переменные: {service_name}, {city}, {city.g} (родительный), {city.p} (предложный). Пример: {service_name} в {city.p}')
                                    ->schema([
                                        TextInput::make('seo_title_template')
                                            ->label('Шаблон SEO Title')
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        Textarea::make('seo_description_template')
                                            ->label('Шаблон SEO Description')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                        TextInput::make('h1_template')
                                            ->label('Шаблон H1')
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        Textarea::make('content_template')
                                            ->label('Шаблон текста')
                                            ->rows(6)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Сохранить')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::Start;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->alignment($this->getFormActionsAlignment())
                            ->key('form-actions'),
                    ]),
            ]);
    }
}
