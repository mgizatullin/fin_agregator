<!DOCTYPE html>
<!--[if IE 8]><html class="ie" xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru-RU" lang="ru-RU"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!-->
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru-RU" lang="ru-RU">
<!--<![endif]-->

<head>
    <!-- Basic Page Needs -->
    <meta charset="utf-8">
    <!--[if IE ]><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/><![endif]-->
    <title>
@if(!empty($seo_title))
    {{ $seo_title }}
@elseif(!empty($pageTitle))
    {{ $pageTitle }} - {{ config('app.name') }}
@elseif(!empty($title))
    {{ $title }} - {{ config('app.name') }}
@elseif(isset($section) && !empty($section->title))
    {{ $section->title }} - {{ config('app.name') }}
@else
    {{ config('app.name') }}
@endif
    </title>

@if(!empty($seo_description))
    <meta name="description" content="{{ $seo_description }}">
@endif

    @php
        $metaTitle = trim((string) (
            $seo_title
            ?? $pageTitle
            ?? $title
            ?? ($section->title ?? config('app.name'))
        ));
        $metaDescription = trim((string) ($seo_description ?? ''));
        $canonicalUrl = $canonical_url ?? url()->current();
        if (is_string($canonicalUrl) && $canonicalUrl !== '') {
            $path = (string) (parse_url($canonicalUrl, PHP_URL_PATH) ?? '');
            if ($path !== '' && $path !== '/' && ! str_ends_with($canonicalUrl, '/')) {
                $canonicalUrl .= '/';
            }
        }

        $fallbackOgImage = !empty($siteSettings->logo ?? null)
            ? (str_starts_with($siteSettings->logo, 'http') ? $siteSettings->logo : asset('storage/' . ltrim((string) $siteSettings->logo, '/')))
            : asset('assets/images/logo/favicon.svg');

        $rawOgImage = $og_image
            ?? $seo_image
            ?? ($article->image ?? null)
            ?? ($post->image ?? null)
            ?? ($service->image ?? null)
            ?? ($page->image ?? null)
            ?? null;

        $resolvedOgImage = null;
        if (is_string($rawOgImage) && trim($rawOgImage) !== '') {
            $rawOgImage = trim($rawOgImage);
            if (str_starts_with($rawOgImage, 'http')) {
                $resolvedOgImage = $rawOgImage;
            } elseif (str_starts_with($rawOgImage, 'storage/')) {
                $resolvedOgImage = asset($rawOgImage);
            } else {
                $resolvedOgImage = asset('storage/' . ltrim($rawOgImage, '/'));
            }
        }

        $metaOgImage = $resolvedOgImage ?: $fallbackOgImage;
    @endphp
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $metaOgImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $metaOgImage }}">
    @include('layouts.partials.schema-jsonld')

    <!-- Mobile Specific Metas -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <!-- Theme Style -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/animate.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/swiper-bundle.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/styles.css') }}?v={{ @filemtime(public_path('assets/css/styles.css')) ?: time() }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/city-dialog.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/article-typography.css') }}">

    @livewireStyles
    @stack('styles')
    <!-- Font -->
    <link rel="stylesheet" href="{{ asset('assets/fonts/fonts.css') }}">

    <!-- Icon -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/fonts/icons/icomoon/style.css') }}">

    <!-- Favicon and Touch Icons  -->
    <link rel="shortcut icon" href="{{ asset('assets/images/logo/favicon.svg') }}">
    <link rel="apple-touch-icon-precomposed" href="{{ asset('assets/images/logo/favicon.svg') }}">
</head>

<body class="{{ request()->routeIs('home') ? 'page-home' : '' }}">
    <!-- wrapper -->
    <div id="wrapper">

        @include('layouts.partials.preloader')

        @hasSection('page-header')
        <div class="wrap-page-header">
            @include('layouts.partials.header')
            @yield('page-header')
                                        </div>
        @else
        @include('layouts.partials.header')
        @endif

