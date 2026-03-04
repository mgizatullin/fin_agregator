<?php

namespace App\Filament\Resources\Blog\Pages;

use App\Filament\Components\BlogCategoriesManager;
use App\Filament\Resources\Blog\BlogResource;
use Filament\Actions\CreateAction;
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
use App\Models\SectionSetting;
use Illuminate\Support\Arr;

class ListBlog extends ListRecords
{
    protected static string $resource = BlogResource::class;

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
        return SectionSetting::getOrCreateForType('blog');
    }

    protected function fillSectionForm(): void
    {
        $setting = $this->getSetting();
        $this->sectionData = $setting->attributesToArray();
    }

    public function saveSectionSettings(): void
    {
        $data = $this->sectionData ?? [];
        $this->getSetting()->update(Arr::only($data, [
            'title',
            'subtitle',
            'description',
            'seo_title',
            'seo_description',
        ]));

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
                        Tab::make('Статьи')
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
                                Livewire::make(BlogCategoriesManager::class)
                                    ->columnSpanFull(),
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
