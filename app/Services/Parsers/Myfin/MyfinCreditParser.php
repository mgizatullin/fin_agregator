<?php

namespace App\Services\Parsers\Myfin;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class MyfinCreditParser
{
    private const CATALOG_URL = 'https://ru.myfin.by/kredity';

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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parse(?int $limit = null, ?string $html = null): array
    {
        $this->log = [];
        $this->log('Начало парсинга Myfin кредитов');

        $providedHtml = is_string($html) ? trim($html) : '';
        $pages = [];

        if ($providedHtml !== '') {
            $this->log('Используется вручную вставленный HTML каталога');
            $pages[] = $providedHtml;
        } else {
            $mainHtml = $this->fetchHtml(self::CATALOG_URL);
            if ($mainHtml === null || trim($mainHtml) === '') {
                $this->log('Каталог кредитов Myfin не загружен');

                return [];
            }

            $pages[] = $mainHtml;

            foreach ($this->extractPaginationUrls($mainHtml) as $pageUrl) {
                if ($limit !== null && $limit > 0 && count($pages) > $limit) {
                    break;
                }

                $pageHtml = $this->fetchHtml($pageUrl);
                if (is_string($pageHtml) && trim($pageHtml) !== '') {
                    $pages[] = $pageHtml;
                }
            }
        }

        $credits = [];
        $seen = [];

        foreach ($pages as $pageIndex => $pageHtml) {
            $pageCredits = $this->parseCatalogPage($pageHtml);
            $this->log('Собрано кредитов со страницы ' . ($pageIndex + 1) . ': ' . count($pageCredits));

            foreach ($pageCredits as $credit) {
                $key = mb_strtolower(($credit['bank'] ?? '') . '|' . ($credit['name'] ?? '') . '|' . ($credit['slug'] ?? ''));
                if (isset($seen[$key])) {
                    continue;
                }

                $credits[] = $credit;
                $seen[$key] = true;

                if ($limit !== null && $limit > 0 && count($credits) >= $limit) {
                    break 2;
                }
            }
        }

        $total = count($credits);
        $this->log('Собрано кредитов всего: ' . $total);

        foreach ($credits as $index => $credit) {
            $position = $index + 1;
            $this->log("[{$position}/{$total}] {$credit['bank']} — {$credit['name']}");
            $this->reportProgress($position, $total);
        }

        $this->log('Парсинг завершён');

        return $credits;
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

            $this->log('URL: ' . $url);
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
     * @return array<int, array<string, mixed>>
     */
    private function parseCatalogPage(string $html): array
    {
        $crawler = new Crawler($html);
        $items = $crawler->filter('div[data-container][data-id]');
        $credits = [];

        foreach ($items as $itemNode) {
            $item = new Crawler($itemNode);
            $credit = $this->parseCreditItem($item);

            if ($credit === null) {
                continue;
            }

            $credits[] = $credit;
        }

        return $credits;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseCreditItem(Crawler $item): ?array
    {
        $jsonLd = $this->extractFinancialProduct($item);

        $bank = $this->extractProviderName($jsonLd);
        if ($bank === '') {
            $bank = $this->extractText($item, '.cards-list-item__logo img[alt]', 'alt');
        }
        if ($bank === '') {
            $bank = $this->extractAttribute($item, '.cards-list-item__logo img[alt]', 'alt');
        }

        $name = $this->extractProductName($jsonLd);
        if ($name === '') {
            $name = $this->extractText($item, '.cards-list-item__cell.cards-list-item__name.tablet-hidden > div:first-child');
        }
        if ($name === '') {
            $name = $this->extractText($item, '.cards-list-item__name.desk-hidden');
        }

        $name = $this->normalizeName($name);
        if ($bank === '' || $name === '') {
            return null;
        }

        $rateText = $this->extractLabeledValue($item, 'Ставка');
        $pskText = $this->extractLabeledValue($item, 'Полная стоимость кредита');
        $amountText = $this->extractLabeledValue($item, 'Сумма');
        $termText = $this->extractLabeledValue($item, 'Срок');
        $detailsText = $this->collapseWhitespace($item->text(''));

        if ($amountText === '') {
            $amountText = $this->extractText($item, '.cards-list-item__cell .cards-list-item__rate.accent.fs-20');
        }

        if ($termText === '') {
            $termText = $this->extractText($item, '.cards-list-item__cell .gray-alt.fs-14');
        }

        $ageRange = $this->extractAgeRange($detailsText);

        return [
            'bank' => $bank,
            'name' => $name,
            'slug' => Str::slug($name) ?: 'credit',
            'rate' => $this->extractMinDecimal($rateText),
            'psk' => $this->extractMaxDecimal($pskText),
            'max_amount' => $this->extractMaxAmount($amountText),
            'term_months' => $this->extractMaxMonths($termText),
            'income_proof_required' => $this->extractIncomeProofRequired($item, $detailsText),
            'age_min' => $ageRange['min'],
            'age_max' => $ageRange['max'],
            'decision' => $this->nullIfEmpty($this->extractLabeledValue($item, 'Решение')),
            'receive_method' => $this->nullIfEmpty($this->extractLabeledValue($item, 'Способ получения')),
            'payment_type' => $this->nullIfEmpty($this->extractLabeledValue($item, 'Тип выплат')),
            'penalty' => $this->nullIfEmpty($this->extractLabeledValue($item, 'Штраф')),
            'no_collateral' => $this->extractFeatureFlag($item, 'Без залога'),
            'no_guarantors' => $this->extractFeatureFlag($item, 'Без поручителей'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractFinancialProduct(Crawler $item): ?array
    {
        foreach ($item->filter('script[type="application/ld+json"]') as $scriptNode) {
            $json = trim((string) $scriptNode->textContent);
            if ($json === '') {
                continue;
            }

            $decoded = json_decode($json, true);
            if (! is_array($decoded)) {
                continue;
            }

            $type = $decoded['@type'] ?? null;
            if (is_string($type) && mb_strtolower($type) === 'financialproduct') {
                return $decoded;
            }
        }

        return null;
    }

    private function extractProviderName(?array $jsonLd): string
    {
        $provider = $jsonLd['provider']['name'] ?? null;

        return is_string($provider) ? trim($provider) : '';
    }

    private function extractProductName(?array $jsonLd): string
    {
        $name = $jsonLd['name'] ?? null;

        return is_string($name) ? trim($name) : '';
    }

    private function normalizeName(string $name): string
    {
        $name = $this->collapseWhitespace($name);
        $name = preg_replace('/^Кредит\s+/u', '', $name) ?? $name;

        return trim($name);
    }

    private function extractLabeledValue(Crawler $item, string $label): string
    {
        foreach ($item->filter('.card-full-info-requirements__item') as $node) {
            $row = new Crawler($node);
            $left = $this->collapseWhitespace($row->filter('.card-full-info-requirements__left')->text(''));

            if ($left === '' || ! str_contains(mb_strtolower($left), mb_strtolower($label))) {
                continue;
            }

            $right = $this->collapseWhitespace($row->filter('.card-full-info-requirements__right')->text(''));
            if ($right !== '') {
                return $right;
            }
        }

        foreach ($item->filter('.cards-list-item__cell') as $node) {
            $cell = new Crawler($node);
            $title = $this->collapseWhitespace($cell->filter('.cards-list-item__title')->text(''));
            if ($title === '' || ! str_contains(mb_strtolower($title), mb_strtolower($label))) {
                continue;
            }

            $sum = $this->collapseWhitespace($cell->filter('.cards-list-item__sum')->text(''));
            if ($sum !== '') {
                return $sum;
            }

            $rate = $this->collapseWhitespace($cell->filter('.cards-list-item__rate')->text(''));
            if ($rate !== '') {
                return $rate;
            }

            $secondary = $this->collapseWhitespace($cell->filter('.gray-alt')->text(''));
            if ($secondary !== '') {
                return $secondary;
            }
        }

        return '';
    }

    /**
     * @return array{min: int|null, max: int|null}
     */
    private function extractAgeRange(string $text): array
    {
        $normalized = mb_strtolower($text);

        if (preg_match('/возраст[^\d]{0,30}(\d{2})\s*[-–—]\s*(\d{2})/u', $normalized, $matches)) {
            return [
                'min' => (int) $matches[1],
                'max' => (int) $matches[2],
            ];
        }

        $min = null;
        $max = null;

        if (preg_match('/возраст[^\d]{0,40}от\s*(\d{2})/u', $normalized, $matches)) {
            $min = (int) $matches[1];
        }

        if (preg_match('/возраст[^\d]{0,40}до\s*(\d{2})/u', $normalized, $matches)) {
            $max = (int) $matches[1];
        }

        return ['min' => $min, 'max' => $max];
    }

    private function extractIncomeProofRequired(Crawler $item, string $detailsText): ?bool
    {
        foreach ($item->filter('.card-full-info__list-icons-item') as $node) {
            $icon = new Crawler($node);
            $text = $this->collapseWhitespace($icon->text(''));
            $normalizedText = mb_strtolower($text);

            if (! str_contains($normalizedText, 'справк') || ! str_contains($normalizedText, 'доход')) {
                continue;
            }

            $class = (string) ($node->attributes->getNamedItem('class')?->nodeValue ?? '');

            return ! str_contains(' ' . $class . ' ', ' none ');
        }

        $normalizedDetails = mb_strtolower($detailsText);

        if (str_contains($normalizedDetails, 'без справки о доходах')) {
            return false;
        }

        if (str_contains($normalizedDetails, 'требуется справка о доходах')) {
            return true;
        }

        return null;
    }

    private function extractFeatureFlag(Crawler $item, string $label): ?bool
    {
        foreach ($item->filter('.card-full-info__list-icons-item') as $node) {
            $icon = new Crawler($node);
            $text = $this->collapseWhitespace($icon->text(''));

            if ($text === '' || ! str_contains(mb_strtolower($text), mb_strtolower($label))) {
                continue;
            }

            $class = (string) ($node->attributes->getNamedItem('class')?->nodeValue ?? '');

            return ! str_contains(' ' . $class . ' ', ' none ');
        }

        return null;
    }

    private function extractMinDecimal(string $text): ?float
    {
        $numbers = $this->extractDecimalNumbers($text);

        if ($numbers === []) {
            return null;
        }

        return min($numbers);
    }

    private function extractMaxDecimal(string $text): ?float
    {
        $numbers = $this->extractDecimalNumbers($text);

        if ($numbers === []) {
            return null;
        }

        return max($numbers);
    }

    /**
     * @return array<int, float>
     */
    private function extractDecimalNumbers(string $text): array
    {
        $text = str_replace(',', '.', $text);
        preg_match_all('/\d+(?:\.\d+)?/u', $text, $matches);

        return array_map(static fn (string $value): float => (float) $value, $matches[0] ?? []);
    }

    private function extractMaxAmount(string $text): ?float
    {
        $matches = [];
        preg_match_all('/(\d+(?:[.,]\d+)?)\s*(тыс|млн|млрд)?\s*[₽р]/ui', $text, $matches, PREG_SET_ORDER);

        if ($matches === []) {
            return null;
        }

        $amounts = [];

        foreach ($matches as $match) {
            $value = (float) str_replace(',', '.', $match[1]);
            $unit = mb_strtolower($match[2] ?? '');

            $multiplier = match ($unit) {
                'тыс' => 1_000,
                'млн' => 1_000_000,
                'млрд' => 1_000_000_000,
                default => 1,
            };

            $amounts[] = $value * $multiplier;
        }

        return $amounts === [] ? null : max($amounts);
    }

    private function extractMaxMonths(string $text): ?int
    {
        $matches = [];
        preg_match_all('/(\d+(?:[.,]\d+)?)\s*(месяц(?:ев|а)?|мес\.?|год(?:а|ов)?|лет)/ui', $text, $matches, PREG_SET_ORDER);

        if ($matches === []) {
            return null;
        }

        $months = [];

        foreach ($matches as $match) {
            $value = (float) str_replace(',', '.', $match[1]);
            $unit = mb_strtolower($match[2]);

            if (str_contains($unit, 'год') || str_contains($unit, 'лет')) {
                $value *= 12;
            }

            $months[] = (int) round($value);
        }

        return $months === [] ? null : max($months);
    }

    /**
     * @return array<int, string>
     */
    private function extractPaginationUrls(string $html): array
    {
        $crawler = new Crawler($html);
        $paginationButton = $crawler->filter('#product-list-pagination');
        if ($paginationButton->count() === 0) {
            return [];
        }

        $encoded = $paginationButton->attr('data-urls');

        if (! is_string($encoded) || trim($encoded) === '') {
            return [];
        }

        $decoded = base64_decode($encoded, true);
        if (! is_string($decoded) || $decoded === '') {
            return [];
        }

        $urls = json_decode($decoded, true);
        if (! is_array($urls)) {
            return [];
        }

        return array_values(array_filter(array_map(function (mixed $url): ?string {
            if (! is_string($url) || trim($url) === '') {
                return null;
            }

            return $this->absoluteUrl($url);
        }, $urls)));
    }

    private function extractText(Crawler $crawler, string $selector, ?string $attribute = null): string
    {
        if ($crawler->filter($selector)->count() === 0) {
            return '';
        }

        if ($attribute !== null) {
            $value = $crawler->filter($selector)->attr($attribute);

            return is_string($value) ? trim($value) : '';
        }

        return $this->collapseWhitespace($crawler->filter($selector)->text(''));
    }

    private function extractAttribute(Crawler $crawler, string $selector, string $attribute): string
    {
        if ($crawler->filter($selector)->count() === 0) {
            return '';
        }

        $value = $crawler->filter($selector)->attr($attribute);

        return is_string($value) ? trim($value) : '';
    }

    private function absoluteUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return 'https://ru.myfin.by' . (str_starts_with($url, '/') ? $url : '/' . $url);
    }

    private function collapseWhitespace(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? $value);
    }

    private function nullIfEmpty(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function reportProgress(int $current, int $total): void
    {
        if ($total <= 0 || ! $this->progressCallback) {
            return;
        }

        $percent = (int) floor(($current / $total) * 100);
        ($this->progressCallback)($percent);
    }

    private function log(string $message): void
    {
        $this->log[] = $message;

        if ($this->logCallback) {
            ($this->logCallback)($message);
        }
    }
}