@yield('content')

        @include('layouts.partials.footer')

    </div>
    <!-- /wrapper -->

    <!-- .prograss -->
    <div class="scrollTop effect-icon">
        <div class="icon">
            <i class="icon-long-arrow-alt-up-solid"></i>
        </div>
        <div class="liquid">
            <svg viewbox="0 0 560 20" class="liquid_wave liquid_wave_back">
                <use xlink:href="#wave"></use>
            </svg>
            <svg viewbox="0 0 560 20" class="liquid_wave liquid_wave_front">
                <use xlink:href="#liquid"></use>
            </svg>
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewbox="0 0 560 20" style="display: none;">
                <symbol id="liquid">
                    <path d="M420,20c21.5-0.4,38.8-2.5,51.1-4.5c13.4-2.2,26.5-5.2,27.3-5.4C514,6.5,518,4.7,528.5,2.7c7.1-1.3,17.9-2.8,31.5-2.7c0,0,0,0,0,0v20H420z" fill="#" style="transition: stroke-dashoffset 10ms linear; stroke-dasharray: 301.839, 301.839; stroke-dashoffset: 251.895px;">
                    </path>
                    <path d="M420,20c-21.5-0.4-38.8-2.5-51.1-4.5c-13.4-2.2-26.5-5.2-27.3-5.4C326,6.5,322,4.7,311.5,2.7C304.3,1.4,293.6-0.1,280,0c0,0,0,0,0,0v20H420z" fill="#"></path>
                    <path d="M140,20c21.5-0.4,38.8-2.5,51.1-4.5c13.4-2.2,26.5-5.2,27.3-5.4C234,6.5,238,4.7,248.5,2.7c7.1-1.3,17.9-2.8,31.5-2.7c0,0,0,0,0,0v20H140z" fill="#"></path>
                    <path d="M140,20c-21.5-0.4-38.8-2.5-51.1-4.5c-13.4-2.2-26.5-5.2-27.3-5.4C46,6.5,42,4.7,31.5,2.7C24.3,1.4,13.6-0.1,0,0c0,0,0,0,0,0l0,20H140z" fill="#"></path>
                </symbol>
            </svg>

        </div>
    </div>
    <!-- /.prograss -->

    <!-- Start Mobile Menu -->
    <div class="offcanvas offcanvas-start canvas-mb" id="menu-mobile">
        <div class="offcanvas-header top-nav-mobile justify-content-between">
            <a href="/" class="logo">
                @php
                    $mobileLogoUrl = !empty($siteSettings->logo ?? null)
                        ? (str_starts_with($siteSettings->logo, 'http') ? $siteSettings->logo : asset('storage/' . $siteSettings->logo))
                        : asset('assets/images/logo/favicon.svg');
                @endphp
                <img src="{{ $mobileLogoUrl }}" alt="{{ config('app.name') }}">
            </a>
            <div class="close-menu" data-bs-dismiss="offcanvas">
                <i class=" icon-times-solid"></i>
            </div>
        </div>
        <div class="mb-canvas-content">
            <div class="mb-body">
                <div class="mb-content-top">
                    <ul class="nav-ul-mb" id="wrapper-menu-navigation">
                        @php
                            $mobileNav = is_array($siteSettings->navigation ?? null) ? $siteSettings->navigation : [];
                            $currentUrl = rtrim(url()->current(), '/') ?: '/';

                            $normalizeNavUrl = function ($raw) {
                                $raw = is_string($raw) ? trim($raw) : '';
                                if ($raw === '') return '#';
                                if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://') || str_starts_with($raw, '//')) return $raw;
                                $raw = '/' . ltrim($raw, '/');
                                return $raw === '' ? '/' : $raw;
                            };

                            $isActiveNavItem = function (array $item) use ($normalizeNavUrl, $currentUrl) {
                                $url = $normalizeNavUrl($item['url'] ?? '');
                                $urlToCompare = is_string($url) ? rtrim(parse_url($url, PHP_URL_PATH) ?: $url, '/') : '';
                                $urlToCompare = $urlToCompare === '' ? '/' : $urlToCompare;
                                if ($urlToCompare !== '#' && $urlToCompare === $currentUrl) return true;
                                $children = $item['children'] ?? [];
                                if (!is_array($children)) return false;
                                foreach ($children as $child) {
                                    if (!is_array($child)) continue;
                                    $childUrl = $normalizeNavUrl($child['url'] ?? '');
                                    $childCompare = is_string($childUrl) ? rtrim(parse_url($childUrl, PHP_URL_PATH) ?: $childUrl, '/') : '';
                                    $childCompare = $childCompare === '' ? '/' : $childCompare;
                                    if ($childCompare !== '#' && $childCompare === $currentUrl) return true;
                                }
                                return false;
                            };
                        @endphp

                        @foreach($mobileNav as $i => $item)
                            @php
                                $item = is_array($item) ? $item : [];
                                $title = (string) ($item['title'] ?? '');
                                $children = $item['children'] ?? [];
                                $hasChildren = is_array($children) && count($children) > 0;
                                $itemActive = $isActiveNavItem($item);
                                $collapseId = 'dropdown-menu-mb-' . $i;
                            @endphp
                            <li class="nav-mb-item{{ $itemActive ? ' active' : '' }}">
                                @if($hasChildren)
                                    <a href="#{{ $collapseId }}" class="collapsed mb-menu-link" data-bs-toggle="collapse" aria-expanded="{{ $itemActive ? 'true' : 'false' }}" aria-controls="{{ $collapseId }}">
                                        <span>{{ $title }}</span>
                                        <span class="btn-open-sub"></span>
                                    </a>
                                    <div id="{{ $collapseId }}" class="collapse{{ $itemActive ? ' show' : '' }}" data-bs-parent="#menu-mobile">
                                        <ul class="sub-nav-menu">
                                            @foreach($children as $child)
                                                @php
                                                    $child = is_array($child) ? $child : [];
                                                    $childTitle = (string) ($child['title'] ?? '');
                                                    $childUrl = $normalizeNavUrl($child['url'] ?? '');
                                                    $childCompare = is_string($childUrl) ? rtrim(parse_url($childUrl, PHP_URL_PATH) ?: $childUrl, '/') : '';
                                                    $childCompare = $childCompare === '' ? '/' : $childCompare;
                                                    $childActive = ($childCompare !== '#' && $childCompare === $currentUrl);
                                                @endphp
                                                <li>
                                                    <a href="{{ $childUrl }}" class="sub-nav-link{{ $childActive ? ' active' : '' }}">{{ $childTitle }}</a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @else
                                    @php($url = $normalizeNavUrl($item['url'] ?? ''))
                                    <a href="{{ $url }}" class="mb-menu-link">
                                        <span>{{ $title }}</span>
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="mb-other-content ">
                    <ul class="mb-info mb_20">
                        @if(filled($siteSettings?->footer_under_logo))
                            <li>
                                {!! $siteSettings->footer_under_logo !!}
                            </li>
                        @else
                            <li>
                                <p class="text_mono-gray">
                                    Адрес:
                                    <a target="_blank" href="https://www.google.com/maps?q=16/9,ScotlandUnitedKingdom">
                                        <span class="fw-5 text_mono-gray-5">16/9, Шотландия, Великобритания</span>
                                    </a>
                                </p>
                            </li>
                            <li>
                                <p class="text_mono-gray">
                                    Эл. почта:
                                    <a href="mailto:themesflat@gmail.com">
                                        <span class="fw-5 text_mono-gray-5">themesflat@gmail.com</span>
                                    </a>
                                </p>
                            </li>
                            <li>
                                <p class="text_mono-gray">
                                    Телефон:
                                    <a href="tel:+11635565389">
                                        <span class="fw-5 text_mono-gray-5">+1 16355 65389</span>
                                    </a>
                                </p>
                            </li>
                        @endif
                    </ul>
                    <div class="mb-wrap-btn d-flex gap_12 flex-wrap">
                        @if(!empty($currencyRates))
                            <div class="header-rates header-rates--mobile d-flex align-items-center gap_12 mb-2 w-100">
                                @if(isset($currencyRates['USD']))<span class="header-rates__item">USD {{ number_format((float)$currencyRates['USD'], 4, '.', '') }}</span>@endif
                                @if(isset($currencyRates['EUR']))<span class="header-rates__item">EUR {{ number_format((float)$currencyRates['EUR'], 4, '.', '') }}</span>@endif
                                @if(isset($currencyRates['CNY']))<span class="header-rates__item">CNY {{ number_format((float)$currencyRates['CNY'], 4, '.', '') }}</span>@endif
                            </div>
                        @endif
                        <button type="button" class="city-select-btn" data-section-base="{{ $headerCitySelectBase ?? '' }}">
                            <span class="header-city-label">@isset($city){{ $city->name }}@else{{ $cityName ?? 'Вся Россия' }}@endisset</span>
                            <span class="bg-effect"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Mobile Menu -->

    <!-- Javascript -->
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/lazysize.min.js') }}"></script>
    <script src="{{ asset('assets/js/simpleParAllaxVanilla.umd.js') }}"></script>
    <script src="{{ asset('assets/js/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/carousel.js') }}"></script>
    <script src="{{ asset('assets/js/ScrollSmooth.js') }}"></script>
    <script src="{{ asset('assets/js/infinityslide.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    @stack('redirect-city')
    <script>
    (function(){ if (typeof window.__REDIRECT_TO_CITY === 'undefined') window.__REDIRECT_TO_CITY = { enabled: false }; })();
    </script>
    <script src="{{ asset('assets/js/city-dialog.js') }}"></script>
    @livewireScripts
    @stack('scripts')
    @if(!empty($siteSettings?->custom_scripts))
    {!! $siteSettings->custom_scripts !!}
    @endif
    <!-- /Javascript -->

</body>

</html>
