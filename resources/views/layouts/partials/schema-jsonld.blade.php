@php
    $siteUrl = url('/');
    $pageUrl = $canonical_url ?? url()->current();
    $siteName = config('app.name');
    $orgName = (string) ($siteSettings->display_name ?? $siteName);
    $logoUrl = !empty($siteSettings->logo ?? null)
        ? (str_starts_with((string) $siteSettings->logo, 'http') ? $siteSettings->logo : asset('storage/' . ltrim((string) $siteSettings->logo, '/')))
        : asset('assets/images/logo/favicon.svg');
    $pageTitle = trim((string) ($seo_title ?? $title ?? ($section->title ?? $siteName)));
    $pageDescription = trim((string) ($seo_description ?? ($section->subtitle ?? '')));
    $pageImage = $metaOgImage ?? null;

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

    $breadcrumbsSource = $breadcrumbs ?? [];
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

    if (isset($article) && ! empty($article->title)) {
        $articleImage = null;
        if (! empty($article->image)) {
            $articleImage = str_starts_with((string) $article->image, 'http')
                ? (string) $article->image
                : asset('storage/' . ltrim((string) $article->image, '/'));
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
        $graph[] = array_filter($articleNode, fn ($v) => ! is_null($v) && $v !== '');
    }

    $productEntity = $credit ?? $deposit ?? $card ?? $loan ?? $bank ?? $service ?? null;
    if ($productEntity && ! empty($productEntity->name)) {
        $productImageRaw = $productEntity->image ?? $productEntity->logo ?? $productEntity->logo_square ?? null;
        $productImage = null;
        if (is_string($productImageRaw) && trim($productImageRaw) !== '') {
            $img = trim($productImageRaw);
            $productImage = str_starts_with($img, 'http') ? $img : asset('storage/' . ltrim($img, '/'));
        }

        $productNode = [
            '@type' => 'Product',
            '@id' => $pageUrl.'/#product',
            'name' => (string) $productEntity->name,
            'description' => trim((string) ($productEntity->short_description ?? $productEntity->description ?? $pageDescription)),
            'url' => $pageUrl,
            'brand' => [
                '@type' => 'Brand',
                'name' => (string) ($productEntity->bank->name ?? $productEntity->name),
            ],
        ];

        if ($productImage) {
            $productNode['image'] = [$productImage];
        } elseif (is_string($pageImage) && $pageImage !== '') {
            $productNode['image'] = [$pageImage];
        }

        $offerPrice = null;
        if (isset($productEntity->annual_fee) && is_numeric($productEntity->annual_fee)) {
            $offerPrice = (float) $productEntity->annual_fee;
        } elseif (isset($productEntity->price) && is_numeric($productEntity->price)) {
            $offerPrice = (float) $productEntity->price;
        }
        if (! is_null($offerPrice)) {
            $productNode['offers'] = [
                '@type' => 'Offer',
                'url' => $pageUrl,
                'priceCurrency' => 'RUB',
                'price' => $offerPrice,
                'availability' => 'https://schema.org/InStock',
            ];
        }

        $reviewCount = method_exists($productEntity, 'reviews') ? (int) $productEntity->reviews()->count() : 0;
        $ratingValue = null;
        if (isset($productEntity->review_rating) && is_numeric($productEntity->review_rating)) {
            $ratingValue = (float) $productEntity->review_rating;
        } elseif (isset($productEntity->rating) && is_numeric($productEntity->rating)) {
            $ratingValue = (float) $productEntity->rating;
        } elseif ($reviewCount > 0 && method_exists($productEntity, 'reviews')) {
            $ratingValue = (float) $productEntity->reviews()->avg('rating');
        }

        if (! is_null($ratingValue) && $reviewCount > 0) {
            $productNode['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round($ratingValue, 2),
                'reviewCount' => $reviewCount,
            ];
        }

        $graph[] = array_filter($productNode, fn ($v) => ! is_null($v) && $v !== '');
    }

    $faqItems = $faqItems ?? $faqs ?? null;
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

    $qaItems = $qaItems ?? null;
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
@endphp

<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => array_values($graph),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
