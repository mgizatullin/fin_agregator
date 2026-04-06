<?php

namespace App\Services;

use App\Models\CbrRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CbrRatesService
{
    private const CBR_JSON_URL = 'https://www.cbr-xml-daily.ru/daily_json.js';

    private const CACHE_KEY = 'cbr_currency_rates';

    private const CACHE_TTL_SECONDS = 3600; // 1 hour

    /**
     * Возвращает курсы USD, EUR, CNY из БД (для хедера и др.).
     * Не обращается к сайту ЦБ — обновление БД по крону (cbr:fetch-rates).
     *
     * @return array{USD: float, EUR: float, CNY: float}
     */
    public function getRates(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): array {
                $row = CbrRate::orderByDesc('rate_date')->first();
                if ($row) {
                    $usd = $row->getRate('USD');
                    $eur = $row->getRate('EUR');
                    $cny = $row->getRate('CNY');
                    if ($usd !== null && $eur !== null && $cny !== null) {
                        return ['USD' => $usd, 'EUR' => $eur, 'CNY' => $cny];
                    }
                }

                return $this->fallbackRates();
            });
        } catch (\Throwable $e) {
            return $this->fallbackRates();
        }
    }

    /** Коды валют для виджета (курсы из ЦБ, хранятся в БД). */
    private const WIDGET_CURRENCY_CODES = ['USD', 'EUR', 'CNY', 'GBP', 'CHF', 'JPY'];

    /**
     * Курсы ЦБ с изменением за сутки для виджета на главной.
     * Только чтение из БД; обновление БД — по крону (php artisan cbr:fetch-rates).
     *
     * @return array{date: string, date_label: string, rates: array<int, array{code: string, rate: float, change: float|null, change_positive: bool|null}>}
     */
    public function getRatesWithChange(): array
    {
        $latest = CbrRate::orderByDesc('rate_date')->first();
        $previous = $latest
            ? CbrRate::where('rate_date', '<', $latest->rate_date)->orderByDesc('rate_date')->first()
            : null;

        $rates = [];
        foreach (self::WIDGET_CURRENCY_CODES as $code) {
            $rate = $latest ? $latest->getRate($code) : null;
            $prevRate = $previous ? $previous->getRate($code) : null;
            $change = $rate !== null && $prevRate !== null ? round($rate - $prevRate, 2) : null;
            $rates[] = [
                'code' => $code,
                'rate' => $rate,
                'change' => $change,
                'change_positive' => $change === null ? null : $change > 0,
            ];
        }

        $date = $latest?->rate_date;
        $dateLabel = $date
            ? 'НА '.strtoupper(Carbon::parse($date)->locale('ru')->translatedFormat('j F'))
            : '';

        return [
            'date' => $date?->format('Y-m-d'),
            'date_label' => $dateLabel,
            'rates' => $rates,
        ];
    }

    /**
     * Данные для страницы курсов ЦБ: топ валют из БД, с изменением к предыдущему торговому дню.
     *
     * @return array{
     *     date: ?string,
     *     date_label: string,
     *     rows: array<int, array{code: string, name: string, rate: ?float, change: ?float, change_positive: ?bool}>
     * }
     */
    public function getPopularRatesForPage(): array
    {
        $codes = array_slice(
            array_values(array_filter(config('currency_rates_page.codes', []))),
            0,
            (int) config('currency_rates_page.limit', 20)
        );
        $names = config('currency_rates_page.names', []);

        $latest = CbrRate::query()->orderByDesc('rate_date')->first();
        $previous = $latest
            ? CbrRate::query()->where('rate_date', '<', $latest->rate_date)->orderByDesc('rate_date')->first()
            : null;

        $rows = [];
        foreach ($codes as $code) {
            $code = strtoupper($code);
            $rate = $latest?->getRate($code);
            $prevRate = $previous?->getRate($code);
            $change = $rate !== null && $prevRate !== null ? round($rate - $prevRate, 4) : null;

            $rows[] = [
                'code' => $code,
                'name' => $names[$code] ?? $code,
                'rate' => $rate,
                'change' => $change,
                'change_positive' => $change === null ? null : $change > 0,
            ];
        }

        $date = $latest?->rate_date;
        $dateLabel = $date
            ? 'на '.Carbon::parse($date)->locale('ru')->translatedFormat('j F Y')
            : '';

        return [
            'date' => $date?->format('Y-m-d'),
            'date_label' => $dateLabel,
            'rows' => $rows,
        ];
    }

    /**
     * Загружает курсы с сайта ЦБ и сохраняет/обновляет запись в БД за указанную дату.
     * Все курсы хранятся как цена за 1 единицу валюты (для JPY: Value/Nominal, т.к. ЦБ отдаёт за 100 йен).
     * Вызывать только из крона или вручную: php artisan cbr:fetch-rates
     *
     * @param  string|null  $forDate  Дата в формате Y-m-d (по умолчанию — сегодня)
     * @param  bool  $force  При true — перезаписать даже если запись за дату уже есть (пересчёт по Nominal)
     */
    public function fetchAndStoreRates(?string $forDate = null, bool $force = false): bool
    {
        $forDate = $forDate ?? now()->toDateString();
        $existing = CbrRate::where('rate_date', $forDate)->first();
        if (! $force && $existing && $existing->rates_json !== null && $existing->rates_json !== []) {
            return true;
        }
        try {
            $response = Http::connectTimeout(15)
                ->timeout(45)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'])
                ->get(self::CBR_JSON_URL);

            if (! $response->successful()) {
                Log::warning('CbrRatesService: ЦБ вернул HTTP '.$response->status(), ['body' => mb_substr($response->body(), 0, 300)]);

                return false;
            }

            $body = $response->body();
            if (str_starts_with($body, "\xEF\xBB\xBF")) {
                $body = substr($body, 3);
            }
            $json = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('CbrRatesService: не удалось разобрать JSON от ЦБ', ['error' => json_last_error_msg()]);

                return false;
            }

            $valute = $json['Valute'] ?? [];
            $ratesJson = [];
            foreach ($valute as $code => $item) {
                if (isset($item['Value'])) {
                    $nominal = max(1, (int) ($item['Nominal'] ?? 1));
                    $ratesJson[$code] = round((float) $item['Value'] / $nominal, 4);
                }
            }
            if (empty($ratesJson)) {
                Log::warning('CbrRatesService: в ответе ЦБ нет курсов (Valute пустой или без Value)');

                return false;
            }
            if ($existing) {
                $existing->update([
                    'rates_json' => $ratesJson,
                    'usd' => $ratesJson['USD'] ?? $existing->usd,
                    'eur' => $ratesJson['EUR'] ?? $existing->eur,
                    'cny' => $ratesJson['CNY'] ?? $existing->cny,
                ]);
            } else {
                CbrRate::create([
                    'rate_date' => $forDate,
                    'usd' => $ratesJson['USD'] ?? 0,
                    'eur' => $ratesJson['EUR'] ?? 0,
                    'cny' => $ratesJson['CNY'] ?? 0,
                    'rates_json' => $ratesJson,
                ]);
            }

            Cache::forget(self::CACHE_KEY);

            return true;
        } catch (\Throwable $e) {
            Log::error('CbrRatesService: ошибка при загрузке курсов ЦБ', [
                'message' => $e->getMessage(),
                'url' => self::CBR_JSON_URL,
            ]);

            return false;
        }
    }

    /** Сохраняет в БД все курсы ЦБ за дату из ответа API. */
    private function storeRatesIfNew(array $json): void
    {
        $dateStr = $json['Date'] ?? null;
        if (! $dateStr) {
            return;
        }
        $date = Carbon::parse($dateStr)->toDateString();
        if (CbrRate::where('rate_date', $date)->exists()) {
            return;
        }
        $valute = $json['Valute'] ?? [];
        $ratesJson = [];
        foreach ($valute as $code => $item) {
            if (isset($item['Value'])) {
                $nominal = max(1, (int) ($item['Nominal'] ?? 1));
                $ratesJson[$code] = round((float) $item['Value'] / $nominal, 4);
            }
        }
        if (empty($ratesJson)) {
            return;
        }
        CbrRate::create([
            'rate_date' => $date,
            'usd' => $ratesJson['USD'] ?? 0,
            'eur' => $ratesJson['EUR'] ?? 0,
            'cny' => $ratesJson['CNY'] ?? 0,
            'rates_json' => $ratesJson,
        ]);
    }

    /**
     * @return array{USD: float, EUR: float, CNY: float}
     */
    private function fallbackRates(): array
    {
        return [
            'USD' => 79.0,
            'EUR' => 91.0,
            'CNY' => 11.5,
        ];
    }
}
