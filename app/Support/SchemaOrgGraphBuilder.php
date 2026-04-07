<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Bank;
use App\Models\BankCategory;
use App\Models\Card;
use App\Models\CardCategory;
use App\Models\Category as BlogCategory;
use App\Models\Credit;
use App\Models\CreditCategory;
use App\Models\Deposit;
use App\Models\DepositCategory;
use App\Models\Loan;
use App\Models\LoanCategory;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

final class SchemaOrgGraphBuilder
{
    /**
     * @param  array<string, mixed>  $v  View data (e.g. get_defined_vars() from schema partial).
     */
    public static function toJson(array $v): string
    {
        $graph = self::buildGraph($v);

        return json_encode(
            [
                '@context' => 'https://schema.org',
                '@graph' => array_values($graph),
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }

    /**
     * @param  array<string, mixed>  $v
     * @return array<int, array<string, mixed>>
     */
    private static function buildGraph(array $v): array
    {
        $siteUrl = url('/');
        $pageUrl = self::normalizePageUrl((string) ($v['canonical_url'] ?? $v['canonicalUrl'] ?? ''), url()->current());
        $siteName = config('app.name');
        $siteSettings = $v['siteSettings'] ?? null;
        $orgName = (string) ($siteSettings?->display_name ?? $siteName);
        $logoUrl = ! empty($siteSettings?->logo)
            ? (str_starts_with((string) $siteSettings->logo, 'http') ? $siteSettings->logo : asset('storage/'.ltrim((string) $siteSettings->logo, '/')))
            : asset('assets/images/logo/favicon.svg');
        $section = $v['section'] ?? null;
        $pageTitle = trim((string) ($v['seo_title'] ?? $v['title'] ?? ($section?->title ?? $siteName)));
        $pageDescription = trim((string) ($v['seo_description'] ?? ($section?->subtitle ?? '')));
        $pageImage = $v['metaOgImage'] ?? $v['og_image'] ?? null;

        $graph = [];

        $graph[] = [
            '@type' => 'Organization',
            '@id' => $siteUrl.'/#organization',
            'name' => $orgName,
            'url' => $siteUrl,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $logoUrl,
            ],
        ];

        $graph[] = [
            '@type' => 'WebSite',
            '@id' => $siteUrl.'/#website',
            'url' => $siteUrl,
            'name' => $siteName,
            'publisher' => ['@id' => $siteUrl.'/#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('/search').'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];

        $webPage = [
            '@type' => 'WebPage',
            '@id' => $pageUrl.'/#webpage',
            'url' => $pageUrl,
            'name' => $pageTitle,
            'inLanguage' => 'ru-RU',
            'isPartOf' => ['@id' => $siteUrl.'/#website'],
            'about' => ['@id' => $siteUrl.'/#organization'],
        ];
        if ($pageDescription !== '') {
            $webPage['description'] = $pageDescription;
        }
        if (is_string($pageImage) && $pageImage !== '') {
            $webPage['primaryImageOfPage'] = [
                '@type' => 'ImageObject',
                'url' => $pageImage,
            ];
        }
        $graph[] = $webPage;

        self::appendCategoryFinancialProductIfNeeded($graph, $v, $pageUrl);

        $breadcrumbsSource = $v['breadcrumbs'] ?? [];
        if (! is_array($breadcrumbsSource) || count($breadcrumbsSource) === 0) {
            $breadcrumbsSource = [['url' => url('/'), 'label' => 'Главная']];
            $segments = request()->segments();
            $current = '';
            foreach ($segments as $segment) {
                $current .= '/'.$segment;
                $breadcrumbsSource[] = [
                    'url' => url($current),
                    'label' => mb_convert_case(str_replace('-', ' ', (string) $segment), MB_CASE_TITLE, 'UTF-8'),
                ];
            }
        }

        $breadcrumbItems = [];
        $position = 1;
        foreach ($breadcrumbsSource as $item) {
            $label = trim((string) ($item['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $entry = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $label,
            ];
            if (! empty($item['url'])) {
                $entry['item'] = (string) $item['url'];
            }
            $breadcrumbItems[] = $entry;
        }
        if (count($breadcrumbItems) > 1) {
            $graph[] = [
                '@type' => 'BreadcrumbList',
                '@id' => $pageUrl.'/#breadcrumb',
                'itemListElement' => $breadcrumbItems,
            ];
        }

        $article = $v['article'] ?? null;
        if ($article && ! empty($article->title)) {
            $articleImage = null;
            if (! empty($article->image)) {
                $articleImage = str_starts_with((string) $article->image, 'http')
                    ? (string) $article->image
                    : asset('storage/'.ltrim((string) $article->image, '/'));
            }

            $articleNode = [
                '@type' => 'BlogPosting',
                '@id' => $pageUrl.'/#article',
                'mainEntityOfPage' => ['@id' => $pageUrl.'/#webpage'],
                'headline' => (string) $article->title,
                'author' => [
                    '@type' => 'Person',
                    'name' => (string) ($article->specialist->name ?? $article->author ?? $orgName),
                ],
                'publisher' => ['@id' => $siteUrl.'/#organization'],
                'datePublished' => optional($article->published_at ?? $article->created_at)->toIso8601String(),
                'dateModified' => optional($article->updated_at ?? $article->created_at)->toIso8601String(),
            ];
            if (! empty($article->excerpt)) {
                $articleNode['description'] = (string) $article->excerpt;
            } elseif ($pageDescription !== '') {
                $articleNode['description'] = $pageDescription;
            }
            if ($articleImage) {
                $articleNode['image'] = [$articleImage];
            } elseif (is_string($pageImage) && $pageImage !== '') {
                $articleNode['image'] = [$pageImage];
            }
            $graph[] = array_filter($articleNode, fn ($val) => ! is_null($val) && $val !== '');
        }

        $productEntity = $v['credit'] ?? $v['deposit'] ?? $v['card'] ?? $v['loan'] ?? $v['bank'] ?? $v['service'] ?? null;
        if ($productEntity && ! empty($productEntity->name)) {
            self::appendFinancialProductEntity($graph, $v, $pageUrl, $pageDescription, $pageImage, $productEntity, $orgName);
        }

        $faqItems = $v['faqItems'] ?? $v['faqs'] ?? null;
        if (is_array($faqItems) && count($faqItems) > 0) {
            $faqEntities = [];
            foreach ($faqItems as $faq) {
                $q = trim((string) ($faq['question'] ?? $faq['q'] ?? ''));
                $a = trim((string) ($faq['answer'] ?? $faq['a'] ?? ''));
                if ($q === '' || $a === '') {
                    continue;
                }
                $faqEntities[] = [
                    '@type' => 'Question',
                    'name' => $q,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => strip_tags($a),
                    ],
                ];
            }
            if (count($faqEntities) > 0) {
                $graph[] = [
                    '@type' => 'FAQPage',
                    '@id' => $pageUrl.'/#faq',
                    'mainEntity' => $faqEntities,
                ];
            }
        }

        $qaItems = $v['qaItems'] ?? null;
        if (is_array($qaItems) && count($qaItems) > 0) {
            foreach ($qaItems as $idx => $qa) {
                $q = trim((string) ($qa['question'] ?? ''));
                $a = trim((string) ($qa['acceptedAnswer'] ?? $qa['answer'] ?? ''));
                if ($q === '' || $a === '') {
                    continue;
                }
                $graph[] = [
                    '@type' => 'QAPage',
                    '@id' => $pageUrl.'/#qa-'.($idx + 1),
                    'mainEntity' => [
                        '@type' => 'Question',
                        'name' => $q,
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => strip_tags($a),
                        ],
                    ],
                ];
            }
        }

        return $graph;
    }

    private static function normalizePageUrl(string $canonical, string $fallback): string
    {
        $u = $canonical !== '' ? $canonical : $fallback;
        $path = (string) (parse_url($u, PHP_URL_PATH) ?? '');
        if ($path !== '' && $path !== '/' && ! str_ends_with($u, '/')) {
            return $u.'/';
        }

        return $u;
    }

    /**
     * @param  array<int, array<string, mixed>>  $graph
     * @param  array<string, mixed>  $v
     */
    private static function appendCategoryFinancialProductIfNeeded(array &$graph, array $v, string $pageUrl): void
    {
        $productEntity = $v['credit'] ?? $v['deposit'] ?? $v['card'] ?? $v['loan'] ?? $v['bank'] ?? $v['service'] ?? null;
        if ($productEntity !== null) {
            return;
        }

        $category = $v['category'] ?? null;
        if (! is_object($category)) {
            return;
        }

        if ($category instanceof BlogCategory) {
            return;
        }

        $items = $v['items'] ?? null;

        if ($category instanceof CreditCategory) {
            $node = self::buildCategoryFinancialProductCredit($pageUrl, $category, $items);
            if ($node !== null) {
                $graph[] = $node;
            }

            return;
        }

        if ($category instanceof DepositCategory) {
            $node = self::buildCategoryFinancialProductDeposit($pageUrl, $category, $items);
            if ($node !== null) {
                $graph[] = $node;
            }

            return;
        }

        if ($category instanceof CardCategory) {
            $node = self::buildCategoryFinancialProductCard($pageUrl, $category, $items);
            if ($node !== null) {
                $graph[] = $node;
            }

            return;
        }

        if ($category instanceof LoanCategory) {
            $node = self::buildCategoryFinancialProductLoan($pageUrl, $category, $items);
            if ($node !== null) {
                $graph[] = $node;
            }

            return;
        }

        if ($category instanceof BankCategory) {
            $node = self::buildCategoryFinancialProductBankCategory($pageUrl, $category, $items);
            if ($node !== null) {
                $graph[] = $node;
            }
        }
    }

    private static function collectPaginatorItems(mixed $items): Collection
    {
        if ($items instanceof Paginator) {
            return $items->getCollection();
        }
        if ($items instanceof Collection) {
            return $items;
        }
        if (is_array($items)) {
            return collect($items);
        }

        return collect();
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function buildCategoryFinancialProductCredit(string $pageUrl, CreditCategory $category, mixed $items): ?array
    {
        $collection = self::collectPaginatorItems($items)->filter(fn ($m) => $m instanceof Credit);
        $node = [
            '@type' => 'FinancialProduct',
            '@id' => $pageUrl.'/#category-financial-product',
            'name' => (string) $category->title,
            'url' => $pageUrl,
        ];
        $desc = trim(strip_tags((string) ($category->subtitle ?? $category->description ?? '')));
        if ($desc !== '') {
            $node['description'] = $desc;
        }

        if ($collection->isEmpty()) {
            return $node;
        }

        /** @var Collection<int, Credit> $collection */
        $rates = $collection->map(fn (Credit $c) => $c->rate)->filter(fn ($r) => $r !== null && (float) $r > 0);
        if ($rates->isNotEmpty()) {
            $node['interestRate'] = (float) $rates->min();
            $node['annualPercentageRate'] = (float) $rates->min();
        }

        $feeParts = [];
        if ($rates->isNotEmpty()) {
            $minR = (float) $rates->min();
            $maxR = (float) $rates->max();
            $feeParts[] = $minR === $maxR
                ? 'Ставка: '.$minR.'%'
                : 'Ставка: '.$minR.'% — '.$maxR.'%';
        }
        $psk = $collection->map(fn (Credit $c) => $c->psk)->filter(fn ($p) => $p !== null && (float) $p > 0);
        if ($psk->isNotEmpty()) {
            $minP = (float) $psk->min();
            $maxP = (float) $psk->max();
            $feeParts[] = $minP === $maxP
                ? 'ПСК: '.$minP.'%'
                : 'ПСК: '.$minP.'% — '.$maxP.'%';
        }
        if ($feeParts !== []) {
            $node['feesAndCommissionsSpecification'] = implode(', ', $feeParts);
        }

        $lows = $collection->pluck('min_amount')->filter(fn ($x) => $x !== null && (float) $x > 0);
        $highs = $collection->pluck('max_amount')->filter(fn ($x) => $x !== null && (float) $x > 0);
        $offer = [
            '@type' => 'AggregateOffer',
            'priceCurrency' => 'RUB',
        ];
        if ($lows->isNotEmpty()) {
            $offer['lowPrice'] = (float) $lows->min();
        }
        if ($highs->isNotEmpty()) {
            $offer['highPrice'] = (float) $highs->max();
        }
        if (isset($offer['lowPrice']) || isset($offer['highPrice'])) {
            $node['offers'] = $offer;
        }

        return $node;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function buildCategoryFinancialProductDeposit(string $pageUrl, DepositCategory $category, mixed $items): ?array
    {
        $collection = self::collectPaginatorItems($items)->filter(fn ($m) => $m instanceof Deposit);
        $node = [
            '@type' => 'FinancialProduct',
            '@id' => $pageUrl.'/#category-financial-product',
            'name' => (string) $category->title,
            'url' => $pageUrl,
        ];
        $desc = trim(strip_tags((string) ($category->subtitle ?? $category->description ?? '')));
        if ($desc !== '') {
            $node['description'] = $desc;
        }

        if ($collection->isEmpty()) {
            return $node;
        }

        $rates = [];
        $lowAmounts = [];
        $highAmounts = [];
        foreach ($collection as $deposit) {
            if (! $deposit instanceof Deposit) {
                continue;
            }
            $deposit->loadMissing('currencies.conditions');
            foreach ($deposit->currencies as $cur) {
                if ($cur->min_amount !== null) {
                    $lowAmounts[] = (float) $cur->min_amount;
                }
                if ($cur->max_amount !== null) {
                    $highAmounts[] = (float) $cur->max_amount;
                }
                foreach ($cur->conditions as $cond) {
                    if ($cond->rate !== null && (float) $cond->rate > 0) {
                        $rates[] = (float) $cond->rate;
                    }
                }
            }
        }

        $ratesC = collect($rates);
        if ($ratesC->isNotEmpty()) {
            $node['interestRate'] = (float) $ratesC->max();
            $node['annualPercentageRate'] = (float) $ratesC->max();
            $node['feesAndCommissionsSpecification'] = 'Ставка: до '.$ratesC->max().'% годовых';
        }

        $lows = collect($lowAmounts);
        $highs = collect($highAmounts);
        $offer = [
            '@type' => 'AggregateOffer',
            'priceCurrency' => 'RUB',
        ];
        if ($lows->isNotEmpty()) {
            $offer['lowPrice'] = (float) $lows->min();
        }
        if ($highs->isNotEmpty()) {
            $offer['highPrice'] = (float) $highs->max();
        }
        if (isset($offer['lowPrice']) || isset($offer['highPrice'])) {
            $node['offers'] = $offer;
        }

        return $node;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function buildCategoryFinancialProductCard(string $pageUrl, CardCategory $category, mixed $items): ?array
    {
        $collection = self::collectPaginatorItems($items)->filter(fn ($m) => $m instanceof Card);
        $node = [
            '@type' => 'FinancialProduct',
            '@id' => $pageUrl.'/#category-financial-product',
            'name' => (string) $category->title,
            'url' => $pageUrl,
        ];
        $desc = trim(strip_tags((string) ($category->subtitle ?? $category->description ?? '')));
        if ($desc !== '') {
            $node['description'] = $desc;
        }

        if ($collection->isEmpty()) {
            return $node;
        }

        $rates = $collection->map(fn (Card $c) => $c->rate)->filter(fn ($r) => $r !== null && (float) $r > 0);
        if ($rates->isNotEmpty()) {
            $node['interestRate'] = (float) $rates->min();
            $node['annualPercentageRate'] = (float) $rates->min();
        }

        $limits = $collection->map(fn (Card $c) => $c->credit_limit)->filter(fn ($l) => $l !== null && (float) $l > 0);
        $offer = [
            '@type' => 'AggregateOffer',
            'priceCurrency' => 'RUB',
        ];
        if ($limits->isNotEmpty()) {
            $offer['lowPrice'] = (float) $limits->min();
            $offer['highPrice'] = (float) $limits->max();
            $node['offers'] = $offer;
        }

        $feeParts = [];
        if ($rates->isNotEmpty()) {
            $feeParts[] = 'Ставка: '.$rates->min().'%'.($rates->max() != $rates->min() ? ' — '.$rates->max().'%' : '');
        }
        $psk = $collection->map(fn (Card $c) => $c->psk)->filter(fn ($p) => $p !== null && (float) $p > 0);
        if ($psk->isNotEmpty()) {
            $feeParts[] = 'ПСК: '.$psk->min().'%'.($psk->max() != $psk->min() ? ' — '.$psk->max().'%' : '');
        }
        if ($feeParts !== []) {
            $node['feesAndCommissionsSpecification'] = implode(', ', $feeParts);
        }

        return $node;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function buildCategoryFinancialProductLoan(string $pageUrl, LoanCategory $category, mixed $items): ?array
    {
        $collection = self::collectPaginatorItems($items)->filter(fn ($m) => $m instanceof Loan);
        $node = [
            '@type' => 'FinancialProduct',
            '@id' => $pageUrl.'/#category-financial-product',
            'name' => (string) $category->title,
            'url' => $pageUrl,
        ];
        $desc = trim(strip_tags((string) ($category->subtitle ?? $category->description ?? '')));
        if ($desc !== '') {
            $node['description'] = $desc;
        }

        if ($collection->isEmpty()) {
            return $node;
        }

        $rates = $collection->map(fn (Loan $l) => $l->rate)->filter(fn ($r) => $r !== null && (float) $r > 0);
        if ($rates->isNotEmpty()) {
            $node['interestRate'] = (float) $rates->min();
            $node['annualPercentageRate'] = (float) $rates->min();
        }

        $lows = $collection->pluck('min_amount')->filter(fn ($x) => $x !== null && (float) $x > 0);
        $highs = $collection->pluck('max_amount')->filter(fn ($x) => $x !== null && (float) $x > 0);
        $offer = [
            '@type' => 'AggregateOffer',
            'priceCurrency' => 'RUB',
        ];
        if ($lows->isNotEmpty()) {
            $offer['lowPrice'] = (float) $lows->min();
        }
        if ($highs->isNotEmpty()) {
            $offer['highPrice'] = (float) $highs->max();
        }
        if (isset($offer['lowPrice']) || isset($offer['highPrice'])) {
            $node['offers'] = $offer;
        }

        $feeParts = [];
        if ($rates->isNotEmpty()) {
            $feeParts[] = 'Ставка: '.$rates->min().'%'.($rates->max() != $rates->min() ? ' — '.$rates->max().'%' : '');
        }
        if ($feeParts !== []) {
            $node['feesAndCommissionsSpecification'] = implode(', ', $feeParts);
        }

        return $node;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function buildCategoryFinancialProductBankCategory(string $pageUrl, BankCategory $category, mixed $items): ?array
    {
        $collection = self::collectPaginatorItems($items)->filter(fn ($m) => $m instanceof Bank);
        $node = [
            '@type' => 'FinancialProduct',
            '@id' => $pageUrl.'/#category-financial-product',
            'name' => (string) $category->title,
            'url' => $pageUrl,
        ];
        $desc = trim(strip_tags((string) ($category->subtitle ?? $category->description ?? '')));
        if ($desc !== '') {
            $node['description'] = $desc;
        }

        if ($collection->isEmpty()) {
            return $node;
        }

        $ratings = $collection->map(fn (Bank $b) => $b->rating)->filter(fn ($r) => $r !== null && (float) $r > 0);
        if ($ratings->isNotEmpty()) {
            $node['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round((float) $ratings->avg(), 2),
                'bestRating' => 5,
                'worstRating' => 1,
            ];
        }

        return $node;
    }

    /**
     * @param  array<int, array<string, mixed>>  $graph
     */
    private static function appendFinancialProductEntity(
        array &$graph,
        array $v,
        string $pageUrl,
        string $pageDescription,
        mixed $pageImage,
        object $productEntity,
        string $orgName
    ): void {
        $productImageRaw = $productEntity->image ?? $productEntity->logo ?? $productEntity->logo_square ?? null;
        $productImage = null;
        if (is_string($productImageRaw) && trim($productImageRaw) !== '') {
            $img = trim($productImageRaw);
            $productImage = str_starts_with($img, 'http') ? $img : asset('storage/'.ltrim($img, '/'));
        }

        $desc = trim((string) ($productEntity->short_description ?? $productEntity->description ?? $pageDescription));
        if ($desc === '' && $productEntity instanceof Bank) {
            $desc = trim((string) ($productEntity->description ?? ''));
        }

        $productNode = [
            '@type' => 'FinancialProduct',
            '@id' => $pageUrl.'/#product',
            'name' => (string) $productEntity->name,
            'url' => $pageUrl,
        ];
        if ($desc !== '') {
            $productNode['description'] = $desc;
        }

        if ($productImage) {
            $productNode['image'] = [$productImage];
        } elseif (is_string($pageImage) && $pageImage !== '') {
            $productNode['image'] = [$pageImage];
        }

        self::applyFinancialProductTerms($productNode, $productEntity);
        self::applyFinancialProductProvider($productNode, $productEntity);
        self::applyFinancialProductOffersAndRating($productNode, $productEntity);

        $graph[] = array_filter($productNode, fn ($val) => ! is_null($val) && $val !== '');
    }

    /**
     * @param  array<string, mixed>  $productNode
     */
    private static function applyFinancialProductTerms(array &$productNode, object $entity): void
    {
        if ($entity instanceof Credit) {
            if ($entity->rate !== null && (float) $entity->rate > 0) {
                $productNode['interestRate'] = (float) $entity->rate;
                $productNode['annualPercentageRate'] = (float) $entity->rate;
            }
            if ($entity->psk !== null && (float) $entity->psk > 0) {
                $parts = ['ПСК: '.(float) $entity->psk.'%'];
                if ($entity->rate !== null && (float) $entity->rate > 0) {
                    array_unshift($parts, 'Ставка: '.(float) $entity->rate.'%');
                }
                $productNode['feesAndCommissionsSpecification'] = implode(', ', $parts);
            } elseif ($entity->rate !== null && (float) $entity->rate > 0) {
                $productNode['feesAndCommissionsSpecification'] = 'Ставка: '.(float) $entity->rate.'%';
            }

            return;
        }

        if ($entity instanceof Deposit) {
            $entity->loadMissing('currencies.conditions');
            $rates = [];
            foreach ($entity->currencies as $cur) {
                foreach ($cur->conditions as $cond) {
                    if ($cond->rate !== null && (float) $cond->rate > 0) {
                        $rates[] = (float) $cond->rate;
                    }
                }
            }
            if ($rates !== []) {
                $maxR = max($rates);
                $productNode['interestRate'] = $maxR;
                $productNode['annualPercentageRate'] = $maxR;
                $productNode['feesAndCommissionsSpecification'] = 'Ставка: до '.$maxR.'% годовых';
            }

            return;
        }

        if ($entity instanceof Card) {
            if ($entity->rate !== null && (float) $entity->rate > 0) {
                $productNode['interestRate'] = (float) $entity->rate;
                $productNode['annualPercentageRate'] = (float) $entity->rate;
            }
            $parts = [];
            if ($entity->rate !== null && (float) $entity->rate > 0) {
                $parts[] = 'Ставка: '.(float) $entity->rate.'%';
            }
            if ($entity->psk !== null && (float) $entity->psk > 0) {
                $parts[] = 'ПСК: '.(float) $entity->psk.'%';
            }
            if ($parts !== []) {
                $productNode['feesAndCommissionsSpecification'] = implode(', ', $parts);
            }

            return;
        }

        if ($entity instanceof Loan) {
            $r = $entity->rate ?? $entity->rate_min;
            if ($r !== null && (float) $r > 0) {
                $productNode['interestRate'] = (float) $r;
                $productNode['annualPercentageRate'] = (float) $r;
                $productNode['feesAndCommissionsSpecification'] = 'Ставка: '.(float) $r.'%';
            }

            return;
        }

        if ($entity instanceof Bank) {
            if ($entity->rating !== null && (float) $entity->rating > 0) {
                $publishedReviews = method_exists($entity, 'reviews')
                    ? (int) $entity->reviews()->where('is_published', true)->count()
                    : 0;
                $agg = [
                    '@type' => 'AggregateRating',
                    'ratingValue' => round((float) $entity->rating, 2),
                    'bestRating' => 5,
                    'worstRating' => 1,
                ];
                if ($publishedReviews > 0) {
                    $agg['ratingCount'] = $publishedReviews;
                }
                $productNode['aggregateRating'] = $agg;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $productNode
     */
    private static function applyFinancialProductProvider(array &$productNode, object $entity): void
    {
        if ($entity instanceof Loan) {
            $logo = null;
            if (! empty($entity->logo)) {
                $raw = trim((string) $entity->logo);
                $logo = str_starts_with($raw, 'http') ? $raw : asset('storage/'.ltrim($raw, '/'));
            }
            $website = isset($entity->website) && is_string($entity->website) && $entity->website !== ''
                ? $entity->website
                : null;
            $productNode['provider'] = array_filter([
                '@type' => 'Organization',
                'name' => (string) ($entity->company_name !== '' ? $entity->company_name : $entity->name),
                'url' => $website,
                'logo' => $logo,
                'image' => $logo,
            ], fn ($val) => $val !== null && $val !== '');

            return;
        }

        if ($entity instanceof Bank) {
            $logo = null;
            if (! empty($entity->logo_square ?? $entity->logo)) {
                $raw = (string) ($entity->logo_square ?? $entity->logo);
                $logo = str_starts_with($raw, 'http') ? $raw : asset('storage/'.ltrim($raw, '/'));
            }
            $productNode['provider'] = array_filter([
                '@type' => 'Organization',
                'name' => (string) $entity->name,
                'url' => url_canonical(url_section('banki/'.$entity->slug)),
                'additionalType' => 'https://schema.org/BankOrCreditUnion',
                'logo' => $logo,
                'image' => $logo,
            ], fn ($v) => $v !== null && $v !== '');

            return;
        }

        $bank = $entity->bank ?? null;
        if ($bank instanceof Bank) {
            $logo = null;
            if (! empty($bank->logo_square ?? $bank->logo)) {
                $raw = (string) ($bank->logo_square ?? $bank->logo);
                $logo = str_starts_with($raw, 'http') ? $raw : asset('storage/'.ltrim($raw, '/'));
            }
            $productNode['provider'] = array_filter([
                '@type' => 'Organization',
                'name' => (string) $bank->name,
                'url' => url_canonical(url_section('banki/'.$bank->slug)),
                'additionalType' => 'https://schema.org/BankOrCreditUnion',
                'logo' => $logo,
                'image' => $logo,
            ], fn ($v) => $v !== null && $v !== '');
        }
    }

    /**
     * @param  array<string, mixed>  $productNode
     */
    private static function applyFinancialProductOffersAndRating(array &$productNode, object $entity): void
    {
        if ($entity instanceof Bank) {
            return;
        }

        $low = null;
        $high = null;
        if (isset($entity->min_amount) && $entity->min_amount !== null && (float) $entity->min_amount > 0) {
            $low = (float) $entity->min_amount;
        }
        if (isset($entity->max_amount) && $entity->max_amount !== null && (float) $entity->max_amount > 0) {
            $high = (float) $entity->max_amount;
        }

        if ($entity instanceof Card && $entity->credit_limit !== null && (float) $entity->credit_limit > 0) {
            $low = (float) $entity->credit_limit;
            $high = (float) $entity->credit_limit;
        }

        if ($low !== null || $high !== null) {
            $offer = [
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'RUB',
            ];
            if ($low !== null) {
                $offer['lowPrice'] = $low;
            }
            if ($high !== null) {
                $offer['highPrice'] = $high;
            }
            $productNode['offers'] = $offer;
        }

        $ratingValue = null;
        $ratingCount = 0;
        if (isset($entity->review_rating) && is_numeric($entity->review_rating)) {
            $ratingValue = (float) $entity->review_rating;
        } elseif (isset($entity->rating) && is_numeric($entity->rating) && ! ($entity instanceof Bank)) {
            $ratingValue = (float) $entity->rating;
        }
        if (isset($entity->review_count) && is_numeric($entity->review_count)) {
            $ratingCount = (int) $entity->review_count;
        }
        if ($ratingCount === 0 && method_exists($entity, 'reviews')) {
            $ratingCount = (int) $entity->reviews()->where('is_published', true)->count();
            if ($ratingValue === null && $ratingCount > 0) {
                $avg = $entity->reviews()->where('is_published', true)->avg('rating');
                $ratingValue = $avg !== null ? (float) $avg : null;
            }
        }

        if ($ratingValue !== null && $ratingCount > 0) {
            $productNode['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round($ratingValue, 2),
                'bestRating' => 5,
                'worstRating' => 1,
                'ratingCount' => $ratingCount,
            ];
        }
    }
}
