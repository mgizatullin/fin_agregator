<?php

namespace App\Services\Parsers\Brobank;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class BrobankCreditCardParser
{
    private const CATALOG_URL = 'https://brobank.ru/kreditnye-karty/';

    private const REQUEST_TIMEOUT = 20;

    private const CONNECT_TIMEOUT = 10;

    private const MAX_RETRIES = 3;

    private const REQUEST_HEADERS = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8',
        'Referer: https://brobank.ru/',
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
        $this->log('Начало парсинга кредитных карт Brobank');

        $providedHtml = is_string($html) ? trim($html) : '';
        if ($providedHtml !== '') {
            $this->log('Используется вручную вставленный HTML каталога');
            $catalogHtml = $providedHtml;
        } else {
            $catalogHtml = $this->fetchHtml(self::CATALOG_URL);
        }

        if (! is_string($catalogHtml) || trim($catalogHtml) === '') {
            $this->log('Каталог кредитных карт Brobank не загружен');

            return [];
        }

        $cards = $this->parseCatalogHtml($catalogHtml, $limit);
        $total = count($cards);

        $this->log('Собрано карт всего: '.$total);

        foreach ($cards as $index => $card) {
            $position = $index + 1;
            $this->log("[{$position}/{$total}] {$card['bank']} — {$card['name']}");
            $this->reportProgress($position, $total);
        }

        $this->log('Парсинг завершён');

        return $cards;
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

