<?php

namespace App\Filament\Pages;

use App\Models\HomePageAdvantage;
use App\Models\HomePageSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Throwable;

class HomePageSettings extends Page
{
    use CanUseDatabaseTransactions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Настройки главной';

    protected static ?string $title = 'Настройки главной страницы';

    protected static ?string $slug = 'home-page-settings';

    protected static string|\UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?int $navigationSort = 9;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    protected string $view = 'filament.pages.home-page-settings';

    public function mount(): void
    {
        $this->ensureMigrationsRan();
        $this->fillForm();
    }

    protected function ensureMigrationsRan(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('home_page_settings')) {
            return;
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('migrations')) {
            $migrations = [
                '2026_03_01_120000_create_home_page_settings_table',
                '2026_03_01_120001_create_home_page_advantages_table',
            ];
            foreach ($migrations as $migration) {
                DB::table('migrations')->where('migration', $migration)->delete();
            }
        }
        Artisan::call('migrate', ['--force' => true]);
    }

    protected function getSetting(): HomePageSetting
    {
        return HomePageSetting::instance();
    }

    protected function fillForm(): void
    {
        $setting = $this->getSetting();
        $data = $setting->attributesToArray();
        $data['about_image'] = $setting->about_image ? [$setting->about_image] : [];
        $data['advantages'] = $setting->advantages()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (HomePageAdvantage $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'description' => $a->description,
                'image' => $a->image ? [$a->image] : [],
            ])
            ->toArray();

        $data['services'] = collect($setting->services ?? [])->map(fn ($s) => array_merge($s, [
            'image' => ! empty($s['image']) ? [$s['image']] : [],
        ]))->values()->toArray();

        $partners = $setting->partners ?? ['title' => '', 'items' => []];
        $data['partners_title'] = $partners['title'] ?? '';
        $data['partners_items'] = collect($partners['items'] ?? [])->map(fn ($p) => [
            'logo' => ! empty($p['logo']) ? [$p['logo']] : [],
        ])->values()->toArray();

        $data['keywords'] = $setting->keywords ?? [];

        $mainBlock = $setting->main_value_block ?? [];
        $data['main_value_block'] = is_array($mainBlock) ? $mainBlock : [];
        if (! empty($data['main_value_block']['icon'])) {
            $data['main_value_block']['icon'] = [$data['main_value_block']['icon']];
        } else {
            $data['main_value_block']['icon'] = [];
        }

        $data['values_grid'] = collect($setting->values_grid ?? [])->map(fn ($item) => array_merge($item, [
            'icon' => ! empty($item['icon']) ? [$item['icon']] : [],
        ]))->values()->toArray();

        $this->form->fill($data);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['about_image']) && is_array($data['about_image'])) {
            $data['about_image'] = Arr::first($data['about_image']) ?: null;
        }
        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function normalizeDataForUpdate(array $data): array
    {
        $services = Arr::pull($data, 'services', []);
        $data['services'] = collect($services)->map(function ($item) {
            $img = $item['image'] ?? [];
            $item['image'] = is_array($img) ? (Arr::first($img) ?: null) : $img;
            return Arr::only($item, ['title', 'alt_title', 'description', 'image', 'link']);
        })->values()->toArray();

        $partnersTitle = Arr::pull($data, 'partners_title', '');
        $partnersItems = Arr::pull($data, 'partners_items', []);
        $data['partners'] = [
            'title' => $partnersTitle,
            'items' => collect($partnersItems)->map(function ($p) {
                $logo = $p['logo'] ?? [];
                return ['logo' => is_array($logo) ? (Arr::first($logo) ?: null) : $logo];
            })->values()->toArray(),
        ];

        $data['keywords'] = Arr::pull($data, 'keywords', []);
        $data['keywords'] = collect($data['keywords'])->map(fn ($k) => is_array($k) ? $k : ['phrase' => $k])->values()->toArray();

        $mainBlock = Arr::pull($data, 'main_value_block', []);
        if (is_array($mainBlock) && isset($mainBlock['icon'])) {
            $icon = $mainBlock['icon'];
            $mainBlock['icon'] = is_array($icon) ? (Arr::first($icon) ?: null) : $icon;
        }
        $data['main_value_block'] = Arr::only($mainBlock ?? [], ['title', 'description', 'url', 'icon']);

        $valuesGrid = Arr::pull($data, 'values_grid', []);
        $data['values_grid'] = collect($valuesGrid)->map(function ($item) {
            $icon = $item['icon'] ?? [];
            $item['icon'] = is_array($icon) ? (Arr::first($icon) ?: null) : $icon;
            return Arr::only($item, ['title', 'description', 'url', 'icon']);
        })->take(6)->values()->toArray();

        return $data;
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
            $advantagesData = Arr::pull($data, 'advantages', []);

            $data = $this->normalizeDataForUpdate($data);

            $setting->update(Arr::except($data, ['id', 'created_at', 'updated_at']));

            $existingIds = [];
            foreach ($advantagesData as $index => $item) {
                $itemImage = $item['image'] ?? [];
                $imagePath = is_array($itemImage) ? (Arr::first($itemImage) ?: null) : $itemImage;
                $payload = [
                    'title' => $item['title'] ?? null,
                    'description' => $item['description'] ?? null,
                    'image' => $imagePath,
                    'sort_order' => $index,
                ];
                if (! empty($item['id'])) {
                    $adv = HomePageAdvantage::where('home_page_setting_id', $setting->id)->find($item['id']);
                    if ($adv) {
                        $adv->update($payload);
                        $existingIds[] = $adv->id;
                        continue;
                    }
                }
                $newAdv = $setting->advantages()->create($payload);
                $existingIds[] = $newAdv->id;
            }
            $setting->advantages()->whereNotIn('id', $existingIds)->delete();

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
            ->title('Настройки главной страницы сохранены')
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
        return $schema
            ->components([
                Tabs::make()
                    ->tabs([
                        Tab::make('Основные настройки')
                            ->schema([
                                Section::make('Блок-герой')
                                    ->description('Заголовок и описание в верхнем блоке главной страницы')
                                    ->schema([
                                        TextInput::make('hero_title')
                                            ->label('Текст заголовка')
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Textarea::make('hero_description')
                                            ->label('Описание блока-героя')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Section::make('Блок преимуществ')
                                    ->description('Заголовок блока и список преимуществ')
                                    ->schema([
                                        TextInput::make('advantages_block_title')
                                            ->label('Заголовок блока преимуществ')
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Repeater::make('advantages')
                                            ->label('Преимущества')
                                            ->schema([
                                                TextInput::make('title')
                                                    ->label('Заголовок')
                                                    ->required()
                                                    ->maxLength(255),

                                                Textarea::make('description')
                                                    ->label('Описание')
                                                    ->rows(3),

                                                FileUpload::make('image')
                                                    ->label('Картинка')
                                                    ->image()
                                                    ->directory('home-page/advantages')
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

                                Section::make('Блок о сервисе')
                                    ->description('Заголовок, описание и картинка блока о сервисе')
                                    ->schema([
                                        TextInput::make('about_title')
                                            ->label('Заголовок блока о сервисе')
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Textarea::make('about_description')
                                            ->label('Описание блока о сервисе')
                                            ->rows(5)
                                            ->columnSpanFull(),

                                        FileUpload::make('about_image')
                                            ->label('Картинка для блока о сервисе')
                                            ->image()
                                            ->directory('home-page/about')
                                            ->disk('public')
                                            ->maxSize(2048)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1),
                            ]),

                        Tab::make('SEO настройки')
                            ->schema([
                                Section::make('SEO настройки')
                                    ->schema([
                                        TextInput::make('seo_title')
                                            ->label('SEO Title')
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Textarea::make('seo_description')
                                            ->label('SEO Description')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1),
                            ]),

                        Tab::make('Услуги')
                            ->schema([
                                Section::make('Услуги')
                                    ->description('Список услуг на главной странице')
                                    ->schema([
                                        Repeater::make('services')
                                            ->label('Услуги')
                                            ->schema([
                                                TextInput::make('title')
                                                    ->label('Название')
                                                    ->maxLength(255),

                                                TextInput::make('alt_title')
                                                    ->label('Альтернативное название')
                                                    ->maxLength(255),

                                                Textarea::make('description')
                                                    ->label('Описание')
                                                    ->rows(3),

                                                FileUpload::make('image')
                                                    ->label('Картинка')
                                                    ->image()
                                                    ->directory('home')
                                                    ->disk('public')
                                                    ->maxSize(2048)
                                                    ->columnSpanFull(),

                                                TextInput::make('link')
                                                    ->label('Ссылка')
                                                    ->maxLength(500)
                                                    ->rules(['nullable', 'string', 'max:500', 'regex:/^(\/.*|https?:\/\/.+)$/'])
                                                    ->helperText('Относительный путь (например /kredity) или полный URL (https://...)'),
                                            ])
                                            ->defaultItems(0)
                                            ->addActionLabel('Добавить услугу')
                                            ->reorderable()
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1),
                            ]),

                        Tab::make('Партнеры')
                            ->schema([
                                Section::make('Партнеры')
                                    ->schema([
                                        TextInput::make('partners_title')
                                            ->label('Заголовок блока')
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Repeater::make('partners_items')
                                            ->label('Логотипы партнёров')
                                            ->schema([
                                                FileUpload::make('logo')
                                                    ->label('Логотип')
                                                    ->image()
                                                    ->directory('home')
                                                    ->disk('public')
                                                    ->maxSize(2048)
                                                    ->columnSpanFull(),
                                            ])
                                            ->defaultItems(0)
                                            ->addActionLabel('Добавить логотип')
                                            ->reorderable()
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1),
                            ]),

                        Tab::make('Ключевые слова')
                            ->schema([
                                Section::make('Ключевые слова')
                                    ->schema([
                                        Repeater::make('keywords')
                                            ->label('Ключевые слова')
                                            ->schema([
                                                TextInput::make('phrase')
                                                    ->label('Фраза')
                                                    ->maxLength(500),
                                            ])
                                            ->defaultItems(0)
                                            ->addActionLabel('Добавить фразу')
                                            ->reorderable()
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['phrase'] ?? null)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1),
                            ]),

                        Tab::make('Главные блоки')
                            ->schema([
                                Section::make('Главный элемент')
                                    ->description('Большая карточка блока ценностей (левая колонка)')
                                    ->schema([
                                        TextInput::make('main_value_block.title')
                                            ->label('Заголовок')
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Textarea::make('main_value_block.description')
                                            ->label('Описание')
                                            ->rows(4)
                                            ->columnSpanFull(),

                                        TextInput::make('main_value_block.url')
                                            ->label('Ссылка')
                                            ->maxLength(500)
                                            ->rules(['nullable', 'string', 'max:500', 'regex:/^(\/.*|https?:\/\/.+)$/'])
                                            ->helperText('Относительный путь (например /kredity) или полный URL')
                                            ->columnSpanFull(),

                                        FileUpload::make('main_value_block.icon')
                                            ->label('Иконка')
                                            ->image()
                                            ->directory('home-page/values')
                                            ->disk('public')
                                            ->maxSize(1024)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1),

                                Section::make('Дополнительные элементы')
                                    ->description('До 6 карточек в правой части блока ценностей')
                                    ->schema([
                                        Repeater::make('values_grid')
                                            ->label('Элементы')
                                            ->schema([
                                                TextInput::make('title')
                                                    ->label('Заголовок')
                                                    ->maxLength(255),

                                                Textarea::make('description')
                                                    ->label('Описание')
                                                    ->rows(2),

                                                TextInput::make('url')
                                                    ->label('Ссылка')
                                                    ->maxLength(500)
                                                    ->rules(['nullable', 'string', 'max:500', 'regex:/^(\/.*|https?:\/\/.+)$/']),

                                                FileUpload::make('icon')
                                                    ->label('Иконка')
                                                    ->image()
                                                    ->directory('home-page/values')
                                                    ->disk('public')
                                                    ->maxSize(1024)
                                                    ->columnSpanFull(),
                                            ])
                                            ->defaultItems(0)
                                            ->maxItems(6)
                                            ->addActionLabel('Добавить элемент')
                                            ->reorderable()
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<Action>
     */
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
