<?php

namespace App\Services\Parsers\Myfin;

use Symfony\Component\DomCrawler\Crawler;

class MyfinBankParser
{
    private const CATALOG_URL = 'https://ru.myfin.by/banki';

    private const REQUEST_TIMEOUT = 20;

    private const CONNECT_TIMEOUT = 10;

    private const MAX_RETRIES = 3;

    private const REQUEST_HEADERS = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8',
        'Referer: https://ru.myfin.by/',
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

    public function parse(?int $limit = null, ?string $html = null): array
    {
        $this->log = [];
        $this->log('Начало парсинга Myfin');

        $providedHtml = is_string($html) ? trim($html) : '';
        if ($providedHtml !== '') {
            $this->log('Используется вручную вставленный HTML каталога');
            $html = $providedHtml;
        } else {
            $html = $this->fetchHtml(self::CATALOG_URL);
        }

        if ($html === null || trim($html) === '') {
            $this->log('Каталог Myfin не загружен');

            return [];
        }

        $banks = $this->parseCatalog($html, $limit);
        $total = count($banks);

        $this->log('Собрано банков из каталога: ' . $total);

        foreach ($banks as $index => $bank) {
            $position = $index + 1;
            $this->log("[{$position}/{$total}] {$bank['name']} — успешно");
            $this->reportProgress($position, $total);
        }

        $this->log('Парсинг завершён');

        return $banks;
    }

    public function fetchHtml(string $url): ?string
    {
        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            $ch = curl_init();

            if ($ch === false) {
                $this->log('HTTP статус: 0');
                $this->log('curl_errno: -1');
                $this->log('curl_error: curl_init failed');
                $this->log('Размер HTML: 0');

                return null;
            }

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => self::REQUEST_TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                CURLOPT_HTTPHEADER => self::REQUEST_HEADERS,
            ]);

            $html = curl_exec($ch);
            $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $size = is_string($html) ? strlen($html) : 0;

            curl_close($ch);

            $this->log('HTTP статус: ' . $httpStatus);
            $this->log('curl_errno: ' . $errno);
            $this->log('curl_error: ' . $error);
            $this->log('Размер HTML: ' . $size);

            if ($html !== false && $httpStatus >= 200 && $httpStatus < 400 && $size > 0) {
                return $html;
            }

            if ($html === false && $attempt < self::MAX_RETRIES) {
                sleep(2);
            }
        }

        return null;
    }

    /**
     * @return array<int, array{name: string, logo: string, head_office: string, site: string|null, phones: array<int, string>, registration_number: string, registration_date: string}>
     */
    private function parseCatalog(string $html, ?int $limit = null): array
    {
        $crawler = new Crawler($html);
        $items = $crawler->filter('.banks-list__item');
        $banks = [];
        $seen = [];

        foreach ($items as $itemNode) {
            $item = new Crawler($itemNode);

            $name = $this->extractText($item, '.bank-info__item.bank-info__name a');
            if ($name === '') {
                continue;
            }

            $key = mb_strtolower($name);
            if (isset($seen[$key])) {
                continue;
            }

            $subtitle = $this->extractText($item, '.bank-info__subtitle');
            $logo = $this->extractAttribute($item, '.bank-detailed-info__logo img[data-url-img], .bank-info__logo img[data-url-img]', 'data-url-img');
            $address = $this->extractText($item, '.bank-info__item.bank-info__adres');
            $phone = $this->extractText($item, '.bank-info__item.bank-info__phone a[href^="tel:"]');

            $banks[] = [
                'name' => $name,
                'logo' => $this->absoluteUrl($logo),
                'head_office' => $address,
                'site' => null,
                'phones' => $phone !== '' ? [$phone] : [],
                'registration_number' => $this->extractRegistrationNumber($subtitle),
                'registration_date' => $this->extractRegistrationDate($subtitle),
            ];

            $seen[$key] = true;

            if ($limit !== null && $limit > 0 && count($banks) >= $limit) {
                break;
            }
        }

        return $banks;
    }

    private function extractRegistrationNumber(string $content): string
    {
        if (preg_match('/Лиц(?:ензия|\.?)\s*(?:ЦБ\s*РФ)?\s*№\s*([0-9]+)/u', $content, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function extractRegistrationDate(string $content): string
    {
        if (preg_match('/\bот\s+(\d{2}\.\d{2}\.\d{4})/u', $content, $matches)) {
            return $matches[1];
        }

        if (preg_match('/Дата\s+регистрации\s+Банком\s+России:\s*(\d{2}\.\d{2}\.\d{4})/u', $content, $matches)) {
            return $matches[1];
        }

        return '';
    }


    private function extractText(Crawler $crawler, string $selector): string
    {
        $node = $crawler->filter($selector);

        return $node->count() > 0
            ? $this->normalizeWhitespace($node->first()->text(''))
            : '';
    }

    private function extractAttribute(Crawler $crawler, string $selector, string $attribute): string
    {
        $node = $crawler->filter($selector);

        return $node->count() > 0
            ? trim((string) $node->first()->attr($attribute))
            : '';
    }

    private function absoluteUrl(string $url): string
    {
        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }

        if (preg_match('~^https?://~i', $url)) {
            return $url;
        }

        return 'https://ru.myfin.by/' . ltrim($url, '/');
    }

    private function normalizeWhitespace(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
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