            $this->log('URL: '.$url);
            $this->log('HTTP статус: '.$httpStatus);
            $this->log('curl_errno: '.$errno);
            $this->log('curl_error: '.$error);
            $this->log('Размер HTML: '.$size);

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
    private function parseCatalogHtml(string $html, ?int $limit = null): array
    {
        $crawler = new Crawler($html);
        $cards = [];
        $seen = [];

        foreach ($crawler->filter('.horizontal-card.more-info.new-layout') as $node) {
            $item = new Crawler($node);
            $parsed = $this->parseBrobankCardNode($item);

            if ($parsed === null) {
                continue;
            }

            $key = mb_strtolower(($parsed['bank'] ?? '').'|'.($parsed['name'] ?? '').'|'.($parsed['slug'] ?? ''));
            if (isset($seen[$key])) {
                continue;
            }

            $cards[] = $parsed;
            $seen[$key] = true;

            if ($limit !== null && $limit > 0 && count($cards) >= $limit) {
                return $cards;
            }
        }

        if ($cards !== []) {
            return $cards;
        }

        $candidateSelectors = [
            'article',
            '.offer-card',
            '.product-card',
            '.catalog-item',
            '.bank-item',
            '.comparison-table__item',
            '.post-card',
            'section article',
        ];

        $nodes = [];

        foreach ($candidateSelectors as $selector) {
            foreach ($crawler->filter($selector) as $node) {
                $text = $this->collapseWhitespace((string) $node->textContent);
                if (
                    $text !== ''
                    && str_contains($text, 'Оформить карту')
                    && str_contains($text, 'Кредитный лимит')
                ) {
                    $hash = spl_object_hash($node);
                    $nodes[$hash] = $node;
                }
            }
        }

        if ($nodes === []) {
            $this->log('Подходящие карточки по CSS-селекторам не найдены, используется текстовый разбор');

            return $this->parseByTextFallback($html, $limit);
        }

        foreach (array_values($nodes) as $node) {
            $item = new Crawler($node);
            $parsed = $this->parseCardNode($item);

            if ($parsed === null) {
                continue;
            }

            $key = mb_strtolower(($parsed['bank'] ?? '').'|'.($parsed['name'] ?? '').'|'.($parsed['slug'] ?? ''));
            if (isset($seen[$key])) {
                continue;
            }

            $cards[] = $parsed;
            $seen[$key] = true;

            if ($limit !== null && $limit > 0 && count($cards) >= $limit) {
                break;
            }
        }

        return $cards;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseBrobankCardNode(Crawler $item): ?array
    {
        $headTitle = $item->filter('.products__item-col.head-title')->count() > 0
            ? $item->filter('.products__item-col.head-title')->first()
            : $item;

        $bank = '';
        if ($headTitle->filter('span.process__data')->count() > 0) {
            $bank = $this->collapseWhitespace($headTitle->filter('span.process__data')->first()->text(''));
        }

        if ($bank === '' && $headTitle->filter('a')->count() > 0) {
            foreach ($headTitle->filter('a') as $linkNode) {
                $link = new Crawler($linkNode);
                $linkText = $this->collapseWhitespace($link->text(''));
                $linkClass = (string) $link->attr('class');

                if ($linkText === '' || str_contains($linkClass, 'more-link')) {
                    continue;
                }

                $bank = $linkText;
                break;
            }
        }

        $name = $headTitle->filter('a.more-link')->count() > 0
            ? $this->collapseWhitespace($headTitle->filter('a.more-link')->first()->text(''))
            : '';

        $detailUrl = $headTitle->filter('a.more-link')->count() > 0
            ? $this->normalizeUrl((string) $headTitle->filter('a.more-link')->first()->attr('href'))
            : null;
        $slug = null;

        if (is_string($detailUrl)) {
            $path = parse_url($detailUrl, PHP_URL_PATH);
            if (is_string($path)) {
                $slug = trim((string) basename(rtrim($path, '/')));
            }
        }

        if ($bank === '' && $name === '') {
            return null;
        }

        $title = trim($bank.' '.$name);
        $summary = $this->extractSummaryTable($item);
        $accordion = $this->extractAccordionData($item);
        $tags = $item->filter('.products__item-categories-item')->each(
            fn (Crawler $node): string => $this->collapseWhitespace($node->text(''))
        );
        $tags = array_values(array_filter(array_unique($tags)));

        $image = null;
        if ($item->filter('.horizontal-card__img img')->count() > 0) {
            $imgNode = $item->filter('.horizontal-card__img img')->first();
            $image = $this->normalizeUrl((string) ($imgNode->attr('data-src') ?: $imgNode->attr('src') ?: ''));
        }

        $encodedApply = null;
        if ($item->filter('.horizontal-card__img .process__data')->count() > 0) {
            $encodedApply = (string) $item->filter('.horizontal-card__img .process__data')->first()->attr('data-pdl');
        }

        $applyUrl = $this->decodeTrackingUrl($encodedApply);
        $rating = null;
        if ($item->filter('.info-rating')->count() > 0) {
            $ratingText = $this->collapseWhitespace($item->filter('.info-rating')->first()->text(''));
            $rating = is_numeric(str_replace(',', '.', $ratingText)) ? (float) str_replace(',', '.', $ratingText) : null;
        }

        $license = null;
        if ($item->filter('.products__license')->count() > 0) {
            $license = $this->collapseWhitespace($item->filter('.products__license')->first()->text(''));
        }

        return [
            'source' => 'brobank',
            'catalog_url' => self::CATALOG_URL,
            'detail_url' => $detailUrl,
            'slug' => $slug !== '' ? $slug : null,
            'bank' => $bank,
            'name' => $name,
            'title' => $title,
            'credit_limit' => $this->extractMoneyValue($summary['Кредитный лимит'] ?? null),
            'credit_limit_text' => $summary['Кредитный лимит'] ?? null,
            'psk_text' => $summary['ПСК'] ?? null,
            'grace_period_text' => $summary['Без процентов'] ?? null,
            'annual_fee_text' => $summary['Стоимость'] ?? null,
            'cashback_text' => $summary['Кешбэк'] ?? null,
            'decision_text' => $summary['Решение'] ?? null,
            'tags' => $tags,
            'accordion' => $accordion,
            'image' => $image,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseCardNode(Crawler $item): ?array
    {
        $text = $this->collapseWhitespace($item->text(''));
        if ($text === '' || ! str_contains($text, 'Оформить карту')) {
            return null;
        }

        $titleLink = $this->findTitleLink($item);
        $title = $titleLink['title'];
        $slug = $titleLink['slug'];
        $detailUrl = $titleLink['url'];

        [$bank, $name] = $this->splitBankAndName($title);

        if ($title === '' || $detailUrl === null || $bank === '' || $name === '') {
            return null;
        }

        $actions = $this->extractActionLinks($item);

        if ($actions['apply_url'] === null && $actions['more_url'] === null) {
            return null;
        }

        $summary = $this->extractSummaryValues($text);
        $tags = $this->extractTags($text);
        $conditions = $this->extractConditionPairs($text);
        $images = $this->extractImages($item);
        $rating = $this->extractRating($text);

        return [
            'source' => 'brobank',
            'catalog_url' => self::CATALOG_URL,
            'detail_url' => $detailUrl,
            'apply_url' => $actions['apply_url'],
            'more_url' => $actions['more_url'],
            'bank' => $bank,
            'name' => $name,
            'title' => $title,
            'slug' => $slug ?: Str::slug($name ?: $title ?: 'card'),
            'rating' => $rating,
            'credit_limit' => $this->extractMoneyValue($summary['credit_limit'] ?? null),
            'psk_text' => $summary['psk'] ?? null,
            'grace_period_text' => $summary['grace_period'] ?? null,
            'annual_fee_text' => $summary['annual_fee'] ?? null,
            'cashback_text' => $summary['cashback'] ?? null,
            'decision_text' => $summary['decision'] ?? null,
            'tags' => $tags,
            'conditions' => $conditions,
            'payment_system' => $conditions['Платежная система'] ?? null,
            'card_type' => $conditions['Тип карты'] ?? null,
            'max_limit_text' => $conditions['Максимальный лимит'] ?? null,
            'min_limit_text' => $conditions['Минимальный лимит'] ?? null,
            'service_cost_text' => $conditions['Стоимость обслуживания'] ?? null,
            'grace_period_conditions_text' => $conditions['Льготный период'] ?? null,
            'min_payment_text' => $conditions['Минимальный платеж'] ?? null,
            'review_time_text' => $conditions['Время рассмотрения'] ?? null,
            'delivery_text' => $conditions['Доставка карты'] ?? null,
            'delivery_time_text' => $conditions['Срок доставки'] ?? null,
            'income_proof_text' => $conditions['Подтверждение дохода'] ?? null,
            'age_text' => $conditions['Возраст'] ?? null,
            'registration_text' => $conditions['Прописка в регионе банка'] ?? null,
            'contactless_payment_text' => $conditions['Бесконтактная оплата'] ?? null,
            'sms_info_text' => $conditions['Смс-информирование'] ?? null,
            'reissue_fee_text' => $conditions['Комиссия за перевыпуск'] ?? null,
            'interest_text' => $conditions['Диапазон ПСК'] ?? ($conditions['Проценты'] ?? null),
            'cash_withdrawal_fee_text' => $conditions['Комиссия за снятие наличных'] ?? null,
            'penalty_text' => $conditions['Размер неустойки в случае неуплаты мин. платежа'] ?? null,
            'cashback_details_text' => $conditions['Кэшбэк'] ?? null,
            'image' => $images['main'],
            'bank_logo' => $images['logo'],
            'raw_text' => $text,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function extractSummaryTable(Crawler $item): array
    {
        $result = [];

        foreach ($item->filter('.horizontal-card__table tr') as $trNode) {
            $tr = new Crawler($trNode);
            if ($tr->filter('td')->count() < 2) {
                continue;
            }

            $label = $this->collapseWhitespace($tr->filter('td')->eq(0)->text(''));
            $value = $this->collapseWhitespace($tr->filter('td')->eq(1)->text(''));

            if ($label !== '' && $value !== '') {
                $result[$label] = $value;
            }
        }

        return $result;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function extractAccordionData(Crawler $item): array
    {
        $result = [];

        foreach ($item->filter('.products-accordion__item') as $accordionNode) {
            $accordion = new Crawler($accordionNode);
            $sectionTitle = '';

            if ($accordion->filter('.products-accordion__title')->count() > 0) {
                $sectionTitle = $this->collapseWhitespace($accordion->filter('.products-accordion__title')->first()->text(''));
            }

            if ($sectionTitle === '') {
                continue;
            }

            $rows = [];
            foreach ($accordion->filter('.products__item-content__item-row') as $rowNode) {
                $row = new Crawler($rowNode);
                $label = $row->filter('.products__item-content__item-title')->count() > 0
                    ? $this->collapseWhitespace($row->filter('.products__item-content__item-title')->first()->text(''))
                    : '';
                $value = $row->filter('.products__item-content__item-p')->count() > 0
                    ? $this->collapseWhitespace($row->filter('.products__item-content__item-p')->first()->text(''))
                    : '';

                if ($label !== '' && $value !== '') {
                    $rows[$label] = $value;
                }
            }

            $result[$sectionTitle] = $rows;
        }

        return $result;
    }

    private function decodeTrackingUrl(?string $encoded): ?string
    {
        if (! is_string($encoded) || trim($encoded) === '') {
            return null;
        }

        $decoded = base64_decode($encoded, true);
        if (! is_string($decoded) || trim($decoded) === '') {
            return null;
        }

        return $this->normalizeUrl($decoded);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseByTextFallback(string $html, ?int $limit = null): array
    {
        $text = html_entity_decode(strip_tags($html));
        $text = preg_replace('/\R+/u', "\n", $text) ?? $text;
        $text = preg_replace("/\n{2,}/u", "\n\n", $text) ?? $text;
        $chunks = preg_split('/\n(?=.+\n(?:\d+[.,]\d+|\d+[.,]\d)\n(?:Лицензия ЦБ РФ: )?)/u', $text) ?: [];
        $result = [];
        $seen = [];

        foreach ($chunks as $chunk) {
            $chunk = trim($chunk);
            if (! str_contains($chunk, 'Оформить карту') || ! str_contains($chunk, 'Кредитный лимит')) {
                continue;
            }

            $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $chunk) ?: [])));
            if (count($lines) < 5) {
                continue;
            }

            $title = $lines[0] ?? '';
            [$bank, $name] = $this->splitBankAndName($title);
            if ($bank === '' && $name === '') {
                continue;
            }

            $summaryText = implode(' ', array_slice($lines, 0, 12));
            $conditions = $this->extractConditionPairs($chunk);

            $parsed = [
                'source' => 'brobank',
                'catalog_url' => self::CATALOG_URL,
                'detail_url' => null,
                'apply_url' => null,
                'more_url' => null,
                'bank' => $bank,
                'name' => $name,
                'title' => $title,
                'slug' => Str::slug($name ?: $title ?: 'card'),
                'rating' => $this->extractRating($chunk),
                'credit_limit' => $this->extractMoneyValue($this->extractBetween($summaryText, 'Кредитный лимит', 'ПСК')),
                'psk_text' => $this->extractBetween($summaryText, 'ПСК', 'Без процентов'),
                'grace_period_text' => $this->extractBetween($summaryText, 'Без процентов', 'Стоимость'),
                'annual_fee_text' => $this->extractBetween($summaryText, 'Стоимость', 'Кэшбек'),
                'cashback_text' => $this->extractBetween($summaryText, 'Кэшбек', 'Решение'),
                'decision_text' => $this->extractAfter($summaryText, 'Решение'),
                'tags' => [],
                'conditions' => $conditions,
                'image' => null,
                'bank_logo' => null,
                'raw_text' => $this->collapseWhitespace($chunk),
            ];

            $key = mb_strtolower(($parsed['bank'] ?? '').'|'.($parsed['name'] ?? '').'|'.($parsed['slug'] ?? ''));
            if (isset($seen[$key])) {
                continue;
            }

            $result[] = $parsed;
            $seen[$key] = true;

            if ($limit !== null && $limit > 0 && count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }

    /**
     * @return array{title:string,slug:?string,url:?string}
     */
    private function findTitleLink(Crawler $item): array
    {
        $links = [];

        foreach ($item->filter('a') as $linkNode) {
            $link = new Crawler($linkNode);
            $text = $this->collapseWhitespace($link->text(''));
            $href = trim((string) $link->attr('href'));

            if ($text === '' || $href === '' || str_contains($text, 'Оформить карту') || str_contains($text, 'Подробнее') || str_contains($text, 'Сравнить')) {
                continue;
            }

            $links[] = [
                'text' => $text,
                'href' => $href,
            ];
        }

        usort($links, fn (array $a, array $b): int => mb_strlen($b['text']) <=> mb_strlen($a['text']));
        $best = $links[0] ?? ['text' => '', 'href' => ''];

        $url = $best['href'] !== '' ? $this->normalizeUrl($best['href']) : null;
        $slug = null;

        if ($url !== null) {
            $path = parse_url($url, PHP_URL_PATH);
            if (is_string($path)) {
                $slug = trim((string) basename(rtrim($path, '/')));
            }
        }

        return [
            'title' => $best['text'],
            'slug' => $slug !== '' ? $slug : null,
            'url' => $url,
        ];
    }

    /**
     * @return array{apply_url:?string,more_url:?string}
     */
    private function extractActionLinks(Crawler $item): array
    {
        $applyUrl = null;
        $moreUrl = null;

        foreach ($item->filter('a') as $linkNode) {
            $link = new Crawler($linkNode);
            $text = $this->collapseWhitespace($link->text(''));
            $href = trim((string) $link->attr('href'));

            if ($href === '') {
                continue;
            }

            if ($applyUrl === null && str_contains($text, 'Оформить карту')) {
                $applyUrl = $this->normalizeUrl($href);
            }

            if ($moreUrl === null && str_contains($text, 'Подробнее')) {
                $moreUrl = $this->normalizeUrl($href);
            }
        }

        return [
            'apply_url' => $applyUrl,
            'more_url' => $moreUrl,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function extractSummaryValues(string $text): array
    {
        return [
            'credit_limit' => $this->extractBetween($text, 'Кредитный лимит', 'ПСК'),
            'psk' => $this->extractBetween($text, 'ПСК', 'Без процентов'),
            'grace_period' => $this->extractBetween($text, 'Без процентов', 'Стоимость'),
            'annual_fee' => $this->extractBetween($text, 'Стоимость', 'Кэшбек'),
            'cashback' => $this->extractBetween($text, 'Кэшбек', 'Решение'),
            'decision' => $this->extractBetween($text, 'Решение', 'Оформить карту'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function extractTags(string $text): array
    {
        $start = mb_strpos($text, 'Решение');
        $end = mb_strpos($text, 'Оформить карту');
        if ($start === false || $end === false || $end <= $start) {
            return [];
        }

        $segment = trim(mb_substr($text, $start, $end - $start));
        $segment = preg_replace('/^Решение\s+[^\p{L}\p{N}]*/u', '', $segment) ?? $segment;
        $segment = preg_replace('/^\S+\s*/u', '', $segment) ?? $segment;

        $parts = preg_split('/\s{2,}| (?=[А-ЯA-ZЁ][а-яa-zё])/u', $segment, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tags = [];

        foreach ($parts as $part) {
            $value = $this->collapseWhitespace($part);
            if ($value !== '' && ! preg_match('/^\d/u', $value)) {
                $tags[] = $value;
            }
        }

        return array_values(array_unique($tags));
    }

    /**
     * @return array<string, string>
     */
    private function extractConditionPairs(string $text): array
    {
        $knownLabels = [
            'Платежная система',
            'Тип карты',
            'Срок действия',
            'Максимальный лимит',
            'Минимальный лимит',
            'Стоимость обслуживания',
            'Льготный период',
            'Минимальный платеж',
            'Время рассмотрения',
            'Доставка карты',
            'Срок доставки',
            'Подтверждение дохода',
            'Возраст',
            'Прописка в регионе банка',
            'Бесконтактная оплата',
            'Смс-информирование',
            'Комиссия за перевыпуск',
            'Проценты',
            'Диапазон ПСК',
            'Комиссия за снятие наличных',
            'Размер неустойки в случае неуплаты мин. платежа',
            'Кэшбэк',
        ];

        $pairs = [];
        foreach ($knownLabels as $label) {
            $value = $this->extractConditionValue($text, $label, $knownLabels);
            if ($value !== '') {
                $pairs[$label] = $value;
            }
        }

        return $pairs;
    }

    private function extractConditionValue(string $text, string $label, array $knownLabels): string
    {
        $pos = mb_strpos($text, $label);
        if ($pos === false) {
            return '';
        }

        $start = $pos + mb_strlen($label);
        $end = mb_strlen($text);

        foreach ($knownLabels as $otherLabel) {
            if ($otherLabel === $label) {
                continue;
            }

            $otherPos = mb_strpos($text, $otherLabel, $start);
            if ($otherPos !== false && $otherPos < $end) {
                $end = $otherPos;
            }
        }

        $value = trim(mb_substr($text, $start, $end - $start));

        if ($label === 'Кэшбэк') {
            $value = preg_replace('/^Кэшбэк\s*/u', '', $value) ?? $value;
        }

        return $this->collapseWhitespace($value);
    }

    /**
     * @return array{main:?string,logo:?string}
     */
    private function extractImages(Crawler $item): array
    {
        $images = [];

        foreach ($item->filter('img') as $imgNode) {
            $img = new Crawler($imgNode);
            $src = trim((string) ($img->attr('src') ?: $img->attr('data-src') ?: ''));
            $alt = $this->collapseWhitespace((string) $img->attr('alt'));

            if ($src === '') {
                continue;
            }

            $images[] = [
                'src' => $this->normalizeUrl($src),
                'alt' => $alt,
            ];
        }

        return [
            'main' => $images[0]['src'] ?? null,
            'logo' => $images[1]['src'] ?? ($images[0]['src'] ?? null),
        ];
    }

    private function extractRating(string $text): ?float
    {
        if (preg_match('/\b(\d+[.,]\d)\b/u', $text, $matches) === 1) {
            return (float) str_replace(',', '.', $matches[1]);
        }

        return null;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitBankAndName(string $title): array
    {
        $title = $this->collapseWhitespace($title);
        if ($title === '') {
            return ['', ''];
        }

        if (preg_match('/^(.*?)\s+Кредитная карта\s+(.+)$/u', $title, $matches) === 1) {
            return [trim($matches[1]), trim($matches[2])];
        }

        if (preg_match('/^(.*?)\s+Виртуальная\s+(.+)$/u', $title, $matches) === 1) {
            return [trim($matches[1]), trim($matches[2])];
        }

        $parts = preg_split('/\s+—\s+|\s+-\s+/u', $title, 2) ?: [];
        if (count($parts) === 2) {
            return [trim($parts[0]), trim($parts[1])];
        }

        return ['', trim($title)];
    }

    private function extractMoneyValue(?string $value): ?float
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        if (preg_match('/([\d\s]+)\s*(?:₽|руб)/u', $value, $matches) === 1) {
            return (float) str_replace([' ', "\xc2\xa0"], '', $matches[1]);
        }

        return null;
    }

    private function extractBetween(string $text, string $startLabel, string $endLabel): string
    {
        $start = mb_strpos($text, $startLabel);
        if ($start === false) {
            return '';
        }

        $start += mb_strlen($startLabel);
        $end = mb_strpos($text, $endLabel, $start);
        if ($end === false) {
            return '';
        }

        return $this->collapseWhitespace(mb_substr($text, $start, $end - $start));
    }

    private function extractAfter(string $text, string $label): string
    {
        $pos = mb_strpos($text, $label);
        if ($pos === false) {
            return '';
        }

        return $this->collapseWhitespace(mb_substr($text, $pos + mb_strlen($label)));
    }

    private function normalizeUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        if (str_starts_with($url, '/')) {
            return 'https://brobank.ru'.$url;
        }

        return $url;
    }

    private function collapseWhitespace(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode($value)));
    }

    private function log(string $message): void
    {
        $this->log[] = $message;

        if (is_callable($this->logCallback)) {
            ($this->logCallback)($message);
        }
    }

    private function reportProgress(int $current, int $total): void
    {
        $percent = $total > 0 ? (int) round(($current / $total) * 100) : 0;

        if (is_callable($this->progressCallback)) {
            ($this->progressCallback)($percent);
        }
    }
}
