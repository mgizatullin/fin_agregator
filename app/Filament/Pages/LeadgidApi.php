<?php

namespace App\Filament\Pages;

use App\Services\LeadgidService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class LeadgidApi extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cloud-arrow-down';

    protected static ?string $navigationLabel = 'API Leadgid';

    protected static ?string $title = 'API Leadgid — офферы';

    protected static ?string $slug = 'leadgid-api';

    protected static string|\UnitEnum|null $navigationGroup = 'Парсинг';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.leadgid-api';

    /** @var array<int, array<string, mixed>> */
    public array $offers = [];

    /** @var array<string, string> */
    public array $countryOptions = ['all' => 'Все'];

    /** @var array<string, string> */
    public array $productOptions = ['all' => 'Все'];

    public string $selectedCountry = 'all';

    public string $selectedProduct = 'all';

    /** @var array<string, mixed>|null */
    public ?array $selectedOffer = null;

    public bool $loading = false;

    /** @var array{success: bool, status?: int, body?: string, error?: string}|null */
    public ?array $connectionStatus = null;

    /** @var array<int, mixed> */
    public array $countries = [];

    /** @var array<int, mixed> */
    public array $products = [];

    public int $limit = 50;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('')
            ->components([
                Section::make('Фильтры')
                    ->schema([
                        Select::make('selectedCountry')
                            ->label('Страна')
                            ->options(fn (): array => $this->countryOptions)
                            ->default('all')
                            ->searchable()
                            ->preload()
                            ->live(),

                        Select::make('selectedProduct')
                            ->label('Продукт')
                            ->options(fn (): array => $this->productOptions)
                            ->default('all')
                            ->searchable()
                            ->preload()
                            ->live(),

                        Select::make('limit')
                            ->label('Кол-во офферов')
                            ->options([
                                50 => '50',
                                100 => '100',
                            ])
                            ->default(50)
                            ->required(),
                    ])
                    ->columns(3),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fetch')
                ->label('Загрузить офферы')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->disabled(fn () => $this->loading)
                ->action(fn () => $this->fetchOffers()),
        ];
    }

    public function checkConnection(): void
    {
        try {
            $response = Http::withHeaders([
                'X-ACCOUNT-TOKEN' => config('services.leadgid.token'),
                'Accept' => 'application/json',
            ])
                ->timeout(10)
                ->get('https://api.leadgid.com/offers/v1/affiliates/countries');

            if ($response->successful()) {
                $this->connectionStatus = [
                    'success' => true,
                    'status' => 'OK',
                    'message' => 'Сервер доступен, API ключ корректный',
                ];

                return;
            }

            if ($response->status() === 401) {
                $this->connectionStatus = [
                    'success' => false,
                    'status' => 'ERROR',
                    'message' => 'Сервер доступен, но API ключ неверный',
                ];

                return;
            }

            $this->connectionStatus = [
                'success' => false,
                'status' => 'ERROR',
                'message' => 'Сервер ответил, но с ошибкой: ' . $response->status(),
            ];
        } catch (ConnectionException $e) {
            $this->connectionStatus = [
                'success' => false,
                'status' => 'ERROR',
                'message' => 'Сервер недоступен (нет соединения)',
            ];
        }
    }

    public function loadDictionaries(): void
    {
        try {
            $this->countries = app(LeadgidService::class)->getCountries();
            $this->products = app(LeadgidService::class)->getProducts();
            $this->buildOptionsFromDictionaries();

            Notification::make()
                ->title('Справочники загружены')
                ->body('Страны: ' . count($this->countries) . ', продукты: ' . count($this->products))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Не удалось загрузить справочники')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function buildOptionsFromDictionaries(): void
    {
        $this->countryOptions = ['all' => 'Все'];
        foreach ($this->countries as $c) {
            if (is_array($c)) {
                $code = $c['code'] ?? $c['id'] ?? null;
                $name = $c['name'] ?? $c['title'] ?? (string) $code;
                if ($code !== null && $code !== '') {
                    $this->countryOptions[(string) $code] = (string) $name;
                }
            } elseif (is_string($c) && $c !== '') {
                $this->countryOptions[$c] = $c;
            }
        }

        $this->productOptions = ['all' => 'Все'];
        foreach ($this->products as $p) {
            if (is_array($p)) {
                $name = $p['name'] ?? $p['title'] ?? $p['id'] ?? null;
                if ($name !== null && $name !== '') {
                    $this->productOptions[(string) $name] = (string) $name;
                }
            } elseif (is_string($p) && $p !== '') {
                $this->productOptions[$p] = $p;
            }
        }
    }

    public function fetchOffers(): void
    {
        if ($this->loading) {
            return;
        }

        $this->loading = true;

        try {
            $response = app(LeadgidService::class)->getOffers([
                'limit' => (int) $this->limit,
            ]);

            $this->offers = $response['data'] ?? [];
            if (! is_array($this->offers)) {
                $this->offers = [];
            }

            $this->buildFilters();

            Log::info('Offers loaded', [
                'count' => count($this->offers),
            ]);

            Notification::make()
                ->title('Офферы получены')
                ->body('Найдено: ' . count($this->offers))
                ->success()
                ->send();
        } catch (\Exception $e) {
            $this->offers = [];
            Notification::make()
                ->title('Не удалось получить офферы')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->loading = false;
        }
    }

    public function buildFilters(): void
    {
        $this->countryOptions = ['all' => 'Все'];
        foreach ($this->offers as $offer) {
            if (! is_array($offer)) {
                continue;
            }
            if (! empty($offer['countries']) && is_array($offer['countries'])) {
                foreach ($offer['countries'] as $country) {
                    if (is_string($country) && $country !== '') {
                        $this->countryOptions[$country] = $country;
                    }
                }
            }
        }

        $this->productOptions = ['all' => 'Все'];
        foreach ($this->offers as $offer) {
            if (! is_array($offer)) {
                continue;
            }
            if (! empty($offer['products']) && is_array($offer['products'])) {
                foreach ($offer['products'] as $product) {
                    if (is_array($product) && ! empty($product['name']) && is_string($product['name'])) {
                        $this->productOptions[$product['name']] = $product['name'];
                    }
                }
            }
        }

        if (! array_key_exists($this->selectedCountry, $this->countryOptions)) {
            $this->selectedCountry = 'all';
        }
        if (! array_key_exists($this->selectedProduct, $this->productOptions)) {
            $this->selectedProduct = 'all';
        }
    }

    /**
     * Офферы отфильтрованные из единственного источника $this->offers.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function getFilteredOffersProperty(): \Illuminate\Support\Collection
    {
        $filtered = collect($this->offers);

        if ($this->selectedCountry !== 'all') {
            $filtered = $filtered->filter(function ($offer) {
                return in_array($this->selectedCountry, $offer['countries'] ?? [], true);
            });
        }

        if ($this->selectedProduct !== 'all') {
            $filtered = $filtered->filter(function ($offer) {
                return collect($offer['products'] ?? [])->contains(function ($product) {
                    return str_contains(
                        mb_strtolower((string) ($product['name'] ?? '')),
                        mb_strtolower($this->selectedProduct)
                    );
                });
            });
        }

        return $filtered->values();
    }

    public function showOffer(int|string $id): void
    {
        $idStr = (string) $id;
        $this->selectedOffer = collect($this->offers)->firstWhere('id', $idStr);
        $this->dispatch('open-modal', id: 'offer-details');
    }
}

