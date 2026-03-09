<?php

namespace App\Services\Parsers\Bankiros;

use App\Models\Bank;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class BankirosParser
{
    private const BASE_URL = 'https://bankiros.ru';

    private const CATALOG_URL = 'https://bankiros.ru/bank';

    private const REQUEST_TIMEOUT = 20;

    private const CONNECT_TIMEOUT = 10;

    private const MAX_RETRIES = 3;

    private const REQUEST_HEADERS = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8',
        'Referer: https://bankiros.ru/',
    ];

    /** @var array<int, string> */
    private array $log = [];

    /** @var callable(string): void|null */
    private $logCallback = null;

    /** @var callable(int): void|null */
    private $progressCallback = null;

    public function setLogCallback(callable $callback): self
    {
        $this->logCallback = $callback;

        return $this;
    }

    public function setProgressCallback(callable $callback): self
    {
        $this->progressCallback = $callback;

        return $this;
    }

    public function parse(?int $limit = null): array
    {
        $this->log = [];
        $this->log('Начало парсинга Bankiros');

        $catalogBanks = $this->collectCatalogBanks($limit);
        $total = count($catalogBanks);

        $this->log('Собрано банков из каталога: ' . $total);

        $normalizedNames = $this->getExistingNormalizedBankNames();
        $result = [];

        foreach ($catalogBanks as $index => $catalogBank) {
            $position = $index + 1;

            $html = $this->fetchHtml($catalogBank['url']);
            if ($html === null) {
                $this->log("[{$position}/{$total}] {$catalogBank['name']} — ошибка загрузки страницы");
                $this->reportProgress($position, $total);
                usleep(rand(500000, 1500000));

                continue;
            }

            $bank = $this->parseBankPage($html, $catalogBank);
            if ($bank === null) {
                $this->log("[{$position}/{$total}] {$catalogBank['name']} — ошибка парсинга");
                $this->reportProgress($position, $total);
                usleep(rand(500000, 1500000));

                continue;
            }

            $normalizedName = $this->normalizeBankName($bank['name']);
            if ($normalizedName !== '' && isset($normalizedNames[$normalizedName])) {
                $this->log("[{$position}/{$total}] {$bank['name']} — дубликат, пропуск");
                $this->reportProgress($position, $total);
                usleep(rand(500000, 1500000));

                continue;
            }

            $savedBank = $this->saveBank($bank);
            $normalizedNames[$normalizedName] = $savedBank['name'];
            $result[] = $savedBank;

            $this->log("[{$position}/{$total}] {$savedBank['name']} — успешно");
            $this->reportProgress($position, $total);
            usleep(rand(500000, 1500000));
        }

        $this->log('Парсинг завершён');

        return $result;
    }

    public function fetchHtml(string $url): ?string
    {
        $resolvedHosts = $this->resolveIpv4Hosts($url);

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            foreach ($resolvedHosts as $resolvedHost) {
                $response = $this->executeCurlRequest($url, $resolvedHost);

                $this->log('Хост: ' . $resolvedHost['host']);
                if ($resolvedHost['ip'] !== null) {
                    $this->log('IPv4: ' . $resolvedHost['ip']);
                }
                $this->log('HTTP статус: ' . $response['http_status']);
                $this->log('curl_errno: ' . $response['errno']);
                $this->log('curl_error: ' . $response['error']);
                $this->log('Размер HTML: ' . $response['size']);

                if ($response['body'] !== null && $response['http_status'] >= 200 && $response['http_status'] < 400) {
                    return $response['body'];
                }
            }

            if ($attempt < self::MAX_RETRIES) {
                sleep(2);
            }
        }

        return null;
    }

    /**
     * @return array<int, array{name: string, url: string, logo: string}>
     */
    private function collectCatalogBanks(?int $limit = null): array
    {
        $banks = [];
        $seen = [];
        $page = 1;

        while (true) {
            $url = self::CATALOG_URL . ($page > 1 ? '?page=' . $page : '');
            $html = $this->fetchHtml($url);

            if ($html === null) {
                break;
            }

            $crawler = new Crawler($html);
            $rows = $crawler->filter('tr.row.body');

            if ($rows->count() === 0) {
                break;
            }

            $foundOnPage = 0;

            foreach ($rows as $row) {
                $rowCrawler = new Crawler($row);
                $link = $rowCrawler->filter('div.h4 a');

                if ($link->count() === 0) {
                    continue;
                }

                $name = trim($link->first()->text(''));
                $href = trim((string) $link->first()->attr('href'));

                if ($name === '' || $href === '') {
                    continue;
                }

                $bankUrl = $this->absoluteUrl($href);
                if ($bankUrl === null) {
                    continue;
                }

                $logo = '';
                $logoNode = $rowCrawler->filter('img[data-url-img]');
                if ($logoNode->count() > 0) {
                    $logo = trim((string) $logoNode->first()->attr('data-url-img'));
                }
                $slug = basename(parse_url($bankUrl, PHP_URL_PATH) ?: '');

                if ($slug === '' || isset($seen[$slug])) {
                    continue;
                }

                $seen[$slug] = true;
                $banks[] = [
                    'name' => $name,
                    'url' => $bankUrl,
                    'logo' => $logo,
                ];
                $foundOnPage++;

                if ($limit && count($banks) >= $limit) {
                    break 2;
                }
            }

            if ($foundOnPage === 0) {
                break;
            }

            $page++;
        }

        return $banks;
    }

    /**
     * @param  array{name: string, url: string, logo: string}  $catalogBank
     * @return array{name: string, logo: string, head_office: string, site: string, phones: array<int, string>, registration_number: string, registration_date: string}|null
     */
    private function parseBankPage(string $html, array $catalogBank): ?array
    {
        $crawler = new Crawler($html);
        $fields = $this->extractConditionFields($crawler);

        $name = trim($catalogBank['name']);
        if ($name === '') {
            $h1 = $crawler->filter('h1');
            if ($h1->count() > 0) {
                $name = trim($h1->first()->text(''));
            }
        }

        if ($name === '') {
            return null;
        }

        return [
            'name' => $name,
            'logo' => $catalogBank['logo'],
            'head_office' => $fields['Головной офис'] ?? '',
            'site' => $this->extractSite($fields['Сайт_node'] ?? null, $fields['Сайт'] ?? ''),
            'phones' => $this->extractPhones($fields['Телефоны'] ?? ''),
            'registration_number' => $fields['Регистрационный номер'] ?? '',
            'registration_date' => $fields['Дата регистрации Банком России'] ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractConditionFields(Crawler $crawler): array
    {
        $fields = [];
        $leftCells = $crawler->filter('[class*="conditions-block__cell-left"]');

        foreach ($leftCells as $leftCell) {
            $labelCrawler = new Crawler($leftCell);
            $label = trim(preg_replace('/\s+/u', ' ', $labelCrawler->text('')) ?? '');

            if ($label === '') {
                continue;
            }

            $rightNode = $leftCell->nextSibling;
            while ($rightNode !== null && $rightNode->nodeType !== XML_ELEMENT_NODE) {
                $rightNode = $rightNode->nextSibling;
            }

            if ($rightNode === null) {
                continue;
            }

            $rightCrawler = new Crawler($rightNode);
            $class = (string) $rightCrawler->attr('class');

            if (! str_contains($class, 'conditions-block__cell-right')) {
                continue;
            }

            $fields[$label] = trim(preg_replace('/\s+/u', ' ', $rightCrawler->text('')) ?? '');
            $fields[$label . '_node'] = $rightCrawler;
        }

        return $fields;
    }

    private function extractSite(?Crawler $siteNode, string $fallback): string
    {
        if ($siteNode !== null) {
            foreach (['data-js-modal-loginbank', 'data-js-modal-loginBank'] as $attribute) {
                $selector = sprintf('[%s]', $attribute);
                if ($siteNode->filter($selector)->count() > 0) {
                    $value = trim((string) $siteNode->filter($selector)->first()->attr($attribute));
                    if ($value !== '') {
                        return $this->normalizeSite($value);
                    }
                }
            }

            if ($siteNode->filter('a[href]')->count() > 0) {
                $href = trim((string) $siteNode->filter('a[href]')->first()->attr('href'));
                if ($href !== '') {
                    return $this->normalizeSite($href);
                }
            }
        }

        return $fallback !== '' ? $this->normalizeSite($fallback) : '';
    }

    /**
     * @return array<int, string>
     */
    private function extractPhones(string $rawPhones): array
    {
        if ($rawPhones === '') {
            return [];
        }

        $parts = preg_split('/[\n,;]+/u', $rawPhones) ?: [];
        $phones = [];

        foreach ($parts as $part) {
            $phone = trim($part);
            if ($phone !== '' && ! in_array($phone, $phones, true)) {
                $phones[] = $phone;
            }
        }

        return $phones;
    }

    /**
     * @param  array{name: string, logo: string, head_office: string, site: string, phones: array<int, string>, registration_number: string, registration_date: string}  $bank
     * @return array{name: string, logo: string, head_office: string, site: string, phones: array<int, string>, registration_number: string, registration_date: string}
     */
    private function saveBank(array $bank): array
    {
        $slug = Str::slug($bank['name']);
        $logoPath = $this->downloadLogo($bank['logo'], $slug);

        Bank::create([
            'name' => $bank['name'],
            'slug' => $slug !== '' ? $slug : Str::slug(Str::random(8)),
            'website' => $bank['site'] !== '' ? $bank['site'] : null,
            'phone' => $bank['phones'][0] ?? null,
            'head_office' => $bank['head_office'] !== '' ? $bank['head_office'] : null,
            'license_number' => $bank['registration_number'] !== '' ? $bank['registration_number'] : null,
            'license_date' => $this->normalizeDate($bank['registration_date']),
            'logo' => $logoPath,
            'description' => null,
        ]);

        $bank['logo'] = $logoPath ?? '';

        return $bank;
    }

    private function downloadLogo(string $logoUrl, string $slug): ?string
    {
        if ($logoUrl === '' || $slug === '') {
            return null;
        }

        $binary = $this->fetchBinary($this->absoluteUrl($logoUrl) ?? $logoUrl);
        if ($binary === null || $binary === '') {
            return null;
        }

        $path = 'banks/' . $slug . '.svg';
        Storage::disk('public')->put($path, $binary);

        return '/storage/' . $path;
    }

    private function fetchBinary(string $url): ?string
    {
        foreach ($this->resolveIpv4Hosts($url) as $resolvedHost) {
            $response = $this->executeCurlRequest($url, $resolvedHost);

            if ($response['body'] !== null && $response['http_status'] >= 200 && $response['http_status'] < 400) {
                return $response['body'];
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function getExistingNormalizedBankNames(): array
    {
        $normalized = [];

        foreach (Bank::query()->pluck('name') as $name) {
            $prepared = $this->normalizeBankName((string) $name);
            if ($prepared !== '') {
                $normalized[$prepared] = (string) $name;
            }
        }

        return $normalized;
    }

    private function normalizeBankName(string $name): string
    {
        $normalized = mb_strtolower(trim($name));
        $normalized = preg_replace('/^банк\s+/u', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    private function normalizeSite(string $site): string
    {
        $site = trim($site);
        if ($site === '') {
            return '';
        }

        if (! preg_match('~^https?://~i', $site)) {
            $site = 'https://' . ltrim($site, '/');
        }

        return $site;
    }

    private function normalizeDate(string $date): ?string
    {
        $date = trim($date);
        if ($date === '') {
            return null;
        }

        if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $date, $matches)) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : null;
    }

    private function absoluteUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }

        if (preg_match('~^https?://~i', $url)) {
            return $url;
        }

        return self::BASE_URL . '/' . ltrim($url, '/');
    }

    /**
     * @return array<int, array{host: string, port: int, ip: string|null}>
     */
    private function resolveIpv4Hosts(string $url): array
    {
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME) ?: 'https';
        $port = (int) (parse_url($url, PHP_URL_PORT) ?: ($scheme === 'http' ? 80 : 443));

        if (! is_string($host) || $host === '') {
            return [['host' => '', 'port' => $port, 'ip' => null]];
        }

        $ips = [];

        if (function_exists('dns_get_record')) {
            $records = dns_get_record($host, DNS_A);
            if (is_array($records)) {
                foreach ($records as $record) {
                    $ip = $record['ip'] ?? null;
                    if (is_string($ip) && $ip !== '') {
                        $ips[] = $ip;
                    }
                }
            }
        }

        if ($ips === [] && function_exists('gethostbynamel')) {
            $fallbackIps = gethostbynamel($host);
            if (is_array($fallbackIps)) {
                foreach ($fallbackIps as $ip) {
                    if (is_string($ip) && $ip !== '') {
                        $ips[] = $ip;
                    }
                }
            }
        }

        $ips = array_values(array_unique($ips));

        if ($ips === []) {
            return [['host' => $host, 'port' => $port, 'ip' => null]];
        }

        return array_map(
            fn (string $ip): array => ['host' => $host, 'port' => $port, 'ip' => $ip],
            $ips,
        );
    }

    /**
     * @param  array{host: string, port: int, ip: string|null}  $resolvedHost
     * @return array{body: string|null, http_status: int, errno: int, error: string, size: int}
     */
    private function executeCurlRequest(string $url, array $resolvedHost): array
    {
        $ch = curl_init();

        if ($ch === false) {
            return [
                'body' => null,
                'http_status' => 0,
                'errno' => -1,
                'error' => 'curl_init failed',
                'size' => 0,
            ];
        }

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => self::REQUEST_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_HTTPHEADER => self::REQUEST_HEADERS,
        ];

        if ($resolvedHost['host'] !== '' && $resolvedHost['ip'] !== null) {
            $options[CURLOPT_RESOLVE] = [
                $resolvedHost['host'] . ':' . $resolvedHost['port'] . ':' . $resolvedHost['ip'],
            ];
        }

        curl_setopt_array($ch, $options);

        $body = curl_exec($ch);
        $response = [
            'body' => is_string($body) ? $body : null,
            'http_status' => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'errno' => curl_errno($ch),
            'error' => curl_error($ch),
            'size' => is_string($body) ? strlen($body) : 0,
        ];

        curl_close($ch);

        return $response;
    }

    private function reportProgress(int $processed, int $total): void
    {
        if ($this->progressCallback === null) {
            return;
        }

        $percent = $total > 0 ? (int) round(($processed / $total) * 100) : 100;
        ($this->progressCallback)($percent);
    }

    private function log(string $message): void
    {
        $this->log[] = $message;

        if ($this->logCallback !== null) {
            ($this->logCallback)($message);
        }
    }
}
