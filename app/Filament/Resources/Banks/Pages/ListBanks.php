<?php

namespace App\Filament\Resources\Banks\Pages;

use App\Filament\Components\SectionCategoriesManager;
use App\Filament\Resources\Banks\BankResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use App\Models\BankCategory;
use App\Models\SectionSetting;

class ListBanks extends ListRecords
{
    protected static string $resource = BankResource::class;

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
        return SectionSetting::getOrCreateForType('banks');
    }

    protected function fillSectionForm(): void
    {
        $setting = $this->getSetting();
        $data = $setting->attributesToArray();
        $data['advantages'] = collect($setting->advantages ?? [])->map(function ($item) {
            $img = is_array($item) ? ($item['image'] ?? null) : null;
            return [
                'title' => is_array($item) ? ($item['title'] ?? '') : (string) $item,
                'description' => is_array($item) ? ($item['description'] ?? '') : '',
                'image' => $img ? (is_array($img) ? $img : [$img]) : [],
            ];
        })->values()->toArray();

        $this->sectionData = $data;
    }

    public function saveSectionSettings(): void
    {
        $data = $this->sectionData ?? [];

        $advantages = Arr::pull($data, 'advantages', []);
        $data['advantages'] = collect($advantages)->map(function ($a) {
            $img = $a['image'] ?? [];
            $path = is_array($img) ? (Arr::first($img) ?: null) : $img;
            return [
                'title' => $a['title'] ?? '',
                'description' => $a['description'] ?? '',
                'image' => $path,
            ];
        })->values()->toArray();

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
                            ->directory('section-advantages/banks')
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
                                    fn (): array => ['modelClass' => BankCategory::class],
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
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
