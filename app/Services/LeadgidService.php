<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LeadgidService
{
    private function baseUrl(): string
    {
        return rtrim((string) config('services.leadgid.base_url', ''), '/') . '/';
    }

    /**
     * Выполняет запрос к Leadgid через cURL (IPv4, TLS verify, User-Agent).
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function makeRequest(string $url, array $params = []): array
    {
        $query = http_build_query($params);
        $fullUrl = $url . ($query ? '?' . $query : '');

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 40,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'X-ACCOUNT-TOKEN: ' . config('services.leadgid.token'),
                'Accept: application/json',
                'User-Agent: Mozilla/5.0',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new \Exception("cURL error {$errno}: {$error} ({$fullUrl})");
        }

        curl_close($ch);

        $decoded = json_decode($response, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function getOffers(array $params = []): array
    {
        return $this->makeRequest($this->baseUrl() . 'offers', $params);
    }

    /**
     * @return array<int, mixed>
     */
    public function getCountries(): array
    {
        $data = $this->makeRequest($this->baseUrl() . 'countries');
        $items = $data['data'] ?? [];

        return is_array($items) ? $items : [];
    }

    /**
     * @return array<int, mixed>
     */
    public function getProducts(): array
    {
        $data = $this->makeRequest($this->baseUrl() . 'products');
        $items = $data['data'] ?? [];

        return is_array($items) ? $items : [];
    }

    /**
     * Приводит параметры country/product к формату country[]/product[].
     *
     * @param  array<string, mixed>  $params
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    private function normalizeArrayParams(array $params, array $keys): array
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $params)) {
                continue;
            }

            $value = $params[$key];
            unset($params[$key]);

            $arrayKey = $key . '[]';
            if ($value === null || $value === '') {
                continue;
            }

            if (is_array($value)) {
                $params[$arrayKey] = array_values(array_filter($value, fn ($v) => $v !== null && $v !== ''));
            } else {
                $params[$arrayKey] = [(string) $value];
            }
        }

        return $params;
    }

    /**
     * Подробная диагностика сетевого соединения с Leadgid API.
     *
     * @return array<string, mixed>
     */
    public function testConnection(): array
    {
        $start = microtime(true);

        try {
            $response = Http::withHeaders([
                'X-ACCOUNT-TOKEN' => config('services.leadgid.token'),
                'Accept' => 'application/json',
            ])
                ->timeout(30)
                ->get('https://api.leadgid.com/offers/v1/affiliates/countries');

            Log::info('Leadgid response', [
                'status' => $response->status(),
                'body' => mb_substr((string) $response->body(), 0, 300),
            ]);

            return [
                'status' => 'OK',
                'http_code' => $response->status(),
                'success' => $response->successful(),
                'time_ms' => (int) round((microtime(true) - $start) * 1000),
                'body' => mb_substr((string) $response->body(), 0, 500),
            ];
        } catch (ConnectionException $e) {
            Log::error('Leadgid error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'FAIL',
                'type' => 'connection',
                'error' => $e->getMessage(),
                'time_ms' => (int) round((microtime(true) - $start) * 1000),
            ];
        } catch (\Exception $e) {
            Log::error('Leadgid error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'FAIL',
                'type' => 'general',
                'error' => $e->getMessage(),
                'time_ms' => (int) round((microtime(true) - $start) * 1000),
            ];
        }
    }

    public function testDns(): string
    {
        return gethostbyname('api.leadgid.com');
    }

    public function testCurl(): ?string
    {
        $out = shell_exec('curl -I https://api.leadgid.com 2>&1');
        return is_string($out) ? $out : null;
    }
}

