<?php

namespace App\Filament\Pages;

use App\Models\SiteSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
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

class SiteSettingsPage extends Page
{
    use CanUseDatabaseTransactions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Настройки сайта';

    protected static ?string $title = 'Настройки сайта';

    protected static ?string $slug = 'site-settings';

    protected static string|\UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?int $navigationSort = 10;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    protected string $view = 'filament.pages.site-settings';

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
            'navigation' => $setting->navigation ?? [],
            'footer_menu_1' => $setting->footer_menu_1 ?? [],
            'footer_menu_2' => $setting->footer_menu_2 ?? [],
            'footer_heading_1' => $setting->footer_heading_1 ?? '',
            'footer_heading_2' => $setting->footer_heading_2 ?? '',
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

            $navigation = $data['navigation'] ?? [];
            $navigation = is_array($navigation) ? $navigation : [];
            $footer1 = $data['footer_menu_1'] ?? [];
            $footer1 = is_array($footer1) ? $footer1 : [];
            $footer2 = $data['footer_menu_2'] ?? [];
            $footer2 = is_array($footer2) ? $footer2 : [];
            $footerHeading1 = isset($data['footer_heading_1']) ? (string) $data['footer_heading_1'] : null;
            $footerHeading2 = isset($data['footer_heading_2']) ? (string) $data['footer_heading_2'] : null;

            $this->getSetting()->update([
                'navigation' => $navigation,
                'footer_menu_1' => $footer1,
                'footer_menu_2' => $footer2,
                'footer_heading_1' => $footerHeading1 !== '' ? $footerHeading1 : null,
                'footer_heading_2' => $footerHeading2 !== '' ? $footerHeading2 : null,
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
            ->title('Настройки сайта сохранены')
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
            ->components([
                Tabs::make('siteSettingsTabs')
                    ->tabs([
                        Tab::make('Основные')
                            ->label('Основные')
                            ->schema([
                                Section::make()
                                    ->schema([])
                                    ->description('Раздел в разработке.')
                                    ->columnSpanFull(),
                            ]),
                        Tab::make('Навигация')
                            ->label('Навигация')
                            ->schema([
                                Section::make('Верхняя навигация')
                                    ->description('Пункты верхнего меню сайта. Можно добавлять вложенные пункты.')
                                    ->schema([
                                        Repeater::make('navigation')
                                            ->label('Верхняя навигация')
                                            ->schema([
                                                TextInput::make('title')
                                                    ->label('Текст ссылки')
                                                    ->maxLength(255)
                                                    ->required(),
                                                TextInput::make('url')
                                                    ->label('Ссылка')
                                                    ->maxLength(500)
                                                    ->prefix('/')
                                                    ->helperText('Относительный путь (например kredity) или полный URL'),
                                                Repeater::make('children')
                                                    ->label('Дочерние пункты')
                                                    ->schema([
                                                        TextInput::make('title')
                                                            ->label('Текст ссылки')
                                                            ->maxLength(255)
                                                            ->required(),
                                                        TextInput::make('url')
                                                            ->label('Ссылка')
                                                            ->maxLength(500)
                                                            ->prefix('/'),
                                                    ])
                                                    ->defaultItems(0)
                                                    ->addActionLabel('Добавить дочерний пункт')
                                                    ->reorderable()
                                                    ->reorderableWithButtons()
                                                    ->collapsible()
                                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                                    ->columnSpanFull(),
                                            ])
                                            ->defaultItems(0)
                                            ->addActionLabel('Добавить пункт меню')
                                            ->reorderable()
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                                Section::make('Навигация футера — колонка 1')
                                    ->schema([
                                        TextInput::make('footer_heading_1')
                                            ->label('Заголовок блока')
                                            ->placeholder('Компания')
                                            ->maxLength(255),
                                        Repeater::make('footer_menu_1')
                                            ->label('Навигация футера — колонка 1')
                                            ->schema([
                                                TextInput::make('label')
                                                    ->label('Название ссылки')
                                                    ->maxLength(255)
                                                    ->required(),
                                                TextInput::make('url')
                                                    ->label('Ссылка')
                                                    ->maxLength(500)
                                                    ->required(),
                                            ])
                                            ->defaultItems(0)
                                            ->addActionLabel('Добавить ссылку')
                                            ->reorderable()
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                                Section::make('Навигация футера — колонка 2')
                                    ->schema([
                                        TextInput::make('footer_heading_2')
                                            ->label('Заголовок блока')
                                            ->placeholder('Ссылки')
                                            ->maxLength(255),
                                        Repeater::make('footer_menu_2')
                                            ->label('Навигация футера — колонка 2')
                                            ->schema([
                                                TextInput::make('label')
                                                    ->label('Название ссылки')
                                                    ->maxLength(255)
                                                    ->required(),
                                                TextInput::make('url')
                                                    ->label('Ссылка')
                                                    ->maxLength(500)
                                                    ->required(),
                                            ])
                                            ->defaultItems(0)
                                            ->addActionLabel('Добавить ссылку')
                                            ->reorderable()
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
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
                    ->id('site-settings-form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->alignment($this->getFormActionsAlignment())
                            ->key('form-actions'),
                    ]),
            ]);
    }
}
