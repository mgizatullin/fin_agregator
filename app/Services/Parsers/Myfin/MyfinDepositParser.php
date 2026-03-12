<?php

namespace App\Services\Parsers\Myfin;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class MyfinDepositParser
{
    private const CATALOG_URL = 'https://ru.myfin.by/vklady';

    private const REQUEST_TIMEOUT = 20;

    private const CONNECT_TIMEOUT = 10;

    private const MAX_RETRIES = 3;

    private const REQUEST_HEADERS = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8',
        'Referer: https://ru.myfin.by/',
    ];

    private const DEPOSIT_TYPES = [
        'Накопительный счет',
        'Классический',
        'Пенсионный',
        'Инвестиционный',
        'Для клиентов банка',
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
    public function parse(?int $limit = null, ?string $html = null, ?string $categoryUrl = null, ?string $categoryName = null): array
    {
        $this->log = [];
        $this->log('Начало парсинга Myfin вкладов');

        $catalogCategory = [
            'name' => $categoryName ?? 'Все',
            'url' => $categoryUrl ?? self::CATALOG_URL,
        ];

        $providedHtml = is_string($html) ? trim($html) : '';
        $pages = [];

        if ($providedHtml !== '') {
            $this->log('Используется вручную вставленный HTML каталога');
            $pages[] = $providedHtml;
        } else {
            $baseUrl = is_string($categoryUrl) && $categoryUrl !== '' ? $categoryUrl : self::CATALOG_URL;
            $this->log('Каталог: ' . $baseUrl);

            $mainHtml = $this->fetchHtml($baseUrl);
            if ($mainHtml === null || trim($mainHtml) === '') {
                $this->log('Каталог вкладов Myfin не загружен');

                return [];
            }

            $pages[] = $mainHtml;

            foreach ($this->extractPaginationUrls($mainHtml) as $pageUrl) {
                $pageHtml = $this->fetchHtml($pageUrl);
                if (is_string($pageHtml) && trim($pageHtml) !== '') {
                    $pages[] = $pageHtml;
                }
            }
        }

        $deposits = [];
        $seen = [];

        foreach ($pages as $pageIndex => $pageHtml) {
            $pageDeposits = $this->parseCatalogPage($pageHtml);
            $this->log('Собрано вкладов со страницы ' . ($pageIndex + 1) . ': ' . count($pageDeposits));

            foreach ($pageDeposits as $deposit) {
                $key = mb_strtolower(($deposit['bank'] ?? '') . '|' . ($deposit['name'] ?? '') . '|' . ($deposit['slug'] ?? ''));
                if (isset($seen[$key])) {
                    continue;
                }

                $deposit['catalog_category'] = $catalogCategory;
                $deposits[] = $deposit;
                $seen[$key] = true;
            }

            if ($limit !== null && $limit > 0 && count($deposits) >= $limit) {
                break;
            }
        }

        if ($limit !== null && $limit > 0) {
            $deposits = array_slice($deposits, 0, $limit);
        }

        foreach ($deposits as $index => $deposit) {
            $position = $index + 1;
            $detailsUrl = $deposit['_details_url'] ?? null;

            if (is_string($detailsUrl) && $detailsUrl !== '') {
                $this->log("[{$position}] Загрузка страницы вклада: {$detailsUrl}");
                $detailHtml = $this->fetchHtml($detailsUrl);

                if (is_string($detailHtml) && trim($detailHtml) !== '') {
                    $deposits[$index] = $this->mergeDepositWithDetails($deposit, $this->parseDepositDetails($detailHtml));
                } else {
                    $this->log("[{$position}] Не удалось загрузить детальную страницу вклада");
                }
            } else {
                $this->log("[{$position}] У вклада нет ссылки на детальную страницу");
            }
        }

        $total = count($deposits);
        $this->log('Собрано вкладов всего: ' . $total);

        foreach ($deposits as $index => $deposit) {
            $position = $index + 1;
            unset($deposits[$index]['_details_url']);
            $this->log("[{$position}/{$total}] {$deposits[$index]['bank']} — {$deposits[$index]['name']}");
            $this->reportProgress($position, $total);
        }

        $this->log('Парсинг завершён');

        return $deposits;
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
        $items = $crawler->filter('.cards-list-item.cards-list-item--requirements');
        $deposits = [];

        foreach ($items as $itemNode) {
            $item = new Crawler($itemNode);
            $deposit = $this->parseDepositItem($item);

            if ($deposit === null) {
                continue;
            }

            $deposits[] = $deposit;
        }

        return $deposits;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseDepositItem(Crawler $item): ?array
    {
        $bank = $this->extractAttribute($item, '.cards-list-item__logo img[alt]', 'alt');
        $name = $this->extractText($item, '.cards-list-item__cell.cards-list-item__name.tablet-hidden > div:first-child');

        if ($name === '') {
            $name = $this->extractText($item, '.cards-list-item__name.desk-hidden');
        }

        $bank = $this->collapseWhitespace($bank);
        $name = $this->collapseWhitespace($name);

        if ($bank === '' || $name === '') {
            return null;
        }

        $detailsLink = $this->extractAttribute($item, '.card-requirements__footer-link a[href]', 'href');
        if ($detailsLink === '') {
            $detailsLink = $this->extractAttribute($item, 'a[href*="/vklady/"]', 'href');
        }

        $detailsUrl = $detailsLink !== '' ? $this->absoluteUrl($detailsLink) : null;
        $slug = $this->extractSlugFromUrl($detailsLink) ?: (Str::slug($name) ?: 'deposit');

        $rateText = $this->extractLabeledValue($item, 'Ставка');
        $termText = $this->extractLabeledValue($item, 'Срок');
        $amountText = $this->extractLabeledValue($item, 'Сумма');

        if ($rateText === '') {
            $rateText = $this->extractText($item, '.cards-list-item__cell.cards-list-item__sum-cont .cards-list-item__sum');
        }

        if ($termText === '') {
            $termText = $this->extractText($item, '.cards-list-item__cell.cards-list-item__rate-cont .cards-list-item__value');
        }

        if ($amountText === '') {
            $amountText = $this->extractText($item, '.cards-list-item__cell.cards-list-item__rate-cont .cards-list-item__rate');
        }

        $iconItems = $this->extractIconItems($item);
        $type = $this->extractDepositType($item, $iconItems);
        $rateBounds = $this->extractRateBounds($rateText);

        return [
            'bank' => $bank,
            'name' => $name,
            'slug' => $slug,
            '_details_url' => $detailsUrl,
            'min_rate' => $rateBounds['min'],
            'max_rate' => $rateBounds['max'],
            'min_term_days' => $this->extractMinInt($termText),
            'max_term_days' => $this->extractMaxInt($termText),
            'term_days' => $this->extractOfferTermDays($rateText),
            'term_months' => $this->extractMaxMonthsFromDays($termText),
            'min_amount' => $this->extractMinAmount($amountText),
            'max_amount' => $this->extractMaxAmount($amountText),
            'deposit_type' => $type,
            'capitalization' => $this->extractFeatureFlag($iconItems, 'Капитализация'),
            'online_opening' => $this->extractFeatureFlag($iconItems, 'Открытие онлайн'),
            'monthly_interest_payment' => $this->extractFeatureFlag($iconItems, 'Выплата процентов ежемесячно'),
            'partial_withdrawal' => $this->extractFeatureFlag($iconItems, 'Частичное снятие'),
            'replenishment' => $this->extractFeatureFlag($iconItems, 'Пополнение'),
            'auto_prolongation' => $this->extractFeatureFlag($iconItems, 'Пролонгация'),
            'insurance' => $this->extractFeatureFlag($iconItems, 'Участник ССВ'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseDepositDetails(string $html): array
    {
        $crawler = new Crawler($html);
        $pageText = $crawler->filter('body')->count() > 0
            ? $this->collapseWhitespace($crawler->filter('body')->text(''))
            : $this->collapseWhitespace($crawler->text(''));

        $summaryRateText = $this->extractFirstPattern($pageText, '/Ставка:\s*(.+?)(?=\s+Сумма\s+вклада:)/u');
        $summaryAmountText = $this->extractFirstPattern($pageText, '/Сумма\s+вклада:\s*(.+?)(?=\s+Срок:)/u');
        $summaryTermText = $this->extractFirstPattern($pageText, '/Срок:\s*(.+?)(?=\s+Подать\s+заявку|\s+Рассчитать\s+вклад|\s+##)/u');

        $conditionsBlock = $this->extractFirstPattern($pageText, '/Валюта:\s*(.+?)(?=\s+Рассчитать\s+вклад|\s+Подать\s+заявку|\s+Вклад\s+на\s+сайте\s+банка|\s+Описание)/u');
        $conditionsAmountText = $this->extractFirstPattern($conditionsBlock, '/Сумма:\s*(.+?)(?=\s+Ставка:)/u');
        $conditionsRateText = $this->extractFirstPattern($conditionsBlock, '/Ставка:\s*(.+?)(?=\s+Пополнение\s+счета|\s+Пополнение|\s+Частичное\s+снятие|\s+Пролонгация|\s+Капитализация)/u');
        $conditionsTermText = $this->extractFirstPattern($conditionsBlock, '/Срок\s+вклада:\s*(.+?)(?=\s+Сумма:)/u');

        $rubRateText = $this->extractRubSectionValue($conditionsRateText, ['RUB', '₽', 'руб']);
        $rubAmountText = $this->extractRubSectionValue($conditionsAmountText, ['RUB', '₽', 'руб']);

        $effectiveRateText = $rubRateText !== '' ? $rubRateText : $summaryRateText;
        $effectiveAmountText = $rubAmountText !== '' ? $rubAmountText : $summaryAmountText;
        $effectiveTermText = $conditionsTermText !== '' ? $conditionsTermText : $summaryTermText;
        $rateBounds = $this->extractRateBounds($effectiveRateText);
        $termDays = $this->extractOfferTermDays($summaryRateText !== '' ? $summaryRateText : $effectiveRateText);

        $featuresText = $conditionsBlock !== '' ? $conditionsBlock : $pageText;
        $featureItems = $this->extractDetailFeatureItems($featuresText);
        $detailType = $this->extractDepositTypeFromText($featuresText);

        return [
            'min_rate' => $rateBounds['min'],
            'max_rate' => $rateBounds['max'],
            'min_term_days' => $this->extractMinInt($effectiveTermText),
            'max_term_days' => $this->extractMaxInt($effectiveTermText),
            'term_days' => $termDays,
            'term_months' => $this->extractMaxMonthsFromDays($effectiveTermText),
            'min_amount' => $this->extractMinAmount($effectiveAmountText),
            'max_amount' => $this->extractMaxAmountIfRange($effectiveAmountText),
            'deposit_type' => $detailType,
            'capitalization' => $this->extractFeatureFlag($featureItems, 'Капитализация'),
            'online_opening' => $this->extractFeatureFlag($featureItems, 'Открытие онлайн'),
            'monthly_interest_payment' => $this->extractFeatureFlag($featureItems, 'Выплата процентов ежемесячно'),
            'partial_withdrawal' => $this->extractFeatureFlag($featureItems, 'Частичное снятие'),
            'replenishment' => $this->extractFeatureFlag($featureItems, 'Пополнение'),
            'auto_prolongation' => $this->extractFeatureFlag($featureItems, 'Пролонгация'),
            'insurance' => $this->extractFeatureFlag($featureItems, 'Участник ССВ') || $this->extractFeatureFlag($featureItems, 'Вклад застрахован'),
        ];
    }

    /**
     * @param  array<string, mixed>  $catalogDeposit
     * @param  array<string, mixed>  $detailDeposit
     * @return array<string, mixed>
     */
    private function mergeDepositWithDetails(array $catalogDeposit, array $detailDeposit): array
    {
        foreach ([
            'min_rate',
            'max_rate',
            'min_term_days',
            'max_term_days',
            'term_days',
            'term_months',
            'min_amount',
            'max_amount',
            'deposit_type',
        ] as $field) {
            if (array_key_exists($field, $detailDeposit) && $detailDeposit[$field] !== null && $detailDeposit[$field] !== '') {
                $catalogDeposit[$field] = $detailDeposit[$field];
            }
        }

        foreach ([
            'capitalization',
            'online_opening',
            'monthly_interest_payment',
            'partial_withdrawal',
            'replenishment',
            'auto_prolongation',
            'insurance',
        ] as $field) {
            if (array_key_exists($field, $detailDeposit)) {
                $catalogDeposit[$field] = (bool) $detailDeposit[$field];
            }
        }

        if (($detailDeposit['max_amount'] ?? null) === null) {
            $catalogDeposit['max_amount'] = null;
        }

        return $catalogDeposit;
    }

    /**
     * @return array<int, string>
     */
    private function extractPaginationUrls(string $html): array
    {
        $crawler = new Crawler($html);
        $urls = [];

        foreach ($crawler->filter('.pagination a[href], #product-list-pagination[data-urls]') as $node) {
            $element = new Crawler($node);

            $encodedUrls = $element->attr('data-urls');
            if (is_string($encodedUrls) && trim($encodedUrls) !== '') {
                $decoded = json_decode(base64_decode($encodedUrls, true) ?: '', true);
                if (is_array($decoded)) {
                    foreach ($decoded as $relativeUrl) {
                        if (is_string($relativeUrl) && trim($relativeUrl) !== '') {
                            $urls[] = $this->absoluteUrl($relativeUrl);
                        }
                    }
                }
            }

            $href = trim((string) $element->attr('href'));
            if ($href !== '' && $href !== 'javascript:;') {
                $urls[] = $this->absoluteUrl($href);
            }
        }

        return array_values(array_unique(array_filter($urls)));
    }

    /**
     * @return array<int, array{text: string, active: bool}>
     */
    private function extractIconItems(Crawler $item): array
    {
        $result = [];

        foreach ($item->filter('.card-full-info__list-icons-item') as $node) {
            if (! $node instanceof \DOMElement) {
                continue;
            }

            $text = $this->extractListItemLabel($node);

            if ($text === '') {
                continue;
            }

            $result[] = [
                'text' => $text,
                'active' => ! preg_match('/(?:^|\s)none(?:\s|$)/', (string) $node->getAttribute('class')),
            ];
        }

        return $result;
    }

    private function extractDepositType(Crawler $item, array $iconItems): ?string
    {
        $candidateTexts = [];

        foreach ($item->filter('.cards-list-item__list-item, .card-full-info__list-icons-item') as $node) {
            $candidateTexts[] = $this->collapseWhitespace($node->textContent ?? '');
        }

        foreach (self::DEPOSIT_TYPES as $type) {
            foreach ($candidateTexts as $text) {
                if ($text !== '' && mb_stripos($text, $type) !== false) {
                    return $type;
                }
            }
        }

        foreach ($iconItems as $iconItem) {
            foreach (self::DEPOSIT_TYPES as $type) {
                if (mb_stripos($iconItem['text'], $type) !== false) {
                    return $type;
                }
            }
        }

        return null;
    }

    private function extractDepositTypeFromText(string $text): ?string
    {
        foreach (self::DEPOSIT_TYPES as $type) {
            if ($text !== '' && mb_stripos($text, $type) !== false) {
                return $type;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array{text: string, active: bool}>  $iconItems
     */
    private function extractFeatureFlag(array $iconItems, string $label): bool
    {
        foreach ($iconItems as $item) {
            if (mb_stripos($item['text'], $label) !== false) {
                return (bool) $item['active'];
            }
        }

        return false;
    }

    /**
     * @return array<int, array{text: string, active: bool}>
     */
    private function extractDetailFeatureItems(string $text): array
    {
        $items = [];

        foreach ([
            'Капитализация процентов',
            'Открытие онлайн',
            'Выплата процентов ежемесячно',
            'Частичное снятие',
            'Пополнение счета',
            'Пополнение',
            'Пролонгация',
            'Участник ССВ',
            'Вклад застрахован',
            'Классический',
            'Накопительный счет',
            'Пенсионный',
            'Инвестиционный',
            'Для клиентов банка',
        ] as $label) {
            if ($text !== '' && mb_stripos($text, $label) !== false) {
                $items[] = [
                    'text' => $label,
                    'active' => true,
                ];
            }
        }

        return $items;
    }

    private function extractLabeledValue(Crawler $item, string $label): string
    {
        foreach ($item->filter('.card-full-info-requirements__item') as $node) {
            $row = new Crawler($node);
            $leftNode = $row->filter('.card-full-info-requirements__left');
            $rightNode = $row->filter('.card-full-info-requirements__right');
            $left = $leftNode->count() > 0 ? $this->collapseWhitespace($leftNode->text('')) : '';

            if ($left === '' || ! str_contains(mb_strtolower($left), mb_strtolower($label))) {
                continue;
            }

            $right = $rightNode->count() > 0 ? $this->collapseWhitespace($rightNode->text('')) : '';
            if ($right !== '') {
                return $right;
            }
        }

        return '';
    }

    private function extractText(Crawler $crawler, string $selector): string
    {
        $node = $crawler->filter($selector);

        return $node->count() > 0
            ? $this->collapseWhitespace($node->first()->text(''))
            : '';
    }

    private function extractAttribute(Crawler $crawler, string $selector, string $attribute): string
    {
        $node = $crawler->filter($selector);

        return $node->count() > 0
            ? trim((string) $node->first()->attr($attribute))
            : '';
    }

    private function extractSlugFromUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return '';
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        return (string) end($segments);
    }

    private function extractListItemLabel(\DOMElement $node): string
    {
        $parts = [];

        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof \DOMText) {
                $text = $this->collapseWhitespace($childNode->textContent ?? '');
                if ($text !== '') {
                    $parts[] = $text;
                }

                continue;
            }

            if ($childNode instanceof \DOMElement && $childNode->tagName === 'span') {
                $text = $this->collapseWhitespace($childNode->textContent ?? '');
                if ($text !== '') {
                    $parts[] = $text;
                }
            }
        }

        return $this->collapseWhitespace(implode(' ', $parts));
    }

    private function extractMaxDecimal(string $value): ?float
    {
        preg_match_all('/\d+(?:[.,]\d+)?/u', str_replace(' ', '', $value), $matches);
        $numbers = array_map(
            static fn (string $number): float => (float) str_replace(',', '.', $number),
            $matches[0] ?? []
        );

        return $numbers !== [] ? max($numbers) : null;
    }

    /**
     * @return array{min: float|null, max: float|null}
     */
    private function extractRateBounds(string $value): array
    {
        $numbers = $this->extractNumbers($value);

        if ($numbers === []) {
            return ['min' => null, 'max' => null];
        }

        if (count($numbers) === 1) {
            return ['min' => (float) $numbers[0], 'max' => null];
        }

        return ['min' => (float) min($numbers), 'max' => (float) max($numbers)];
    }

    private function extractMinDecimal(string $value): ?float
    {
        preg_match_all('/\d+(?:[.,]\d+)?/u', str_replace(' ', '', $value), $matches);
        $numbers = array_map(
            static fn (string $number): float => (float) str_replace(',', '.', $number),
            $matches[0] ?? []
        );

        return $numbers !== [] ? min($numbers) : null;
    }

    private function extractMinAmount(string $value): ?float
    {
        $numbers = $this->extractNumbers($value);

        return $numbers !== [] ? min($numbers) : null;
    }

    private function extractMaxAmount(string $value): ?float
    {
        $numbers = $this->extractNumbers($value);

        return $numbers !== [] ? max($numbers) : null;
    }

    private function extractMaxAmountIfRange(string $value): ?float
    {
        $numbers = $this->extractNumbers($value);

        if (count($numbers) < 2) {
            return null;
        }

        return max($numbers);
    }

    private function extractOfferTermDays(string $value): ?int
    {
        if ($value === '') {
            return null;
        }

        if (preg_match('/на\s+(\d+)\s+дн(?:ей|я|ь)?/ui', $value, $matches)) {
            return isset($matches[1]) ? (int) $matches[1] : null;
        }

        return null;
    }

    private function extractMinInt(string $value): ?int
    {
        $numbers = $this->extractNumbers($value);

        return $numbers !== [] ? (int) min($numbers) : null;
    }

    private function extractMaxInt(string $value): ?int
    {
        $numbers = $this->extractNumbers($value);

        return $numbers !== [] ? (int) max($numbers) : null;
    }

    private function extractMaxMonthsFromDays(string $value): ?int
    {
        $numbers = $this->extractNumbers($value);
        if ($numbers === []) {
            return null;
        }

        $maxDays = (float) max($numbers);

        return (int) ceil($maxDays / 30);
    }

    private function extractMinMonthsFromDays(string $value): ?int
    {
        $numbers = $this->extractNumbers($value);
        if ($numbers === []) {
            return null;
        }

        $minDays = (float) min($numbers);

        return (int) ceil($minDays / 30);
    }

    /**
     * @return array<int, float>
     */
    private function extractNumbers(string $value): array
    {
        preg_match_all('/\d+(?:[.,]\d+)?/u', str_replace(' ', '', $value), $matches);

        return array_map(
            static fn (string $number): float => (float) str_replace(',', '.', $number),
            $matches[0] ?? []
        );
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

    private function collapseWhitespace(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function extractFirstPattern(string $text, string $pattern): string
    {
        if ($text === '') {
            return '';
        }

        if (! preg_match($pattern, $text, $matches)) {
            return '';
        }

        return $this->collapseWhitespace((string) ($matches[1] ?? ''));
    }

    /**
     * @param  array<int, string>  $currencyMarkers
     */
    private function extractRubSectionValue(string $text, array $currencyMarkers): string
    {
        if ($text === '') {
            return '';
        }

        foreach ($currencyMarkers as $marker) {
            if (preg_match('/(.{0,80}?\d[\d\s.,%-]*?)\s*(?:' . preg_quote($marker, '/') . ')/ui', $text, $matches)) {
                return $this->collapseWhitespace((string) ($matches[1] ?? ''));
            }
        }

        if (preg_match('/([\d\s.,%-]+)\s*в\s+RUB/ui', $text, $matches)) {
            return $this->collapseWhitespace((string) ($matches[1] ?? ''));
        }

        return '';
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
