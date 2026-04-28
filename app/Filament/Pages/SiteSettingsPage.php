<?php

namespace App\Filament\Pages;

use App\Models\SiteSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
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
            'footer_menu_bottom' => $setting->footer_menu_bottom ?? [],
            'footer_heading_1' => $setting->footer_heading_1 ?? '',
            'footer_heading_2' => $setting->footer_heading_2 ?? '',
            'copyright' => $setting->copyright ?? '',
            'custom_scripts' => $setting->custom_scripts ?? '',
            'logo' => $setting->logo ? [$setting->logo] : [],
            'site_display_name' => $setting->site_display_name ?? '',
            'email' => $setting->email ?? '',
            'applications_email' => $setting->applications_email ?? '',
            'mail_transport_mode' => $setting->mail_transport_mode ?? 'default',
            'smtp_host' => $setting->smtp_host ?? '',
            'smtp_port' => $setting->smtp_port ? (string) $setting->smtp_port : '',
            'smtp_encryption' => $setting->smtp_encryption ?? 'tls',
            'smtp_username' => $setting->smtp_username ?? '',
            'smtp_password' => $setting->smtp_password ?? '',
            'smtp_from_address' => $setting->smtp_from_address ?? '',
            'smtp_from_name' => $setting->smtp_from_name ?? '',
            'footer_under_logo' => $setting->footer_under_logo ?? '',
            'social_twitter' => $setting->social_twitter ?? '',
            'social_facebook' => $setting->social_facebook ?? '',
            'social_github' => $setting->social_github ?? '',
            'social_instagram' => $setting->social_instagram ?? '',
            'social_youtube' => $setting->social_youtube ?? '',
            'social_zen' => $setting->social_zen ?? '',
            'social_telegram' => $setting->social_telegram ?? '',

            // поля "О проекте" вынесены в отдельную страницу
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
            $footerBottom = $data['footer_menu_bottom'] ?? [];
            $footerBottom = is_array($footerBottom) ? $footerBottom : [];
            $footerHeading1 = $this->stringOrNull($data['footer_heading_1'] ?? null);
            $footerHeading2 = $this->stringOrNull($data['footer_heading_2'] ?? null);
            $logo = $data['logo'] ?? [];
            $logoPath = $this->stringOrNull(is_array($logo) ? Arr::first($logo) : $logo);

            $siteDisplayName = $this->stringOrNull($data['site_display_name'] ?? null);
            $email = $this->stringOrNull($data['email'] ?? null);
            $applicationsEmail = $this->stringOrNull($data['applications_email'] ?? null);
            $mailTransportMode = $this->stringOrNull($data['mail_transport_mode'] ?? null) ?? 'default';
            $smtpHost = $this->stringOrNull($data['smtp_host'] ?? null);
            $smtpPort = isset($data['smtp_port']) ? (int) $data['smtp_port'] : null;
            $smtpEncryption = $this->stringOrNull($data['smtp_encryption'] ?? null);
            $smtpUsername = $this->stringOrNull($data['smtp_username'] ?? null);
            $smtpPassword = $this->stringOrNull($data['smtp_password'] ?? null, trim: false);
            $smtpFromAddress = $this->stringOrNull($data['smtp_from_address'] ?? null);
            $smtpFromName = $this->stringOrNull($data['smtp_from_name'] ?? null);
            $footerUnderLogo = $this->richTextOrNull($data['footer_under_logo'] ?? null);

            $this->getSetting()->update([
                'navigation' => $navigation,
                'footer_menu_1' => $footer1,
                'footer_menu_2' => $footer2,
                'footer_menu_bottom' => $footerBottom,
                'footer_heading_1' => $footerHeading1,
                'footer_heading_2' => $footerHeading2,
                'site_display_name' => $siteDisplayName,
                'email' => $email,
                'applications_email' => $applicationsEmail,
                'mail_transport_mode' => in_array($mailTransportMode, ['default', 'smtp'], true) ? $mailTransportMode : 'default',
                'smtp_host' => $smtpHost,
                'smtp_port' => $smtpPort ?: null,
                'smtp_encryption' => in_array($smtpEncryption, ['tls', 'ssl'], true) ? $smtpEncryption : null,
                'smtp_username' => $smtpUsername,
                'smtp_password' => $smtpPassword,
                'smtp_from_address' => $smtpFromAddress,
                'smtp_from_name' => $smtpFromName,
                'copyright' => $this->stringOrNull($data['copyright'] ?? null, trim: false),
                'custom_scripts' => $this->stringOrNull($data['custom_scripts'] ?? null, trim: false),
                'logo' => $logoPath,
                'footer_under_logo' => $footerUnderLogo,
                'social_twitter' => $this->stringOrNull($data['social_twitter'] ?? null),
                'social_facebook' => $this->stringOrNull($data['social_facebook'] ?? null),
                'social_github' => $this->stringOrNull($data['social_github'] ?? null),
                'social_instagram' => $this->stringOrNull($data['social_instagram'] ?? null),
                'social_youtube' => $this->stringOrNull($data['social_youtube'] ?? null),
                'social_zen' => $this->stringOrNull($data['social_zen'] ?? null),
                'social_telegram' => $this->stringOrNull($data['social_telegram'] ?? null),

                // поля "О проекте" вынесены в отдельную страницу
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
                                Section::make('Копирайт и скрипты')
                                    ->schema([
                                        Textarea::make('copyright')
                                            ->label('Копирайты')
                                            ->placeholder('© 2025. Все права защищены.')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                        Textarea::make('custom_scripts')
                                            ->label('Кастомные скрипты')
                                            ->helperText('Вставьте код вместе с тегами <script>. Скрипты выводятся на всех страницах перед закрывающим тегом </body>.')
                                            ->rows(8)
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                                Section::make('Название сайта')
                                    ->description('Используется в конце заголовка вкладки браузера (например, на страницах кредитов). Если пусто — берётся значение из настройки APP_NAME в .env.')
                                    ->schema([
                                        TextInput::make('site_display_name')
                                            ->label('Название для title')
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                                Section::make('Контактные email')
                                    ->schema([
                                        TextInput::make('email')
                                            ->label('Email')
                                            ->email()
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        TextInput::make('applications_email')
                                            ->label('Email для заявок')
                                            ->email()
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                                Section::make('Отправка писем')
                                    ->description('Для отправки заявок можно использовать сервер по умолчанию или отдельный SMTP.')
                                    ->schema([
                                        Select::make('mail_transport_mode')
                                            ->label('Режим отправки')
                                            ->options([
                                                'default' => 'Обычный (из .env)',
                                                'smtp' => 'SMTP (из полей ниже)',
                                            ])
                                            ->default('default')
                                            ->required()
                                            ->native(false)
                                            ->columnSpanFull(),
                                        TextInput::make('smtp_host')
                                            ->label('SMTP Host')
                                            ->maxLength(255),
                                        TextInput::make('smtp_port')
                                            ->label('SMTP Port')
                                            ->numeric(),
                                        Select::make('smtp_encryption')
                                            ->label('Шифрование')
                                            ->options([
                                                'tls' => 'TLS',
                                                'ssl' => 'SSL',
                                            ])
                                            ->placeholder('Без шифрования')
                                            ->native(false),
                                        TextInput::make('smtp_username')
                                            ->label('SMTP Username')
                                            ->maxLength(255),
                                        TextInput::make('smtp_password')
                                            ->label('SMTP Password')
                                            ->password()
                                            ->revealable(),
                                        TextInput::make('smtp_from_address')
                                            ->label('From Email')
                                            ->email()
                                            ->maxLength(255),
                                        TextInput::make('smtp_from_name')
                                            ->label('From Name')
                                            ->maxLength(255),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                                Section::make('Логотип')
                                    ->description('Отображается в шапке сайта и в подвале. Если не задан — используется логотип по умолчанию.')
                                    ->schema([
                                        FileUpload::make('logo')
                                            ->label('Логотип')
                                            ->image()
                                            ->disk('public')
                                            ->directory('site')
                                            ->visibility('public')
                                            ->maxFiles(1)
                                            ->columnSpanFull(),
                                        RichEditor::make('footer_under_logo')
                                            ->label('Подпись под логотипом')
                                            ->helperText('Текст под логотипом в подвале (контакты, адрес).')
                                            ->json(false)
                                            ->toolbarButtons([
                                                ['bold', 'italic', 'link'],
                                                ['bulletList', 'orderedList'],
                                            ])
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                                Section::make('Соцсети')
                                    ->description('Ссылки на соцсети в подвале. Иконка показывается только если указана ссылка.')
                                    ->schema([
                                        TextInput::make('social_twitter')
                                            ->label('Twitter / X')
                                            ->url()
                                            ->placeholder('https://twitter.com/...')
                                            ->maxLength(500),
                                        TextInput::make('social_facebook')
                                            ->label('Facebook')
                                            ->url()
                                            ->placeholder('https://facebook.com/...')
                                            ->maxLength(500),
                                        TextInput::make('social_github')
                                            ->label('GitHub')
                                            ->url()
                                            ->placeholder('https://github.com/...')
                                            ->maxLength(500),
                                        TextInput::make('social_instagram')
                                            ->label('Instagram')
                                            ->url()
                                            ->placeholder('https://instagram.com/...')
                                            ->maxLength(500),
                                        TextInput::make('social_youtube')
                                            ->label('YouTube')
                                            ->url()
                                            ->placeholder('https://youtube.com/...')
                                            ->maxLength(500),
                                        TextInput::make('social_zen')
                                            ->label('Дзен')
                                            ->url()
                                            ->placeholder('https://dzen.ru/...')
                                            ->maxLength(500),
                                        TextInput::make('social_telegram')
                                            ->label('Телеграм')
                                            ->url()
                                            ->placeholder('https://t.me/...')
                                            ->maxLength(500),
                                    ])
                                    ->columns(2)
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
                                                    ->reorderableWithDragAndDrop(false)
                                                    ->reorderableWithButtons()
                                                    ->collapsible()
                                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                                    ->columnSpanFull(),
                                            ])
                                            ->defaultItems(0)
                                            ->addActionLabel('Добавить пункт меню')
                                            ->reorderable()
                                            ->reorderableWithDragAndDrop(false)
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
                                            ->reorderableWithDragAndDrop(false)
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
                                            ->reorderableWithDragAndDrop(false)
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                                Section::make('Навигация футера — низ')
                                    ->description('Ссылки в нижней полосе футера.')
                                    ->schema([
                                        Repeater::make('footer_menu_bottom')
                                            ->label('Навигация футера — низ')
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
                                            ->reorderableWithDragAndDrop(false)
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

    private function stringOrNull(mixed $value, bool $trim = true): ?string
    {
        if (is_array($value)) {
            return null;
        }

        if (is_object($value) && ! method_exists($value, '__toString')) {
            return null;
        }

        if ($value === null) {
            return null;
        }

        $value = (string) $value;
        $normalized = $trim ? trim($value) : $value;

        return $normalized !== '' ? $normalized : null;
    }

    private function richTextOrNull(mixed $value): ?string
    {
        $html = description_ensure_html($value);

        return trim($html) !== '' ? $html : null;
    }
}
