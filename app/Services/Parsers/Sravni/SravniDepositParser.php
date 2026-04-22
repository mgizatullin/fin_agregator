<?php

namespace App\Services\Parsers\Sravni;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Cookie\CookieJar;

class SravniDepositParser
{
    private const CATALOG_URL = 'https://www.sravni.ru/vklady/';
    private const SEARCH_FILTERS_URL = 'https://www.sravni.ru/proxy-deposits/search-filters';
    private const SEARCH_FILTERS_URL_ALT = 'https://www.sravni.ru/proxy-deposits/searchFilters';
    private const SEARCH_URL = 'https://www.sravni.ru/proxy-deposits/search';

    private const MAX_DEPOSITS_DEFAULT = 200;

    /** @var array<int, string> */
    private array $log = [];

    /** @var array<string, int> */
    private array $stats = [
        'found' => 0,
        'processed' => 0,
        'success' => 0,
        'errors' => 0,
    ];

    private CookieJar $cookieJar;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $url): array
    {
        $this->resetRuntime();
        $this->logMessage('Старт парсинга Sravni: ' . $url);

        $result = $this->parseInternal($url, false);

        if ($result === []) {
            $this->stats['errors']++;
        } else {
            $this->stats['success']++;
        }
        $this->stats['processed'] = 1;

        return $result;
    }

    /**
     * Массовый парсинг всех вкладов из каталога Sravni.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseAll(?string $catalogUrl = null, int $max = self::MAX_DEPOSITS_DEFAULT): array
    {
        $this->resetRuntime();
        $catalogUrl = trim((string) ($catalogUrl ?: self::CATALOG_URL));
        if ($catalogUrl === '') {
            $catalogUrl = self::CATALOG_URL;
        }

        $max = $max > 0 ? $max : self::MAX_DEPOSITS_DEFAULT;
        $this->logMessage("Старт массового парсинга Sravni: {$catalogUrl}");

        $links = $this->collectAllDepositLinks($catalogUrl, $max);
        $this->stats['found'] = count($links);
        $this->logMessage('Найдено ссылок вкладов: ' . $this->stats['found']);

        if ($links === []) {
            $this->logAndWarn('Ссылки вкладов не найдены в каталоге.');

            return [];
        }

        $links = array_slice($links, 0, $max);
        $this->logMessage('К обработке (с учетом лимита): ' . count($links));

        $result = [];
        foreach ($links as $link) {
            $this->stats['processed']++;
            $this->logMessage('Парсинг: ' . $link);

            try {
                $parsed = $this->parseInternal($link, true);
            } catch (\Throwable $e) {
                $this->stats['errors']++;
                $this->logAndWarn('✖ Ошибка: ' . $e->getMessage());
                continue;
            }

            if ($parsed === []) {
                $this->stats['errors']++;
                $this->logAndWarn('✖ Ошибка: не удалось извлечь данные.');
                continue;
            }

            $this->stats['success']++;
            $this->logMessage('✔ Успешно');
            $result[] = $parsed[0];
        }

        $this->logMessage('Завершение массового парсинга.');

        return $result;
    }

    /**
     * @return array<int, string>
     */
    public function getLog(): array
    {
        return $this->log;
    }

    /**
     * @return array<string, int>
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    private function resetRuntime(): void
    {
        $this->log = [];
        $this->cookieJar = new CookieJar();
        $this->stats = [
            'found' => 0,
            'processed' => 0,
            'success' => 0,
            'errors' => 0,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseInternal(string $url, bool $withInlineLog): array
    {
        $html = $this->fetchHtml($url);
        if ($html === null || trim($html) === '') {
            $this->logAndWarn('Не удалось загрузить HTML страницы.');
            return [];
        }

        if ($this->looksLikeRobotPage($html)) {
            $this->logAndWarn('Похоже на антибот-страницу («Вы не робот?»). Попробуйте увеличить паузу между запросами или повторить позже.');
            return [];
        }

        $nextDataJson = $this->extractNextDataJson($html);
        if ($nextDataJson === null) {
            $this->logAndWarn('JSON __NEXT_DATA__ не найден на странице. Title: ' . $this->extractHtmlTitle($html) . '.');
            return [];
        }

        try {
            $nextData = json_decode($nextDataJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logAndWarn('Не удалось декодировать __NEXT_DATA__: ' . $e->getMessage());
            return [];
        }

        $depositPayload = $this->findUpdateDepositPayload($nextData);
        if (! is_array($depositPayload)) {
            $this->logAndWarn('Не найден блок updateDeposit в __NEXT_DATA__. Возможно, изменилась структура Sravni.');
            return [];
        }

        $normalized = $this->normalizeDeposit($depositPayload);
        if (($normalized[0]['conditions'][0]['amount_ranges'][0]['terms'] ?? []) === []) {
            $this->logAndWarn('terms пустые после нормализации. Проверьте структуру data.terms.');
        }

        if (! $withInlineLog) {
            $this->logMessage('Парсинг завершен успешно.');
        }

        return $normalized;
    }

    private function fetchHtml(string $url): ?string
    {
        $response = $this->requestWithRetry($url, false, []);
        return $response?->body();
    }

    private function requestWithRetry(string $url, bool $expectsJson, array $query): ?\Illuminate\Http\Client\Response
    {
        // Базовая защита от rate-limit.
        sleep(1);

        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'Accept' => $expectsJson ? 'application/json, text/html' : 'application/json, text/html',
            'Accept-Language' => 'ru-RU,ru;q=0.9',
            'Referer' => 'https://www.sravni.ru/',
        ];

        $candidateUrls = $this->buildUrlCandidates($url);

        foreach ($candidateUrls as $candidateUrl) {
            $response = null;
            try {
                $response = Http::withHeaders($headers)
                    ->withOptions([
                        'connect_timeout' => 20,
                        'force_ip_resolve' => 'v4',
                        'version' => 1.1,
                        'cookies' => $this->cookieJar,
                        'curl' => [
                            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        ],
                    ])
                    ->timeout(45)
                    ->retry(3, 1000)
                    ->get($candidateUrl, $query);
            } catch (\Throwable $e) {
                $this->logAndWarn('HTTP исключение: ' . $e->getMessage() . ' [' . $candidateUrl . ']');
            }

            if ($response instanceof \Illuminate\Http\Client\Response && $response->successful()) {
                if (! $expectsJson && $this->looksLikeRobotPage((string) $response->body())) {
                    $this->logAndWarn('Антибот HTML (200) для: ' . $candidateUrl);
                } else {
                return $response;
                }
            }

            // Дополнительная ручная попытка после встроенного retry.
            usleep(500000);
            try {
                $response = Http::withHeaders($headers)
                    ->withOptions([
                        'connect_timeout' => 20,
                        'force_ip_resolve' => 'v4',
                        'version' => 1.1,
                        'cookies' => $this->cookieJar,
                        'curl' => [
                            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        ],
                    ])
                    ->timeout(45)
                    ->retry(3, 1000)
                    ->get($candidateUrl, $query);
            } catch (\Throwable $e) {
                $this->logAndWarn('HTTP ручной retry упал: ' . $e->getMessage() . ' [' . $candidateUrl . ']');
                continue;
            }

            if ($response->successful()) {
                if (! $expectsJson && $this->looksLikeRobotPage((string) $response->body())) {
                    $this->logAndWarn('Антибот HTML (200) для: ' . $candidateUrl);
                } else {
                return $response;
                }
            }

            // Фолбэк на raw cURL с теми же network-настройками.
            $curlBody = $this->rawCurlGet($candidateUrl, $query, $headers);
            if (is_string($curlBody) && $curlBody !== '') {
                if (! $expectsJson && $this->looksLikeRobotPage($curlBody)) {
                    $this->logAndWarn('Антибот HTML (raw cURL) для: ' . $candidateUrl);
                    continue;
                }
                return new \Illuminate\Http\Client\Response(
                    new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $curlBody)
                );
            }

            $this->logAndWarn('HTTP ' . $response->status() . ' [' . $candidateUrl . ']');
        }

        return null;
    }

    private function looksLikeRobotPage(string $html): bool
    {
        $h = mb_strtolower($html);
        return str_contains($h, 'вы не робот') || str_contains($h, 'captcha') || str_contains($h, 'cf-challenge');
    }

    private function extractHtmlTitle(string $html): string
    {
        if (preg_match('~<title[^>]*>(.*?)</title>~isu', $html, $m) === 1) {
            return trim(strip_tags((string) ($m[1] ?? '')));
        }
        return '';
    }

    /**
     * @return array<int, string>
     */
    private function buildUrlCandidates(string $url): array
    {
        $urls = [$url];
        if (str_contains($url, '://www.sravni.ru/')) {
            $urls[] = str_replace('://www.sravni.ru/', '://sravni.ru/', $url);
        } elseif (str_contains($url, '://sravni.ru/')) {
            $urls[] = str_replace('://sravni.ru/', '://www.sravni.ru/', $url);
        }

        return array_values(array_unique($urls));
    }

    /**
     * @param array<string, string> $headers
     */
    private function rawCurlGet(string $url, array $query, array $headers): ?string
    {
        $queryString = http_build_query($query);
        $fullUrl = $url . ($queryString !== '' ? (str_contains($url, '?') ? '&' : '?') . $queryString : '');

        $headerRows = [];
        foreach ($headers as $key => $value) {
            $headerRows[] = $key . ': ' . $value;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_HTTPHEADER => $headerRows,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        ]);

        $result = curl_exec($ch);
        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);
            $this->logAndWarn('Raw cURL ошибка: ' . $error . ' [' . $fullUrl . ']');
            return null;
        }

        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode < 200 || $httpCode >= 300) {
            $this->logAndWarn('Raw cURL HTTP ' . $httpCode . ' [' . $fullUrl . ']');
            return null;
        }

        return is_string($result) ? $result : null;
    }

    /**
     * @return array<int, string>
     */
    private function collectAllDepositLinks(string $catalogUrl, int $max): array
    {
        $links = [];

        $bankAliases = $this->fetchBankAliasesFromApi();
        $this->logMessage('Банков из API search-filters: ' . count($bankAliases));

        $apiSearchLinks = $this->fetchDepositLinksFromSearchApi($max);
        if ($apiSearchLinks !== []) {
            $this->logMessage('Ссылок из API search: ' . count($apiSearchLinks));
            $links = array_merge($links, $apiSearchLinks);
        }

        if (count($links) < $max) {
            $fallbackLinks = $this->collectLinksFromBankPages($catalogUrl, $bankAliases, $max);
            $this->logMessage('Ссылок из fallback-страниц банков: ' . count($fallbackLinks));
            $links = array_merge($links, $fallbackLinks);
        }

        $links = array_values(array_unique(array_filter($links, fn ($v) => is_string($v) && $v !== '')));
        if (count($links) > $max) {
            $links = array_slice($links, 0, $max);
        }

        return $links;
    }

    /**
     * @return array<int, string>
     */
    private function fetchBankAliasesFromApi(): array
    {
        $aliases = [];

        foreach ([self::SEARCH_FILTERS_URL, self::SEARCH_FILTERS_URL_ALT] as $endpoint) {
            $response = $this->requestWithRetry($endpoint, true, []);
            if ($response === null) {
                continue;
            }

            $data = $response->json();
            $this->collectBankAliasesByBankDetail($data, $aliases);
            $this->collectBankAliasesFromFilters($data, $aliases);

            if (count($aliases) > 0) {
                break;
            }
        }

        return array_values(array_unique(array_filter($aliases, fn ($v) => is_string($v) && $v !== '' && ! str_contains($v, ':'))));
    }

    /**
     * @param mixed $node
     * @param array<int, string> $aliases
     */
    private function collectBankAliasesByBankDetail(mixed $node, array &$aliases): void
    {
        if (! is_array($node)) {
            return;
        }

        if (isset($node['bankDetail']) && is_array($node['bankDetail'])) {
            $alias = $node['bankDetail']['alias'] ?? null;
            if (is_string($alias) && $alias !== '') {
                $aliases[] = $alias;
            }
        }

        foreach ($node as $value) {
            $this->collectBankAliasesByBankDetail($value, $aliases);
        }
    }

    /**
     * @param mixed $node
     * @param array<int, string> $aliases
     */
    private function collectBankAliasesFromFilters(mixed $node, array &$aliases): void
    {
        if (! is_array($node)) {
            return;
        }

        // Частый формат: banks => [{ alias, name, ... }]
        if (isset($node['banks']) && is_array($node['banks'])) {
            foreach ($node['banks'] as $bankItem) {
                if (! is_array($bankItem)) {
                    continue;
                }
                $alias = $bankItem['alias'] ?? null;
                if (is_string($alias) && $alias !== '' && ! str_contains($alias, ':')) {
                    $aliases[] = $alias;
                }
            }
        }

        // Универсальный fallback: alias у объектов с bank/name.
        if (isset($node['alias']) && is_string($node['alias'])) {
            $looksLikeBank = isset($node['bankName']) || isset($node['name']) || isset($node['bankId']) || isset($node['id']);
            if ($looksLikeBank) {
                $alias = trim($node['alias']);
                if ($alias !== '' && ! str_contains($alias, ':')) {
                    $aliases[] = $alias;
                }
            }
        }

        foreach ($node as $value) {
            $this->collectBankAliasesFromFilters($value, $aliases);
        }
    }

    /**
     * @return array<int, string>
     */
    private function fetchDepositLinksFromSearchApi(int $max): array
    {
        $terms = [30, 90, 180, 365, 730, 1095];
        $amounts = [10000, 100000, 1000000];
        $links = [];

        // Пробный запрос: если endpoint недоступен/404, не спамим десятками запросов.
        $probe = $this->requestWithRetry(self::SEARCH_URL, true, [
            'term' => $terms[0],
            'amount' => $amounts[0],
        ]);
        if ($probe === null) {
            $this->logMessage('API search недоступен, пропускаем этот источник.');
            return [];
        }
        $this->collectDepositLinksFromSearchPayload($probe->json(), $links);

        foreach ($terms as $term) {
            foreach ($amounts as $amount) {
                if (count($links) >= $max) {
                    break 2;
                }

                $response = $this->requestWithRetry(self::SEARCH_URL, true, [
                    'term' => $term,
                    'amount' => $amount,
                ]);

                if ($response === null) {
                    continue;
                }

                $payload = $response->json();
                $this->collectDepositLinksFromSearchPayload($payload, $links);
                $links = array_values(array_unique($links));
            }
        }

        return array_slice($links, 0, $max);
    }

    /**
     * @param mixed $payload
     * @param array<int, string> $links
     */
    private function collectDepositLinksFromSearchPayload(mixed $payload, array &$links): void
    {
        if (! is_array($payload)) {
            return;
        }

        $bankAlias = $payload['bankDetail']['alias'] ?? null;
        $productAlias = $payload['product']['alias'] ?? null;
        if (is_string($bankAlias) && is_string($productAlias) && $bankAlias !== '' && $productAlias !== '') {
            $links[] = $this->makeAbsoluteUrl('/bank/' . $bankAlias . '/vklad/' . $productAlias . '/');
        }

        $urlFields = ['url', 'productUrl', 'depositUrl', 'link', 'href'];
        foreach ($urlFields as $field) {
            $value = $payload[$field] ?? null;
            if (is_string($value) && preg_match('~(?:^https?://www\.sravni\.ru)?/bank/[^/]+/vklad/[^/]+/?$~u', $value)) {
                $links[] = $this->makeAbsoluteUrl($value);
            }
        }

        foreach ($payload as $value) {
            $this->collectDepositLinksFromSearchPayload($value, $links);
        }
    }

    /**
     * @param array<int, string> $bankAliases
     * @return array<int, string>
     */
    private function collectLinksFromBankPages(string $catalogUrl, array $bankAliases, int $max): array
    {
        $catalogHtml = null;
        if ($bankAliases === []) {
            $catalogHtml = $this->fetchHtml($catalogUrl);
            if (is_string($catalogHtml) && trim($catalogHtml) !== '') {
                // Восстановленный fallback: вытаскиваем alias банков из __NEXT_DATA__ каталога.
                $nextDataJson = $this->extractNextDataJson($catalogHtml);
                if (is_string($nextDataJson) && trim($nextDataJson) !== '') {
                    try {
                        $nextData = json_decode($nextDataJson, true, 512, JSON_THROW_ON_ERROR);
                        if (is_array($nextData)) {
                            $bankAliases = array_merge($bankAliases, $this->extractBankAliasesFromNextData($nextData));
                            $bankAliases = array_merge($bankAliases, $this->extractBankAliasesDeep($nextData));
                        }
                    } catch (\JsonException $e) {
                        $this->logAndWarn('Не удалось декодировать __NEXT_DATA__ каталога: ' . $e->getMessage());
                    }
                }
            }
        }

        $bankAliases = array_values(array_unique(array_filter($bankAliases, fn ($v) => is_string($v) && $v !== '' && ! str_contains($v, ':'))));
        $bankCatalogLinks = [];
        foreach ($bankAliases as $alias) {
            $bankCatalogLinks[] = $this->makeAbsoluteUrl('/bank/' . $alias . '/vklady/');
        }

        if ($bankCatalogLinks === []) {
            $catalogHtml = is_string($catalogHtml) ? $catalogHtml : $this->fetchHtml($catalogUrl);
            if (is_string($catalogHtml) && trim($catalogHtml) !== '') {
                $bankCatalogLinks = array_values(array_unique($this->extractBankCatalogLinksFromCatalogHtml($catalogHtml)));
            }
        }

        $this->logMessage('Кандидатов страниц банков: ' . count($bankCatalogLinks));
        $links = [];

        foreach ($bankCatalogLinks as $bankCatalogUrl) {
            if (count($links) >= $max) {
                break;
            }

            $this->logMessage('Поиск вкладов на странице банка: ' . $bankCatalogUrl);
            $bankHtml = $this->fetchHtml($bankCatalogUrl);
            if (! is_string($bankHtml) || trim($bankHtml) === '') {
                $this->logAndWarn('Не удалось загрузить страницу банка: ' . $bankCatalogUrl);
                continue;
            }

            $localLinks = [];
            $localBankCatalogLinks = [];
            $localBankAliases = [];
            $bankAliasFromUrl = $this->extractBankAliasFromUrl($bankCatalogUrl);
            $bankNextDataJson = $this->extractNextDataJson($bankHtml);
            if ($bankNextDataJson !== null) {
                try {
                    $bankNextData = json_decode($bankNextDataJson, true, 512, JSON_THROW_ON_ERROR);
                    $this->collectDepositLinksFromNode($bankNextData, $localLinks, $localBankCatalogLinks, $localBankAliases);
                    $localLinks = array_merge($localLinks, $this->extractDepositLinksByProductAliases($bankNextData, $bankAliasFromUrl));
                } catch (\JsonException $e) {
                    $this->logAndWarn('Не удалось декодировать __NEXT_DATA__ страницы банка: ' . $e->getMessage());
                }
            }

            if (preg_match_all('~href=["\'](/bank/[^"\']+/vklad/[^"\']+/?)["\']~u', $bankHtml, $m2)) {
                foreach ($m2[1] as $path) {
                    if (is_string($path)) {
                        $localLinks[] = $this->makeAbsoluteUrl($path);
                    }
                }
            }

            $localLinks = array_values(array_unique(array_filter($localLinks, fn ($v) => is_string($v) && $v !== '')));
            if ($localLinks !== []) {
                $this->logMessage('Найдено вкладов на странице банка: ' . count($localLinks));
            }
            $links = array_values(array_unique(array_merge($links, $localLinks)));
        }

        return array_slice($links, 0, $max);
    }

    /**
     * @return array<int, string>
     */
    private function extractBankCatalogLinksFromCatalogHtml(string $catalogHtml): array
    {
        $links = [];
        if (preg_match_all('~href=["\'](/bank/[^"\']+/vklady/[^"\']*)["\']~u', $catalogHtml, $matches)) {
            foreach ($matches[1] as $path) {
                if (is_string($path)) {
                    $links[] = $this->makeAbsoluteUrl($path);
                }
            }
        }

        return array_values(array_unique($links));
    }

    /**
     * @return array<int, string>
     */
    private function extractDepositLinks(string $catalogHtml, int $max): array
    {
        $links = [];
        $bankCatalogLinks = [];
        $bankAliases = [];

        $nextDataJson = $this->extractNextDataJson($catalogHtml);
        if ($nextDataJson !== null) {
            try {
                $nextData = json_decode($nextDataJson, true, 512, JSON_THROW_ON_ERROR);
                $this->collectDepositLinksFromNode($nextData, $links, $bankCatalogLinks, $bankAliases);
                $bankAliases = array_merge($bankAliases, $this->extractBankAliasesFromNextData($nextData));
                $bankAliases = array_merge($bankAliases, $this->extractBankAliasesDeep($nextData));
            } catch (\JsonException $e) {
                $this->logAndWarn('Не удалось декодировать __NEXT_DATA__ каталога: ' . $e->getMessage());
            }
        }

        // fallback: regex по href в HTML
        if (preg_match_all('~href=["\'](/bank/[^"\']+/vklad/[^"\']+/?)["\']~u', $catalogHtml, $matches)) {
            foreach ($matches[1] as $path) {
                if (! is_string($path)) {
                    continue;
                }
                $links[] = $this->makeAbsoluteUrl($path);
            }
        }
        if (preg_match_all('~href=["\'](/bank/[^"\']+/vklady/[^"\']*)["\']~u', $catalogHtml, $matches)) {
            foreach ($matches[1] as $path) {
                if (! is_string($path)) {
                    continue;
                }
                $bankCatalogLinks[] = $this->makeAbsoluteUrl($path);
            }
        }

        $links = array_values(array_unique(array_filter($links, fn ($v) => is_string($v) && $v !== '')));
        $bankCatalogLinks = array_values(array_unique(array_filter($bankCatalogLinks, fn ($v) => is_string($v) && $v !== '')));
        $bankAliases = array_values(array_unique(array_filter($bankAliases, fn ($v) => is_string($v) && $v !== '' && !str_contains($v, ':'))));

        foreach ($bankAliases as $alias) {
            $bankCatalogLinks[] = $this->makeAbsoluteUrl('/bank/' . $alias . '/vklady/');
        }
        $bankCatalogLinks = array_values(array_unique($bankCatalogLinks));
        $this->logMessage('Кандидатов страниц банков: ' . count($bankCatalogLinks));

        if (count($links) >= $max) {
            return array_slice($links, 0, $max);
        }

        // Если на общей странице нет прямых ссылок на вклады — обходим банковские страницы вкладов.
        foreach ($bankCatalogLinks as $bankCatalogUrl) {
            if (count($links) >= $max) {
                break;
            }

            $this->logMessage('Поиск вкладов на странице банка: ' . $bankCatalogUrl);
            $bankHtml = $this->fetchHtml($bankCatalogUrl);
            if (! is_string($bankHtml) || trim($bankHtml) === '') {
                $this->logAndWarn('Не удалось загрузить страницу банка: ' . $bankCatalogUrl);
                continue;
            }

            $localLinks = [];
            $localBankCatalogLinks = [];
            $localBankAliases = [];
            $bankAliasFromUrl = $this->extractBankAliasFromUrl($bankCatalogUrl);

            $bankNextDataJson = $this->extractNextDataJson($bankHtml);
            if ($bankNextDataJson !== null) {
                try {
                    $bankNextData = json_decode($bankNextDataJson, true, 512, JSON_THROW_ON_ERROR);
                    $this->collectDepositLinksFromNode($bankNextData, $localLinks, $localBankCatalogLinks, $localBankAliases);
                    $localLinks = array_merge(
                        $localLinks,
                        $this->extractDepositLinksByProductAliases($bankNextData, $bankAliasFromUrl)
                    );
                } catch (\JsonException $e) {
                    $this->logAndWarn('Не удалось декодировать __NEXT_DATA__ страницы банка: ' . $e->getMessage());
                }
            }

            if (preg_match_all('~href=["\'](/bank/[^"\']+/vklad/[^"\']+/?)["\']~u', $bankHtml, $m2)) {
                foreach ($m2[1] as $path) {
                    if (is_string($path)) {
                        $localLinks[] = $this->makeAbsoluteUrl($path);
                    }
                }
            }

            $localLinks = array_values(array_unique(array_filter($localLinks, fn ($v) => is_string($v) && $v !== '')));
            if ($localLinks !== []) {
                $this->logMessage('Найдено вкладов на странице банка: ' . count($localLinks));
            }
            $links = array_values(array_unique(array_merge($links, $localLinks)));
        }

        if (count($links) > $max) {
            $links = array_slice($links, 0, $max);
        }

        return $links;
    }

    /**
     * @param mixed $node
     * @param array<int, string> $links
     * @param array<int, string> $bankCatalogLinks
     * @param array<int, string> $bankAliases
     */
    private function collectDepositLinksFromNode(mixed $node, array &$links, array &$bankCatalogLinks, array &$bankAliases): void
    {
        if (is_string($node)) {
            if (preg_match('~^/bank/[^/:]+/vklad/[^/:]+/?$~u', $node)) {
                $links[] = $this->makeAbsoluteUrl($node);
            } elseif (preg_match('~^/bank/[^/:]+/vklady(?:/.*)?$~u', $node)) {
                $bankCatalogLinks[] = $this->makeAbsoluteUrl($node);
            } elseif (preg_match('~^https?://www\.sravni\.ru/bank/[^/:]+/vklad/[^/:]+/?$~u', $node)) {
                $links[] = rtrim($node, '/') . '/';
            } elseif (preg_match('~^https?://www\.sravni\.ru/bank/[^/:]+/vklady(?:/.*)?$~u', $node)) {
                $bankCatalogLinks[] = rtrim($node, '/') . '/';
            }
            return;
        }

        if (! is_array($node)) {
            return;
        }

        foreach ($node as $value) {
            $this->collectDepositLinksFromNode($value, $links, $bankCatalogLinks, $bankAliases);
        }
    }

    private function makeAbsoluteUrl(string $pathOrUrl): string
    {
        $value = trim($pathOrUrl);
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return rtrim($value, '/') . '/';
        }

        if (! str_starts_with($value, '/')) {
            $value = '/' . $value;
        }

        return 'https://www.sravni.ru' . rtrim($value, '/') . '/';
    }

    /**
     * @param array<string, mixed> $nextData
     * @return array<int, string>
     */
    private function extractBankAliasesFromNextData(array $nextData): array
    {
        $aliases = [];
        $organizationList = $nextData['props']['initialReduxState']['organization']['list'] ?? [];
        if (! is_array($organizationList)) {
            return [];
        }

        foreach ($organizationList as $organization) {
            if (! is_array($organization)) {
                continue;
            }
            $alias = $organization['alias'] ?? null;
            if (! is_string($alias) || $alias === '' || str_contains($alias, ':')) {
                continue;
            }
            $aliases[] = $alias;
        }

        return array_values(array_unique($aliases));
    }

    /**
     * Универсальный fallback: ищем alias у объектов банков в любой части JSON.
     *
     * @param mixed $node
     * @return array<int, string>
     */
    private function extractBankAliasesDeep(mixed $node): array
    {
        $aliases = [];
        $this->collectBankAliasesDeep($node, $aliases);

        return array_values(array_unique($aliases));
    }

    /**
     * @param mixed $node
     * @param array<int, string> $aliases
     */
    private function collectBankAliasesDeep(mixed $node, array &$aliases): void
    {
        if (! is_array($node)) {
            return;
        }

        if (array_key_exists('alias', $node) && is_string($node['alias'])) {
            $alias = trim($node['alias']);
            $looksLikeBank = array_key_exists('bankName', $node)
                || array_key_exists('bankFullName', $node)
                || array_key_exists('bankId', $node)
                || array_key_exists('organizationId', $node);

            if ($looksLikeBank && $alias !== '' && ! str_contains($alias, ':')) {
                $aliases[] = $alias;
            }
        }

        foreach ($node as $value) {
            $this->collectBankAliasesDeep($value, $aliases);
        }
    }

    private function extractBankAliasFromUrl(string $url): ?string
    {
        if (preg_match('~https?://www\.sravni\.ru/bank/([^/]+)/~u', $url, $match) !== 1) {
            return null;
        }

        $alias = trim((string) ($match[1] ?? ''));
        return $alias !== '' ? $alias : null;
    }

    /**
     * @param array<string, mixed> $nextData
     * @return array<int, string>
     */
    private function extractDepositLinksByProductAliases(array $nextData, ?string $bankAlias): array
    {
        if (! is_string($bankAlias) || $bankAlias === '') {
            return [];
        }

        $productAliases = [];
        $this->collectProductAliases($nextData, $productAliases);
        $productAliases = array_values(array_unique(array_filter($productAliases, fn ($v) => is_string($v) && $v !== '' && ! str_contains($v, ':'))));

        $links = [];
        foreach ($productAliases as $productAlias) {
            $links[] = $this->makeAbsoluteUrl('/bank/' . $bankAlias . '/vklad/' . $productAlias . '/');
        }

        return array_values(array_unique($links));
    }

    /**
     * @param mixed $node
     * @param array<int, string> $productAliases
     */
    private function collectProductAliases(mixed $node, array &$productAliases): void
    {
        if (! is_array($node)) {
            return;
        }

        if (array_key_exists('product', $node) && is_array($node['product'])) {
            $productAlias = $node['product']['alias'] ?? null;
            if (is_string($productAlias) && $productAlias !== '') {
                $productAliases[] = $productAlias;
            }
        }

        foreach ($node as $value) {
            $this->collectProductAliases($value, $productAliases);
        }
    }

    private function extractNextDataJson(string $html): ?string
    {
        if (preg_match('/<script[^>]*id=["\']__NEXT_DATA__["\'][^>]*>(.*?)<\/script>/su', $html, $m) === 1) {
            return trim((string) ($m[1] ?? ''));
        }

        // fallback: window.__NEXT_DATA__ = {...}
        if (preg_match('/window\.__NEXT_DATA__\s*=\s*(\{.*?\})\s*;/su', $html, $m) === 1) {
            return trim((string) ($m[1] ?? ''));
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $nextData
     * @return array<string, mixed>|null
     */
    private function findUpdateDepositPayload(array $nextData): ?array
    {
        $candidates = [];
        $this->collectUpdateDepositCandidates($nextData, $candidates);

        foreach ($candidates as $candidate) {
            if ($this->looksLikeDepositPayload($candidate)) {
                return $candidate;
            }
        }

        // fallback: sometimes payload might be wrapped in data/result object
        foreach ($candidates as $candidate) {
            foreach (['data', 'result', 'deposit'] as $nestedKey) {
                $nested = $candidate[$nestedKey] ?? null;
                if (is_array($nested) && $this->looksLikeDepositPayload($nested)) {
                    return $nested;
                }
            }
        }

        return null;
    }

    /**
     * @param  mixed  $node
     * @param  array<int, array<string, mixed>>  $out
     */
    private function collectUpdateDepositCandidates(mixed $node, array &$out): void
    {
        if (! is_array($node)) {
            return;
        }

        // Query cache style: queries[].queryKey includes "updateDeposit"
        if (isset($node['queries']) && is_array($node['queries'])) {
            foreach ($node['queries'] as $queryKey => $query) {
                if (! is_array($query)) {
                    continue;
                }

                $queryKeyRaw = $query['queryKey'] ?? $query['queryHash'] ?? $queryKey;
                $queryKeyString = is_string($queryKeyRaw) ? $queryKeyRaw : json_encode($queryKeyRaw, JSON_UNESCAPED_UNICODE);
                if (! is_string($queryKeyString) || mb_stripos($queryKeyString, 'updatedeposit') === false) {
                    continue;
                }

                // react-query style
                $stateData = $query['state']['data'] ?? null;
                if (is_array($stateData)) {
                    $out[] = $stateData;
                }

                // RTK query style
                $queryData = $query['data'] ?? null;
                if (is_array($queryData)) {
                    $out[] = $queryData;
                }
            }
        }

        // Direct key updateDeposit
        if (isset($node['updateDeposit']) && is_array($node['updateDeposit'])) {
            $out[] = $node['updateDeposit'];
        }

        // Recurse all nested arrays
        foreach ($node as $value) {
            if (is_array($value)) {
                $this->collectUpdateDepositCandidates($value, $out);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private function looksLikeDepositPayload(array $candidate): bool
    {
        $bankName = $this->arrayGetString($candidate, ['bankDetail', 'bankName']);
        $displayTitle = $this->arrayGetString($candidate, ['displayTitle']);
        $terms = $candidate['data']['terms'] ?? $candidate['terms'] ?? null;

        return $bankName !== '' || $displayTitle !== '' || is_array($terms);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function normalizeDeposit(array $payload): array
    {
        $bankName = $this->arrayGetString($payload, ['bankDetail', 'bankName']);
        $displayTitle = $this->arrayGetString($payload, ['displayTitle']);

        $aboutMap = $this->extractAboutInformation($payload['relatedInformation'] ?? null);

        $minAmount = $this->arrayGetNumber($payload, ['data', 'minAmount', 'amount'])
            ?? $this->arrayGetNumber($payload, ['minAmount', 'amount']);
        $maxAmount = $this->arrayGetNumber($payload, ['data', 'maxAmount', 'amount'])
            ?? $this->arrayGetNumber($payload, ['maxAmount', 'amount']);

        $termsRaw = $payload['data']['terms'] ?? $payload['terms'] ?? [];
        $terms = [];
        if (is_array($termsRaw)) {
            foreach ($termsRaw as $termRow) {
                if (! is_array($termRow)) {
                    continue;
                }

                $termDays = $this->arrayGetInt($termRow, ['period', 'daysValue']);
                $rate = $this->arrayGetNumber($termRow, ['annualRate', 'value']);

                if ($termDays === null || $rate === null) {
                    continue;
                }

                $terms[] = [
                    'term_days' => $termDays,
                    'rate' => $rate,
                ];
            }
        }

        usort($terms, fn (array $a, array $b): int => ((int) $a['term_days']) <=> ((int) $b['term_days']));

        $category = $this->arrayGetString($payload, ['category', 'name']);
        if ($category === '') {
            $category = $this->arrayGetString($payload, ['type', 'name']);
        }

        return [[
            'bank' => $bankName,
            'deposit_name' => $displayTitle,
            'deposit_type' => 'Срочный вклад',
            'category' => $category,
            'features' => [
                'capitalization' => $this->containsCapitalization($aboutMap),
                'online_opening' => $this->toBoolFromYesNo($aboutMap['способ открытия'] ?? null),
                'monthly_interest_payout' => $this->toBoolFromYesNo($aboutMap['выплата процентов'] ?? null),
                'partial_withdrawal' => $this->toBoolFromYesNo($aboutMap['частичное снятие'] ?? null),
                'replenishment' => $this->toBoolFromYesNo($aboutMap['пополнение'] ?? null),
                'early_termination' => $this->toBoolFromYesNo($aboutMap['досрочное закрытие'] ?? null),
                'auto_prolongation' => $this->toBoolFromYesNo($aboutMap['автопролонгация'] ?? null),
                'insurance' => true,
            ],
            'conditions' => [[
                'currency' => 'RUB',
                'amount_ranges' => [[
                    'amount_from' => $minAmount,
                    'amount_to' => $maxAmount,
                    'terms' => $terms,
                ]],
            ]],
        ]];
    }

    /**
     * @param  mixed  $relatedInformation
     * @return array<string, string>
     */
    private function extractAboutInformation(mixed $relatedInformation): array
    {
        $rows = [];
        $this->collectTitleValueRows($relatedInformation, $rows);

        $aboutMap = [];
        foreach ($rows as $row) {
            $title = mb_strtolower(trim((string) ($row['title'] ?? '')));
            $value = trim((string) ($row['value'] ?? ''));
            if ($title === '' || $value === '') {
                continue;
            }

            $aboutMap[$title] = $value;
        }

        return $aboutMap;
    }

    /**
     * @param  mixed  $node
     * @param  array<int, array{title: string, value: string}>  $rows
     */
    private function collectTitleValueRows(mixed $node, array &$rows): void
    {
        if (! is_array($node)) {
            return;
        }

        $titleCandidates = ['displayTitle', 'title', 'name', 'label'];
        $valueCandidates = ['displayValue', 'value', 'description', 'text'];

        $title = '';
        foreach ($titleCandidates as $k) {
            if (isset($node[$k]) && is_scalar($node[$k])) {
                $title = trim((string) $node[$k]);
                if ($title !== '') {
                    break;
                }
            }
        }

        $value = '';
        foreach ($valueCandidates as $k) {
            if (isset($node[$k]) && is_scalar($node[$k])) {
                $value = trim((string) $node[$k]);
                if ($value !== '') {
                    break;
                }
            }
        }

        if ($title !== '' && $value !== '') {
            $rows[] = ['title' => $title, 'value' => $value];
        }

        foreach ($node as $child) {
            if (is_array($child)) {
                $this->collectTitleValueRows($child, $rows);
            }
        }
    }

    /**
     * @param  array<string, string>  $aboutMap
     */
    private function containsCapitalization(array $aboutMap): bool
    {
        foreach ($aboutMap as $value) {
            if (mb_stripos($value, 'капитализац') !== false) {
                return true;
            }
        }

        return false;
    }

    private function toBoolFromYesNo(?string $value): ?bool
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $v = mb_strtolower(trim($value));
        if ($v === 'да') {
            return true;
        }
        if ($v === 'нет') {
            return false;
        }

        // Дополнительная эвристика для "Способ открытия"
        if (mb_stripos($v, 'онлайн') !== false) {
            return true;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $array
     * @param  array<int, string>  $path
     */
    private function arrayGetString(array $array, array $path): string
    {
        $value = $this->arrayGet($array, $path);

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * @param  array<string, mixed>  $array
     * @param  array<int, string>  $path
     */
    private function arrayGetNumber(array $array, array $path): ?float
    {
        $value = $this->arrayGet($array, $path);
        if ($value === null || $value === '' || ! is_numeric((string) $value)) {
            return null;
        }

        return (float) $value;
    }

    /**
     * @param  array<string, mixed>  $array
     * @param  array<int, string>  $path
     */
    private function arrayGetInt(array $array, array $path): ?int
    {
        $value = $this->arrayGet($array, $path);
        if ($value === null || $value === '' || ! preg_match('/^-?\d+$/', (string) $value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param  array<string, mixed>  $array
     * @param  array<int, string>  $path
     */
    private function arrayGet(array $array, array $path): mixed
    {
        $cursor = $array;
        foreach ($path as $key) {
            if (! is_array($cursor) || ! array_key_exists($key, $cursor)) {
                return null;
            }
            $cursor = $cursor[$key];
        }

        return $cursor;
    }

    private function logAndWarn(string $message): void
    {
        $this->logMessage($message);
        Log::warning('[SravniDepositParser] ' . $message);
    }

    private function logMessage(string $message): void
    {
        $this->log[] = $message;
    }
}

