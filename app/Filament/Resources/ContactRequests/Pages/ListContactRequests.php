<?php

namespace App\Filament\Resources\ContactRequests\Pages;

use App\Filament\Resources\ContactRequests\ContactRequestResource;
use App\Models\ContactPageSetting;
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

class ListContactRequests extends ListRecords
{
    protected static string $resource = ContactRequestResource::class;

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
        return [];
    }

    protected function getSetting(): ContactPageSetting
    {
        return ContactPageSetting::getInstance();
    }

    protected function fillSectionForm(): void
    {
        $this->sectionData = Arr::only(
            $this->getSetting()->attributesToArray(),
            ['title', 'phone'],
        );
    }

    public function saveSectionSettings(): void
    {
        $data = Arr::only($this->sectionData ?? [], ['title', 'phone']);

        $this->getSetting()->update($data);

        Notification::make()
            ->success()
            ->title('Настройки страницы контактов сохранены')
            ->send();
    }

    public function sectionEditForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('sectionData')
            ->components([
                TextInput::make('title')
                    ->label('Заголовок страницы')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('phone')
                    ->label('Номер телефона')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('contactRequestsTabs')
                    ->tabs([
                        Tab::make('Заявки')
                            ->schema([
                                EmbeddedTable::make(),
                            ]),
                        Tab::make('Редактирование')
                            ->schema([
                                Form::make([EmbeddedSchema::make('sectionEditForm')])
                                    ->id('contact-settings-form')
                                    ->livewireSubmitHandler('saveSectionSettings')
                                    ->footer([
                                        Actions::make([
                                            \Filament\Actions\Action::make('saveSectionSettings')
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
