<?php

namespace App\Filament\Resources\Specialists\Pages;

use App\Filament\Resources\Specialists\SpecialistResource;
use App\Models\SectionSetting;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

class ListSpecialists extends ListRecords
{
    protected static string $resource = SpecialistResource::class;

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
        return SectionSetting::getOrCreateForType('specialists');
    }

    protected function fillSectionForm(): void
    {
        $setting = $this->getSetting();
        $data = $setting->attributesToArray();

        if (isset($data['description']) && is_string($data['description'])) {
            $data['description'] = description_to_html($data['description']);
        }

        $this->sectionData = $data;
    }

    public function saveSectionSettings(): void
    {
        $data = $this->sectionData ?? [];

        if (array_key_exists('description', $data)) {
            $data['description'] = $this->normalizeDescriptionState($data['description'] ?? '');
        }

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

    protected function normalizeDescriptionState(mixed $state): string
    {
        if (is_array($state)) {
            $state = RichContentRenderer::make($state)->toUnsafeHtml();
        }

        return description_ensure_html(is_string($state) ? $state : '');
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
                    ->json(false)
                    ->columnSpanFull()
                    ->toolbarButtons([
                        ['bold', 'italic', 'link'],
                        ['h2', 'h3'],
                        ['bulletList', 'orderedList'],
                    ]),
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
