<!DOCTYPE html>
<!--[if IE 8]><html class="ie" xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru-RU" lang="ru-RU"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!-->
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru-RU" lang="ru-RU">
<!--<![endif]-->

<head>
    <meta charset="utf-8">
    <!--[if IE ]><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/><![endif]-->
    <title>{{ $section->title ?? config('app.name', 'Финансовый маркетплейс') }}</title>

    <meta name="description" content="{{ $section->subtitle ?? config('app.name') }}">
    <meta name="keywords" content="">

    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/animate.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/swiper-bundle.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/styles.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/city-dialog.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/fonts/fonts.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/fonts/icons/icomoon/style.css') }}">

    <link rel="shortcut icon" href="{{ asset('assets/images/logo/favicon.svg') }}">
    <link rel="apple-touch-icon-precomposed" href="{{ asset('assets/images/logo/favicon.svg') }}">
</head>

<body>
    <div id="wrapper">
        <div class="wrap-page-header">
            <div class="page-title style-default">
                <div class="tf-container">
                    <div class="row">
                        <div class="col-12">
                            <div class="heading mb_51">
                                <h1 class="text_black letter-spacing-1 ">{{ $section->title ?? '' }}</h1>
                                @if(!empty($showCitySelect) && !empty($citySelectBase))
                                    <button type="button" class="city-select-btn" data-section-base="{{ $citySelectBase }}">
                                        <span class="header-city-label">@isset($city){{ $city->name }}@else{{ $cityName ?? 'Вся Россия' }}@endisset</span>
                                    </button>
                                @endif
                                <p class="sub-heading text_mono-gray-7">{{ $section->subtitle ?? '' }}</p>
                            </div>
                            <ul class="breadcrumb">
                                <li><a href="{{ url('/') }}" class="link">Главная</a></li>
                                @if(isset($sectionIndexUrl) && isset($sectionIndexTitle))
                                    <li><a href="{{ $sectionIndexUrl }}" class="link">{{ $sectionIndexTitle }}</a></li>
                                @endif
                                <li>{{ $section->title ?? '' }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div><!-- /.page-title -->
        </div>

        <div class="main-content style-1 ">
            @yield('content')
        </div>

    </div>

    <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/lazysize.min.js') }}"></script>
    <script src="{{ asset('assets/js/simpleParAllaxVanilla.umd.js') }}"></script>
    <script src="{{ asset('assets/js/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/carousel.js') }}"></script>
    <script src="{{ asset('assets/js/ScrollSmooth.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    @stack('redirect-city')
    <script>
    (function(){ if (typeof window.__REDIRECT_TO_CITY === 'undefined') window.__REDIRECT_TO_CITY = { enabled: false }; })();
    </script>
    <script src="{{ asset('assets/js/city-dialog.js') }}"></script>
</body>

</html>

