<?php

namespace App\Filament\Resources\Cards\Pages;

use App\Filament\Components\SectionCategoriesManager;
use App\Filament\Resources\Cards\CardResource;
use App\Models\CardCategory;
use App\Models\SectionSetting;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

class ListCards extends ListRecords
{
    protected static string $resource = CardResource::class;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $sectionData = [];

    public function mount(): void
    {
        parent::mount();
        $this->fillSectionForm();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getSetting(): SectionSetting
    {
        return SectionSetting::getOrCreateForType('cards');
    }

    protected function fillSectionForm(): void
    {
        $setting = $this->getSetting();
        $data = $setting->attributesToArray();
        $data['faq_title'] = $setting->faq_title ?? '';
        $data['faq_description'] = $setting->faq_description ?? '';
        $data['faq_items'] = collect($setting->faq_items ?? [])
            ->map(fn ($item) => Arr::only(is_array($item) ? $item : [], ['question', 'answer']))
            ->filter(fn ($item) => filled($item['question'] ?? null) || filled($item['answer'] ?? null))
            ->values()
            ->toArray();

        if (isset($data['description']) && is_string($data['description'])) {
            $data['description'] = description_to_html($data['description']);
        }
        if (isset($data['content_template']) && is_string($data['content_template'])) {
            $data['content_template'] = description_to_html($data['content_template']);
        }

        $this->sectionData = $data;
    }

    public function saveSectionSettings(): void
    {
        $data = $this->sectionData ?? [];

        $faqItems = Arr::pull($data, 'faq_items', []);
        $data['faq_items'] = collect($faqItems)
            ->map(fn ($item) => Arr::only(is_array($item) ? $item : [], ['question', 'answer']))
            ->filter(fn ($item) => filled($item['question'] ?? null) || filled($item['answer'] ?? null))
            ->values()
            ->toArray();

        if (array_key_exists('description', $data)) {
            $data['description'] = description_ensure_html($data['description'] ?? '');
        }
        if (array_key_exists('content_template', $data)) {
            $data['content_template'] = description_ensure_html($data['content_template'] ?? '');
        }

        $this->getSetting()->update(Arr::except($data, ['id', 'created_at', 'updated_at', 'type']));

        Notification::make()
            ->success()
            ->title('Настройки раздела сохранены')
            ->send();
    }

    public function sectionEditForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('sectionData')
            ->components([
                TextInput::make('title')
                    ->label('Заголовок')
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('subtitle')
                    ->label('Подзаголовок')
                    ->maxLength(255)
                    ->columnSpanFull(),
                RichEditor::make('description')
                    ->label('Описание')
                    ->columnSpanFull()
                    ->json(false)
                    ->extraInputAttributes(['style' => 'min-height: 300px'])
                    ->toolbarButtons([
                        ['bold', 'italic', 'link'],
                        ['h2', 'h3'],
                        ['bulletList', 'orderedList'],
                    ])
                    ->afterStateHydrated(function (RichEditor $component, mixed $state): void {
                        $json = null;

                        if (is_string($state)) {
                            $json = $state;
                        } elseif (is_array($state)) {
                            $text = $state['content'][0]['content'][0]['text'] ?? null;
                            if (is_string($text) && str_starts_with(trim($text), '{"type":"doc"')) {
                                $json = $text;
                            }
                        }

                        if (! is_string($json)) {
                            return;
                        }

                        $trimmed = trim($json);
                        if (! (str_starts_with($trimmed, '{"type":"doc"') || str_starts_with($trimmed, '{"type": "doc"'))) {
                            return;
                        }

                        $decoded = json_decode($trimmed, true);
                        if (! is_array($decoded)) {
                            return;
                        }

                        $component->state($decoded);
                    }),
                TextInput::make('faq_title')
                    ->label('Заголовок FAQ')
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('faq_description')
                    ->label('Описание FAQ')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('reviews_block_title')
                    ->label('Заголовок блока отзывов')
                    ->maxLength(255)
                    ->columnSpanFull(),
                Repeater::make('faq_items')
                    ->label('Частые вопросы')
                    ->schema([
                        TextInput::make('question')
                            ->label('Вопрос')
                            ->maxLength(500)
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('answer')
                            ->label('Ответ')
                            ->rows(4)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->defaultItems(0)
                    ->addActionLabel('Добавить вопрос')
                    ->reorderable()
                    ->reorderableWithButtons()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                    ->columnSpanFull(),
            ]);
    }

    public function sectionSeoForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('sectionData')
            ->components([
                TextInput::make('seo_title')
                    ->label('SEO Title')
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('seo_description')
                    ->label('SEO Description')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function sectionCityForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('sectionData')
            ->components([
                TextInput::make('seo_title_template')
                    ->label('Шаблон SEO Title')
                    ->maxLength(255)
                    ->helperText('Переменные: {service_name}, {city}, {city.g}, {city.p}')
                    ->columnSpanFull(),
                Textarea::make('seo_description_template')
                    ->label('Шаблон SEO Description')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('h1_template')
                    ->label('Шаблон H1')
                    ->maxLength(255)
                    ->columnSpanFull(),
                RichEditor::make('content_template')
                    ->label('Шаблон текста')
                    ->columnSpanFull()
                    ->json(false)
                    ->extraInputAttributes(['style' => 'min-height: 200px'])
                    ->toolbarButtons([
                        ['bold', 'italic', 'link'],
                        ['h2', 'h3'],
                        ['bulletList', 'orderedList'],
                    ])
                    ->afterStateHydrated(function (RichEditor $component, mixed $state): void {
                        if (! is_string($state) || trim($state) === '') {
                            return;
                        }
                        $trimmed = trim($state);
                        if (str_starts_with($trimmed, '{"type":"doc"') || str_starts_with($trimmed, '{"type": "doc"')) {
                            $decoded = json_decode($trimmed, true);
                            if (is_array($decoded)) {
                                $component->state($decoded);
                            }
                        }
                    }),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('sectionTabs')
                    ->tabs([
                        Tab::make('Список')
                            ->schema([
                                EmbeddedTable::make(),
                            ]),
                        Tab::make('Редактирование')
                            ->schema([
                                Form::make([EmbeddedSchema::make('sectionEditForm')])
                                    ->id('section-edit-form')
                                    ->livewireSubmitHandler('saveSectionSettings')
                                    ->footer([
                                        Actions::make([
                                            \Filament\Actions\Action::make('saveSectionSettings')
                                                ->label('Сохранить')
                                                ->submit('saveSectionSettings'),
                                        ]),
                                    ]),
                            ]),
                        Tab::make('Категории')
                            ->schema([
                                Livewire::make(
                                    SectionCategoriesManager::class,
                                    fn (): array => ['modelClass' => CardCategory::class],
                                )->columnSpanFull(),
                            ]),
                        Tab::make('SEO настройки')
                            ->schema([
                                Form::make([EmbeddedSchema::make('sectionSeoForm')])
                                    ->id('section-seo-form')
                                    ->livewireSubmitHandler('saveSectionSettings')
                                    ->footer([
                                        Actions::make([
                                            \Filament\Actions\Action::make('saveSectionSettingsSeo')
                                                ->label('Сохранить')
                                                ->submit('saveSectionSettings'),
                                        ]),
                                    ]),
                            ]),
                        Tab::make('Мультигородность')
                            ->schema([
                                Form::make([EmbeddedSchema::make('sectionCityForm')])
                                    ->id('section-city-form')
                                    ->livewireSubmitHandler('saveSectionSettings')
                                    ->footer([
                                        Actions::make([
                                            \Filament\Actions\Action::make('saveSectionSettingsCity')
                                                ->label('Сохранить')
                                                ->submit('saveSectionSettings'),
                                        ]),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
