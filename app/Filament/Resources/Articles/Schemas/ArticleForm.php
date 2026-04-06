<?php

namespace App\Filament\Resources\Articles\Schemas;

use App\Models\Specialist;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->tabs([
                        Tab::make('Основная информация')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Заголовок')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state)))
                                    ->columnSpan(12),

                                TextInput::make('slug')
                                    ->label('URL')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->columnSpan(12),

                                Select::make('category_id')
                                    ->label('Категория')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->columnSpan(12),

                                Select::make('specialist_id')
                                    ->label('Автор')
                                    ->relationship('specialist', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->allowHtml()
                                    ->getOptionLabelFromRecordUsing(function (Specialist $record): string {
                                        $name = e($record->name ?? '');
                                        $position = e($record->position ?? '');
                                        $photo = (string) ($record->photo ?? '');
                                        $photoUrl = $photo !== ''
                                            ? (str_starts_with($photo, 'http') ? $photo : asset('storage/'.$photo))
                                            : null;

                                        $img = $photoUrl
                                            ? '<img src="'.$photoUrl.'" alt="" style="width:28px;height:28px;border-radius:999px;object-fit:cover;flex:0 0 28px;" />'
                                            : '<span aria-hidden="true" style="width:28px;height:28px;border-radius:999px;background:#e9ecef;display:inline-flex;align-items:center;justify-content:center;flex:0 0 28px;font-weight:700;color:#6c757d;">'.mb_substr($name, 0, 1).'</span>';

                                        $posHtml = $position !== '' ? '<div style="font-size:12px;color:#6c757d;line-height:1.1;">'.$position.'</div>' : '';

                                        return '<div style="display:flex;gap:10px;align-items:center;">'
                                            .$img
                                            .'<div style="min-width:0;">'
                                            .'<div style="font-weight:600;line-height:1.1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'.$name.'</div>'
                                            .$posHtml
                                            .'</div>'
                                            .'</div>';
                                    })
                                    ->afterStateUpdated(function (Set $set, $state): void {
                                        if (! $state) {
                                            $set('author', null);

                                            return;
                                        }
                                        $name = Specialist::query()->whereKey($state)->value('name');
                                        $set('author', $name ? (string) $name : null);
                                    })
                                    ->columnSpan(12),

                                FileUpload::make('image')
                                    ->label('Изображение')
                                    ->image()
                                    ->disk('public')
                                    ->directory('blog/articles')
                                    ->maxSize(2048)
                                    ->imagePreviewHeight(200)
                                    ->columnSpanFull(),

                                Toggle::make('is_published')
                                    ->label('Опубликовано')
                                    ->default(false)
                                    ->required()
                                    ->columnSpan(12),

                                DatePicker::make('published_at')
                                    ->label('Дата публикации')
                                    ->native(false)
                                    ->displayFormat('d.m.Y')
                                    ->format('Y-m-d')
                                    ->nullable()
                                    ->dehydrateStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('Y-m-d').' 00:00:00' : null)
                                    ->columnSpan(3),
                            ])
                            ->columns(12),

                        Tab::make('Контент')
                            ->schema([
                                Textarea::make('excerpt')
                                    ->label('Краткое описание')
                                    ->rows(4)
                                    ->columnSpanFull(),

                                RichEditor::make('content')
                                    ->label('Текст статьи (редактор)')
                                    ->columnSpanFull()
                                    ->extraInputAttributes(['style' => 'min-height: 300px'])
                                    ->toolbarButtons([
                                        ['source-code'],
                                        ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link', 'textColor', 'highlight', 'code'],
                                        ['h1', 'h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
                                        ['blockquote', 'codeBlock', 'bulletList', 'orderedList', 'details', 'small', 'lead'],
                                        ['table', 'attachFiles', 'customBlocks'],
                                        ['grid', 'gridDelete', 'horizontalRule', 'clearFormatting'],
                                        ['undo', 'redo'],
                                    ]),

                                Textarea::make('content_html')
                                    ->label('HTML целиком (опционально)')
                                    ->helperText('Если заполнено, на сайте показывается этот HTML вместо текста из редактора. Нужен для разметки, которую TipTap не поддерживает (например dl/dt/dd, свои class/style). Доверяйте только доверенным редакторам.')
                                    ->rows(16)
                                    ->columnSpanFull(),
                            ])
                            ->columns(1),

                        Tab::make('SEO настройки')
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
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
