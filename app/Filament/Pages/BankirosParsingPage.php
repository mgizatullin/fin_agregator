<?php

namespace App\Filament\Pages;

use App\Services\Parsers\Bankiros\BankirosParser;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BankirosParsingPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Bankiros — банки';

    protected static ?string $title = 'Парсинг банков (Bankiros)';

    protected static ?string $slug = 'bankiros-parsing';

    protected static string|\UnitEnum|null $navigationGroup = 'Парсинг';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.bankiros-parsing';

    public ?string $logOutput = null;

    public ?string $jsonResult = null;

    public ?int $parserProgressPercent = 0;

    public ?string $banksLimit = null;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('')
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('banksLimit')
                            ->label('Количество банков для парсинга')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->placeholder('Пусто — парсить все банки'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run')
                ->label('Запустить парсинг')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->action(function (): void {
                    $this->logOutput = '';
                    $this->jsonResult = null;
                    $this->parserProgressPercent = 0;

                    $limit = null;
                    if ($this->banksLimit !== null && $this->banksLimit !== '') {
                        $limit = (int) $this->banksLimit;
                        if ($limit < 1) {
                            $limit = null;
                        }
                    }

                    $parser = app(BankirosParser::class);
                    $parser->setLogCallback(function (string $message): void {
                        $this->logOutput = ($this->logOutput ?? '') . $message . "\n";
                    });
                    $parser->setProgressCallback(function (int $percent): void {
                        $this->parserProgressPercent = $percent;
                    });

                    $result = $parser->parse($limit);
                    $this->logOutput = trim($this->logOutput ?? '');

                    $banks = is_array($result) ? $result : [];
                    $this->jsonResult = json_encode($banks, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    $this->parserProgressPercent = 100;

                    Notification::make()
                        ->title('Парсинг завершён')
                        ->body('Найдено банков: ' . count($banks))
                        ->success()
                        ->send();
                }),
        ];
    }
}
