<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CbrRatesService
{
    private const CBR_JSON_URL = 'https://www.cbr-xml-daily.ru/daily_json.js';

    private const CACHE_KEY = 'cbr_currency_rates';

    private const CACHE_TTL_SECONDS = 3600; // 1 hour

    /**
     * Возвращает курсы USD, EUR, CNY (руб. за единицу). При ошибке — запасные значения.
     *
     * @return array{USD: float, EUR: float, CNY: float}
     */
    public function getRates(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): array {
                $response = Http::timeout(10)->get(self::CBR_JSON_URL);
                if (! $response->successful()) {
                    return $this->fallbackRates();
                }
                $json = $response->json();
                $valute = $json['Valute'] ?? [];
                $result = [];
                foreach (['USD', 'EUR', 'CNY'] as $code) {
                    if (isset($valute[$code]['Value'])) {
                        $result[$code] = (float) $valute[$code]['Value'];
                    }
                }
                return $result ?: $this->fallbackRates();
            });
        } catch (\Throwable $e) {
            return $this->fallbackRates();
        }
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
