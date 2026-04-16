<?php

namespace App\Filament\Pages;

use App\Models\Bank;
use App\Models\Card;
use App\Models\Credit;
use App\Models\Deposit;
use App\Models\Loan;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class ImportDescriptionsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Импорт описаний';

    protected static ?string $title = 'Импорт описаний (JSON)';

    protected static ?string $slug = 'import-descriptions';

    protected static string|\UnitEnum|null $navigationGroup = 'Импорт';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.import-descriptions';

    public ?string $jsonPayload = null;

    public ?int $rowsCount = null;

    public ?int $updatedCount = null;

    public ?int $notFoundCount = null;

    /** @var array<int, string> */
    public array $issues = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('')
            ->components([
                Section::make()
                    ->schema([
                        Textarea::make('jsonPayload')
                            ->label('JSON массив')
                            ->rows(18)
                            ->helperText('Формат: [{"url":"...","description":"..."}]. Поддерживаются страницы: /kredity/{slug}, /vklady/{slug}, /karty/{slug}, /zaimy/{slug}, /banki/{slug}. Поле «Описание» будет перезаписано.')
                            ->required(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Импортировать')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->requiresConfirmation()
                ->action('importJson'),
        ];
    }

    public function importJson(): void
    {
        $this->resetStatus();

        $payload = trim((string) $this->jsonPayload);
        if ($payload === '') {
            Notification::make()
                ->title('Поле JSON пустое')
                ->danger()
                ->send();

            return;
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Некорректный JSON')
                ->danger()
                ->body($e->getMessage())
                ->send();

            return;
        }

        if (! is_array($decoded)) {
            Notification::make()
                ->title('Ожидается JSON массив')
                ->danger()
                ->send();

            return;
        }

        $updated = 0;
        $notFound = 0;
        $issues = [];

        foreach (array_values($decoded) as $index => $item) {
            $rowNumber = $index + 1;
            if (! is_array($item)) {
                $issues[] = "Элемент #{$rowNumber}: ожидается объект с полями url/description.";
                continue;
            }

            $url = trim((string) ($item['url'] ?? ''));
            $description = (string) ($item['description'] ?? '');

            if ($url === '') {
                $issues[] = "Элемент #{$rowNumber}: не заполнен url.";
                continue;
            }

            $target = $this->extractTargetFromUrl($url);
            if ($target === null) {
                $issues[] = "Элемент #{$rowNumber}: url '{$url}' не распознан. Ожидается /kredity/{slug}, /vklady/{slug}, /karty/{slug}, /zaimy/{slug} или /banki/{slug}.";
                continue;
            }

            [$modelClass, $slug, $section] = $target;

            /** @var Model|null $record */
            $record = $modelClass::query()->where('slug', $slug)->first();
            if (! $record) {
                $notFound++;
                $issues[] = "Элемент #{$rowNumber}: {$section} не найден(а) по slug '{$slug}' (url: {$url}).";
                continue;
            }

            $record->setAttribute('description', $description);
            $record->save();
            $updated++;
        }

        $this->rowsCount = count($decoded);
        $this->updatedCount = $updated;
        $this->notFoundCount = $notFound;
        $this->issues = $issues;

        if ($issues !== []) {
            Notification::make()
                ->title('Импорт завершён с предупреждениями')
                ->warning()
                ->body("Обработано: {$this->rowsCount}. Обновлено: {$updated}. Не найдено: {$notFound}. Ошибок: ".count($issues).'.')
                ->send();
        } else {
            Notification::make()
                ->title('Импорт успешно завершён')
                ->success()
                ->body("Обработано: {$this->rowsCount}. Обновлено: {$updated}.")
                ->send();
        }
    }

    private function resetStatus(): void
    {
        $this->rowsCount = null;
        $this->updatedCount = null;
        $this->notFoundCount = null;
        $this->issues = [];
    }

    /**
     * @return array{0: class-string<Model>, 1: string, 2: string}|null
     */
    private function extractTargetFromUrl(string $url): ?array
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || trim($path) === '') {
            $path = $url; // allow passing "/kredity/slug"
        }

        $path = trim(explode('?', $path, 2)[0] ?? $path);
        $path = trim($path, '/');
        if ($path === '') {
            return null;
        }

        $segments = array_values(array_filter(explode('/', $path), fn ($s) => $s !== ''));
        if (count($segments) !== 2) {
            return null;
        }

        [$section, $slug] = $segments;
        $section = trim((string) $section);
        $slug = trim((string) $slug);

        if ($section === '' || $slug === '') {
            return null;
        }

        return match ($section) {
            'kredity' => [Credit::class, $slug, 'Кредит'],
            'vklady' => [Deposit::class, $slug, 'Вклад'],
            'karty' => [Card::class, $slug, 'Карта'],
            'zaimy' => [Loan::class, $slug, 'Займ'],
            'banki' => [Bank::class, $slug, 'Банк'],
            default => null,
        };
    }
}

