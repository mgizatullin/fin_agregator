<?php

namespace App\Filament\Pages;

use App\Models\SiteSettings;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
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
use Throwable;
use Illuminate\Support\Arr;
use Filament\Actions\Action;

class AboutProjectSettingsPage extends Page
{
    use CanUseDatabaseTransactions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?string $navigationLabel = 'О проекте';

    protected static ?string $title = 'О проекте';

    protected static ?string $slug = 'about-project-settings';

    protected static string|\UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?int $navigationSort = 10;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    protected string $view = 'filament.pages.about-project-settings';

    public function mount(): void
    {
        $this->fillForm();
    }

    protected function getSetting(): SiteSettings
    {
        return SiteSettings::getInstance();
    }

    protected function fillForm(): void
    {
        $setting = $this->getSetting();

        $this->data = [
            'about_project_page_title' => $setting->about_project_page_title ?? '',
            'about_project_page_subtitle' => $setting->about_project_page_subtitle ?? '',

            'about_project_description_1' => $setting->about_project_description_1 ?? '',
            'about_project_description_2' => $setting->about_project_description_2 ?? '',
            'about_project_facts' => $setting->about_project_facts ?? [],

            'about_project_team_title' => $setting->about_project_team_title ?? '',
            'about_project_team_description' => $setting->about_project_team_description ?? '',
            'about_project_team_items' => $setting->about_project_team_items ?? [],

            'about_project_approach_title' => $setting->about_project_approach_title ?? '',
            'about_project_approach_description' => $setting->about_project_approach_description ?? '',
            'about_project_approach_items' => $setting->about_project_approach_items ?? [],

            'about_project_reviews_title' => $setting->about_project_reviews_title ?? '',
            'about_project_reviews_description' => $setting->about_project_reviews_description ?? '',
            'about_project_reviews_items' => collect($setting->about_project_reviews_items ?? [])
                ->map(function ($item) {
                    if (! is_array($item)) {
                        return ['image' => []];
                    }
                    $img = $item['image'] ?? null;

                    return [
                        'image' => $img ? [$img] : [],
                    ];
                })
                ->values()
                ->toArray(),

            'about_project_seo_title' => $setting->about_project_seo_title ?? '',
            'about_project_seo_description' => $setting->about_project_seo_description ?? '',
        ];
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();
            $this->callHook('beforeValidate');
            $data = $this->form->getState();
            $this->callHook('afterValidate');
            $this->callHook('beforeSave');

            $aboutReviewsItems = collect($data['about_project_reviews_items'] ?? [])
                ->map(function ($item) {
                    $img = $item['image'] ?? [];
                    $path = is_array($img) ? (array_key_exists(0, $img) ? $img[0] : null) : $img;

                    return ['image' => $path];
                })
                ->values()
                ->toArray();

            $this->getSetting()->update([
                'about_project_page_title' => isset($data['about_project_page_title']) && (string) $data['about_project_page_title'] !== '' ? (string) $data['about_project_page_title'] : null,
                'about_project_page_subtitle' => isset($data['about_project_page_subtitle']) && (string) $data['about_project_page_subtitle'] !== '' ? (string) $data['about_project_page_subtitle'] : null,

                'about_project_description_1' => isset($data['about_project_description_1']) && (string) $data['about_project_description_1'] !== '' ? (string) $data['about_project_description_1'] : null,
                'about_project_description_2' => isset($data['about_project_description_2']) && (string) $data['about_project_description_2'] !== '' ? (string) $data['about_project_description_2'] : null,
                'about_project_facts' => $data['about_project_facts'] ?? [],

                'about_project_team_title' => isset($data['about_project_team_title']) && (string) $data['about_project_team_title'] !== '' ? (string) $data['about_project_team_title'] : null,
                'about_project_team_description' => isset($data['about_project_team_description']) && (string) $data['about_project_team_description'] !== '' ? (string) $data['about_project_team_description'] : null,
                'about_project_team_items' => $data['about_project_team_items'] ?? [],

                'about_project_approach_title' => isset($data['about_project_approach_title']) && (string) $data['about_project_approach_title'] !== '' ? (string) $data['about_project_approach_title'] : null,
                'about_project_approach_description' => isset($data['about_project_approach_description']) && (string) $data['about_project_approach_description'] !== '' ? (string) $data['about_project_approach_description'] : null,
                'about_project_approach_items' => $data['about_project_approach_items'] ?? [],

                'about_project_reviews_title' => isset($data['about_project_reviews_title']) && (string) $data['about_project_reviews_title'] !== '' ? (string) $data['about_project_reviews_title'] : null,
                'about_project_reviews_description' => isset($data['about_project_reviews_description']) && (string) $data['about_project_reviews_description'] !== '' ? (string) $data['about_project_reviews_description'] : null,
                'about_project_reviews_items' => $aboutReviewsItems,

                'about_project_seo_title' => isset($data['about_project_seo_title']) && (string) $data['about_project_seo_title'] !== '' ? (string) $data['about_project_seo_title'] : null,
                'about_project_seo_description' => isset($data['about_project_seo_description']) && (string) $data['about_project_seo_description'] !== '' ? (string) $data['about_project_seo_description'] : null,
            ]);

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
            ->title('Настройки раздела «О проекте» сохранены')
            ->send();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('tabs')
                    ->tabs([
                        Tab::make('О проекте')
                            ->label('О проекте')
                            ->schema([
                                Section::make('Шапка страницы')
                                    ->schema([
                                        TextInput::make('about_project_page_title')
                                            ->label('Заголовок')
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Textarea::make('about_project_page_subtitle')
                                            ->label('Подзаголовок')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Section::make('Описание')
                                    ->schema([
                                        RichEditor::make('about_project_description_1')
                                            ->label('Описание (1)')
                                            ->toolbarButtons([
                                                ['bold', 'italic', 'link'],
                                                ['bulletList', 'orderedList'],
                                            ])
                                            ->columnSpanFull(),
                                        RichEditor::make('about_project_description_2')
                                            ->label('Описание (2)')
                                            ->toolbarButtons([
                                                ['bold', 'italic', 'link'],
                                                ['bulletList', 'orderedList'],
                                            ])
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Section::make('Факты')
                                    ->schema([
                                        Repeater::make('about_project_facts')
                                            ->label('Факты')
                                            ->defaultItems(0)
                                            ->addActionLabel('Добавить факт')
                                            ->reorderable()
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['text_1'] ?? null)
                                            ->schema([
                                                TextInput::make('text_1')
                                                    ->label('Текст 1')
                                                    ->maxLength(255)
                                                    ->columnSpanFull(),
                                                TextInput::make('text_2')
                                                    ->label('Текст 2')
                                                    ->maxLength(255)
                                                    ->columnSpanFull(),
                                                TextInput::make('text_3')
                                                    ->label('Текст 3')
                                                    ->maxLength(255)
                                                    ->columnSpanFull(),
                                            ]),
                                    ])
                                    ->columnSpanFull(),

                                Section::make('Команда')
                                    ->schema([
                                        TextInput::make('about_project_team_title')
                                            ->label('Заголовок')
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        Textarea::make('about_project_team_description')
                                            ->label('Описание')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                        Repeater::make('about_project_team_items')
                                            ->label('Позиции команды')
                                            ->defaultItems(0)
                                            ->addActionLabel('Добавить позицию')
                                            ->reorderable()
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => ($state['name'] ?? null) ?: null)
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label('Имя')
                                                    ->maxLength(255)
                                                    ->columnSpanFull(),
                                                TextInput::make('role')
                                                    ->label('Должность')
                                                    ->maxLength(255)
                                                    ->columnSpanFull(),
                                            ])
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),

                                Section::make('Наш подход')
                                    ->schema([
                                        TextInput::make('about_project_approach_title')
                                            ->label('Заголовок')
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        Textarea::make('about_project_approach_description')
                                            ->label('Описание')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                        Repeater::make('about_project_approach_items')
                                            ->label('Позиции')
                                            ->defaultItems(0)
                                            ->addActionLabel('Добавить позицию')
                                            ->reorderable()
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                            ->schema([
                                                TextInput::make('title')
                                                    ->label('Заголовок')
                                                    ->maxLength(255)
                                                    ->columnSpanFull(),
                                                Textarea::make('description')
                                                    ->label('Описание')
                                                    ->rows(3)
                                                    ->columnSpanFull(),
                                            ])
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),

                                Section::make('Отзывы')
                                    ->schema([
                                        TextInput::make('about_project_reviews_title')
                                            ->label('Заголовок')
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        Textarea::make('about_project_reviews_description')
                                            ->label('Описание')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                        Repeater::make('about_project_reviews_items')
                                            ->label('Позиции отзывов')
                                            ->defaultItems(0)
                                            ->addActionLabel('Добавить отзыв')
                                            ->reorderable()
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->itemLabel(function (array $state): ?string {
                                                $img = $state['image'] ?? null;

                                                if (is_array($img)) {
                                                    return isset($img[0]) ? (string) $img[0] : null;
                                                }

                                                // В некоторых случаях FileUpload может отдавать TemporaryUploadedFile как объект,
                                                // а не массив. Тогда возвращаем строковое представление (обычно имя файла).
                                                return is_null($img) ? null : (string) $img;
                                            })
                                            ->schema([
                                                FileUpload::make('image')
                                                    ->label('Картинка')
                                                    ->image()
                                                    ->disk('public')
                                                    ->visibility('public')
                                                    ->directory('about-project/reviews')
                                                    ->imagePreviewHeight(120)
                                                    ->maxSize(2048)
                                                    ->maxFiles(1)
                                                    ->columnSpanFull(),
                                            ])
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('SEO настройки')
                            ->label('SEO настройки')
                            ->schema([
                                Section::make('SEO настройки')
                                    ->schema([
                                        TextInput::make('about_project_seo_title')
                                            ->label('SEO Title')
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Textarea::make('about_project_seo_description')
                                            ->label('SEO Description')
                                            ->rows(4)
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
                    ->id('about-project-settings-form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->alignment($this->getFormActionsAlignment())
                            ->key('form-actions'),
                    ]),
            ]);
    }
}

