<!DOCTYPE html>
<!--[if IE 8]><html class="ie" xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru-RU" lang="ru-RU"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!-->
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru-RU" lang="ru-RU">
<!--<![endif]-->

<head>
    <!-- Basic Page Needs -->
    <meta charset="utf-8">
    <!--[if IE ]><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/><![endif]-->
    <title>{{ $pageTitle ?? config("app.name", "Финансовый маркетплейс") }}</title>

    <meta name="description" content="{{ $metaDescription ?? config("app.name") }}">


    <!-- Mobile Specific Metas -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <!-- Theme Style -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/animate.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/swiper-bundle.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/odometer.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/styles.css') }}">

    <!-- Font -->
    <link rel="stylesheet" href="{{ asset('assets/fonts/fonts.css') }}">

    <!-- Icon -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/fonts/' . substr('font/icons/icomoon/style.css', 6)) }}">

    <!-- Favicon and Touch Icons  -->
    <link rel="shortcut icon" href="{{ asset('assets/images/logo/favicon.svg') }}">
    <link rel="apple-touch-icon-precomposed" href="{{ asset('assets/images/logo/favicon.svg') }}">
</head>

<body>
    <!-- wrapper -->
    <div id="wrapper">


        <!-- .preload -->
        <div id="loading">
            <div id="loading-center">
                <div class="loader-container">
                    <div class="wrap-loader">
                        <div class="loader">
                        </div>
                        <div class="icon">
                            <img src="{{ asset('assets/images/logo/loading.png') }}" alt="logo">
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /.preload -->


        <!-- header -->
        <header id="header-main" class="header style-default  ">
            <div class="header-inner">
                <div class="tf-container">
                    <div class="row">
                        <div class="col-12">
                            <div class="header-inner-wrap">
                                <div class="header-left d-flex align-items-center">
                                    <div class="header-logo">
                                        <a href="index.html" class="site-logo">
                                            <svg width="149" height="46" viewBox="0 0 149 46" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <rect x="0.625" y="0.625" width="147.75" height="44.75" rx="22.375" fill="white"></rect>
                                                <rect x="0.625" y="0.625" width="147.75" height="44.75" rx="22.375" stroke="white" stroke-width="1.25"></rect>
                                                <path d="M32.3775 20.311L39.7515 32.7325C39.8499 32.8983 40.0285 33 40.2214 33L47.6138 33C47.8054 33 47.983 32.8997 48.0819 32.7356L52 26.2351L44.3144 26.2245C44.123 26.2242 43.9458 26.1239 43.847 25.9599L36.2013 13.2645C36.1024 13.1004 35.9248 13 35.7332 13L28.3861 13C28.1948 13 28.0174 13.1 27.9184 13.2638L23.9821 19.776L20.1708 26.052C20.0657 26.2251 20.0649 26.4421 20.1688 26.6159L23.823 32.7338C23.9217 32.8989 24.0998 33 24.2922 33L30.9792 33C31.4043 33 31.6666 32.5358 31.4473 32.1716L23.9821 19.776L31.4378 19.776C31.8235 19.776 32.1806 19.9793 32.3775 20.311Z" fill="#0075FF"></path>
                                                <path d="M60.56 30L65.7 16H67.88L73 30H70.88L66.78 18.4L62.64 30H60.56ZM62.88 26.54L63.44 24.94H70.02L70.56 26.54H62.88ZM78.4873 30.24C77.5273 30.24 76.674 30.0133 75.9273 29.56C75.194 29.1067 74.6207 28.4867 74.2073 27.7C73.8073 26.9 73.6073 26 73.6073 25C73.6073 23.9867 73.8073 23.0867 74.2073 22.3C74.6207 21.5133 75.2007 20.9 75.9473 20.46C76.694 20.0067 77.5473 19.78 78.5073 19.78C79.294 19.78 79.9873 19.94 80.5873 20.26C81.2007 20.5667 81.674 21 82.0073 21.56V15.6H84.0073V30H82.2073L82.0073 28.44C81.794 28.7467 81.5273 29.04 81.2073 29.32C80.8873 29.5867 80.5007 29.8067 80.0473 29.98C79.594 30.1533 79.074 30.24 78.4873 30.24ZM78.8073 28.5C79.434 28.5 79.9873 28.3533 80.4673 28.06C80.9473 27.7667 81.3207 27.36 81.5873 26.84C81.854 26.3067 81.9873 25.6933 81.9873 25C81.9873 24.3067 81.854 23.7 81.5873 23.18C81.3207 22.66 80.9473 22.2533 80.4673 21.96C79.9873 21.6667 79.434 21.52 78.8073 21.52C78.194 21.52 77.6473 21.6667 77.1673 21.96C76.6873 22.2533 76.314 22.66 76.0473 23.18C75.7807 23.7 75.6473 24.3067 75.6473 25C75.6473 25.6933 75.7807 26.3067 76.0473 26.84C76.314 27.36 76.6873 27.7667 77.1673 28.06C77.6473 28.3533 78.194 28.5 78.8073 28.5ZM89.1417 30L85.3817 20.02H87.4817L90.3217 28.08L93.1817 20.02H95.2417L91.5017 30H89.1417ZM96.7648 30V20.02H98.7648V30H96.7648ZM97.7648 18.08C97.3915 18.08 97.0782 17.96 96.8248 17.72C96.5715 17.48 96.4448 17.1733 96.4448 16.8C96.4448 16.44 96.5715 16.1467 96.8248 15.92C97.0782 15.68 97.3915 15.56 97.7648 15.56C98.1382 15.56 98.4515 15.68 98.7048 15.92C98.9582 16.1467 99.0848 16.44 99.0848 16.8C99.0848 17.1733 98.9582 17.48 98.7048 17.72C98.4515 17.96 98.1382 18.08 97.7648 18.08ZM105.345 30C104.705 30 104.152 29.9 103.685 29.7C103.218 29.5 102.858 29.1667 102.605 28.7C102.352 28.2333 102.225 27.6 102.225 26.8V21.7H100.505V20.02H102.225L102.485 17.52H104.225V20.02H107.065V21.7H104.225V26.82C104.225 27.3933 104.345 27.7867 104.585 28C104.825 28.2 105.238 28.3 105.825 28.3H106.965V30H105.345ZM113.159 30.24C112.185 30.24 111.325 30.0267 110.579 29.6C109.832 29.16 109.245 28.5533 108.819 27.78C108.405 26.9933 108.199 26.08 108.199 25.04C108.199 23.9867 108.405 23.0667 108.819 22.28C109.232 21.4933 109.812 20.88 110.559 20.44C111.319 20 112.192 19.78 113.179 19.78C114.165 19.78 115.012 20 115.719 20.44C116.439 20.8667 116.985 21.44 117.359 22.16C117.745 22.88 117.939 23.6733 117.939 24.54C117.939 24.6733 117.939 24.82 117.939 24.98C117.939 25.1267 117.932 25.2933 117.919 25.48H109.659V24.06H115.959C115.919 23.2467 115.639 22.6067 115.119 22.14C114.599 21.6733 113.945 21.44 113.159 21.44C112.625 21.44 112.125 21.56 111.659 21.8C111.205 22.04 110.839 22.3933 110.559 22.86C110.292 23.3267 110.159 23.9133 110.159 24.62V25.2C110.159 25.9333 110.292 26.5533 110.559 27.06C110.839 27.5533 111.205 27.9333 111.659 28.2C112.112 28.4533 112.605 28.58 113.139 28.58C113.779 28.58 114.305 28.44 114.719 28.16C115.145 27.8667 115.459 27.48 115.659 27H117.659C117.485 27.6133 117.192 28.1667 116.779 28.66C116.365 29.14 115.852 29.5267 115.239 29.82C114.639 30.1 113.945 30.24 113.159 30.24ZM118.234 30L121.734 25L118.234 20.02H120.394L123.054 23.92L125.734 20.02H127.894L124.394 25L127.894 30H125.734L123.054 26.1L120.394 30H118.234Z" fill="black"></path>
                                            </svg>

                                        </a>
                                    </div>
                                    <nav class="main-menu style-default">
                                        <ul class="navigation ">
                                            <li class="has-child  current-menu"><a href="#">Home</a>
                                                <div class="submenu mega-menu">
                                                    <div class="wrap-demo-item tf-grid-layout-lg lg-col-3 ">
                                                        <div class="demo-item current-menu-item ">
                                                            <a href="index.html">
                                                                <div class="demo-image">
                                                                    <img class=" ls-is-cached lazyloaded" data-src="images/demo/demo-1.jpg" src="images/demo/demo-1.jpg" alt="demo-1.jpg">
                                                                </div>
                                                                <h6 class="demo-name fw-4">Business Consulting
                                                                </h6>
                                                            </a>
                                                        </div>
                                                        <div class="demo-item">
                                                            <a href="finance-consulting.html">
                                                                <div class="demo-image">
                                                                    <img class=" ls-is-cached lazyloaded" data-src="images/demo/demo-2.jpg" src="images/demo/demo-2.jpg" alt="demo-2.jpg">
                                                                </div>
                                                                <h6 class="demo-name fw-4">Finance Consulting
                                                                </h6>
                                                            </a>
                                                        </div>
                                                        <div class="demo-item">
                                                            <a href="finance-advisor.html">
                                                                <div class="demo-image">
                                                                    <img class=" ls-is-cached lazyloaded" data-src="images/demo/demo-3.jpg" src="images/demo/demo-3.jpg" alt="home-3">
                                                                </div>
                                                                <h6 class="demo-name fw-4">Finance Advisor</h6>
                                                            </a>
                                                        </div>
                                                        <div class="demo-item ">
                                                            <a href="insurance-consulting.html">
                                                                <div class="demo-image">
                                                                    <img class=" ls-is-cached lazyloaded" data-src="images/demo/demo-4.jpg" src="images/demo/demo-4.jpg" alt="home-4">
                                                                </div>
                                                                <h6 class="demo-name fw-4">Insurance Consulting
                                                                </h6>
                                                            </a>
                                                        </div>
                                                        <div class="demo-item">
                                                            <a href="marketing-consulting.html">
                                                                <div class="demo-image">
                                                                    <img class=" ls-is-cached lazyloaded" data-src="images/demo/demo-5.jpg" src="images/demo/demo-5.jpg" alt="home-5">
                                                                </div>
                                                                <h6 class="demo-name fw-4">Marketing Consulting
                                                                </h6>
                                                            </a>
                                                        </div>
                                                        <div class="comming-soon">
                                                            <a href="coming-soon.html" class="wrap">
                                                                <h5 class="demo-name ">Coming Soon</h5>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                            <li class="has-child position-relative"><a href="#">Pages</a>
                                                <ul class="submenu">
                                                    <li class="menu-item"><a href="about.html">About</a></li>
                                                    <li class="menu-item"><a href="portfolio.html">Case Study</a>
                                                    </li>
                                                    <li class="menu-item"><a href="single-project.html">Single
                                                            Project</a></li>
                                                    <li class="menu-item"><a href="pricing.html">Pricing</a></li>
                                                    <li class="menu-item"><a href="faqs.html">FAQs</a></li>
                                                    <li class="menu-item"><a href="team.html">Team</a></li>
                                                    <li class="menu-item"><a href="career.html">Career</a></li>
                                                    <li class="menu-item"><a href="404.html">Error</a></li>
                                                    <li class="menu-item"><a href="coming-soon.html">Coming Soon</a>
                                                    </li>
                                                </ul>
                                            </li>
                                            <li class="has-child position-relative"><a href="#">Serivce</a>
                                                <ul class="submenu">
                                                    <li class="menu-item"><a href="services.html">Services</a>
                                                    </li>
                                                    <li class="menu-item"><a href="service-details.html">Services
                                                            Details</a>
                                                    </li>
                                                </ul>
                                            </li>
                                            <li class="has-child position-relative"><a href="#">Blog</a>
                                                <ul class="submenu">
                                                    <li class="menu-item"><a href="blog.html">Blog
                                                        </a>
                                                    </li>
                                                    <li class="menu-item"><a href="single-post.html">Single Post</a>
                                                    </li>

                                                </ul>
                                            </li>
                                            <li><a href="contact-us.html">Contact</a></li>
                                            <li class="has-child position-relative"><a href="#">Shop</a>
                                                <ul class="submenu">
                                                    <li class="menu-item"><a href="shop.html">Shop</a>
                                                    </li>
                                                    <li class="menu-item"><a href="product-details.html">Product
                                                            Deail</a>
                                                    </li>
                                                    <li class="menu-item"><a href="cart.html">Cart</a>
                                                    </li>
                                                    <li class="menu-item"><a href="cart.html">Checkout</a>
                                                    </li>
                                                </ul>
                                            </li>

                                        </ul>
                                    </nav>
                                </div>
                                <div class="header-right d-flex align-items-center ">
                                    <div class="popup-show-form">
                                        <a href="#" class="tf-btn btn-white btn-find btn-show " style="--button-width: 48px;">
                                            <i class="icon-search-solid"></i>
                                            <span class="bg-effect"></span>
                                        </a>
                                        <div class="popup-show popup-form-search">
                                            <div class="close-form">
                                                <i class="icon-times-solid"></i>
                                            </div>
                                            <form class="form-search style-line-bot style-1" action="#">
                                                <fieldset class="text">
                                                    <input type="text" placeholder="Search..." class="" name="text" tabindex="0" value="" aria-required="true" required="">
                                                </fieldset>
                                                <button class="" type="submit">
                                                    <i class="icon icon-search-solid"></i>
                                                </button>
                                            </form>
                                            <ul class="wrap-tag d-flex align-items-center flex-wrap gap_16">
                                                <li class="tag-item d-flex align-items-center gap_12">
                                                    <span class="sub-heading text_black">Blog</span>
                                                    <span class="remove-item">
                                                        <i class="icon-times-solid"></i>
                                                    </span>
                                                </li>
                                                <li class="tag-item d-flex align-items-center gap_12">
                                                    <span class="sub-heading text_black">Single Post</span>
                                                    <span class="remove-item">
                                                        <i class="icon-times-solid"></i>
                                                    </span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <a href="pricing.html" class="tf-btn btn-white hide-sm" style="--button-width: 120.433px;">
                                        <span>Get Started</span>
                                        <span class="bg-effect"></span>
                                    </a>
                                    <a href="contact-us.html" class="tf-btn hide-sm  " style="--button-width: 119.55px;">
                                        <span>Contact Us</span>
                                        <span class="bg-effect"></span>
                                    </a>
                                    <div class="mobile-button" data-bs-toggle="offcanvas" data-bs-target="#menu-mobile" aria-controls="menu-mobile">
                                        <div class="burger">
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header><!-- /header -->



        <!-- .page-title -->
        <div class="page-title style-5">
            <div class="tf-container mt_25">
                <div class="row">
                    <div class="col-12">
                        <div class="heading-title text-center mb_40">
                            <div class="heading-tag d-flex gap_12 align-items-center mx-auto mb_20">
                                <div class="icon">
                                    <i class="icon-medal-first-place"></i>
                                </div>
                                <p class="text-body-3 text_mono-dark-10 fw-5">Top #1 Финансовая аналитика
                                </p>
                            </div>
                            <h1 class="title text_mono-dark-9 fw-5 mb_28">
                                {!! $settings->hero_title ?? "Честный подбор финансовых услуг" !!}
                            </h1>
                            <p class="text_mono-gray-7 sub-heading">{!! $settings->hero_description ?? "Мы анализируем предложения банков и финансовых организаций." !!}</p>
                        </div>
                        
                        
                    </div>
                </div>
            </div>
           
        </div><!-- /.page-title -->


                  <!-- .-section- -->

                <div class="tf-container-2 pt_80 ">
                   
                    <div class="values-layout">
                        <div class="values-main">
                        @php
                            $mainValue = $settings->main_value_block ?? [];
                            $mainTitle = $mainValue['title'] ?? 'Commitment to intergrity <br> and ethical practices';
                            $mainDesc = $mainValue['description'] ?? "The company always puts the client's interests first, ensuring transparency and honesty in Все financial recommendations";
                            $mainUrl = $mainValue['url'] ?? null;
                            $mainIcon = $mainValue['icon'] ?? null;
                            $mainIconUrl = $mainIcon ? asset('storage/' . $mainIcon) : asset('assets/images/box-icon/line-chart.svg');
                        @endphp
                        @if($mainUrl)
                            <a href="{{ $mainUrl }}" class="tf-box-icon style-4 effect-icon" style="text-decoration:none; color:inherit; display:block;">
                        @else
                            <div class="tf-box-icon style-4 effect-icon">
                        @endif
                                <div class="heading d-flex justify-content-between gap_12">
                                    <h5 class=" text_dark fw-6">{!! nl2br(e($mainTitle)) !!}</h5>
                                    <div class="icon">
                                        <img src="{{ $mainIconUrl }}" alt="">
                                    </div>
                                </div>
                                <p class=" text-body-1 text_dark">{{ $mainDesc }}</p>
                        @if($mainUrl)
                            </a>
                        @else
                            </div>
                        @endif
                        </div>
                        <div class="values-grid">
                        @php $valuesGrid = $settings->values_grid ?? []; @endphp
                        @foreach(array_slice($valuesGrid, 0, 6) as $item)
                        @php
                            $item = is_array($item) ? (object) $item : $item;
                            $vUrl = $item->url ?? null;
                            $vIcon = $item->icon ?? null;
                            $vIconUrl = $vIcon ? asset('storage/' . $vIcon) : asset('assets/images/box-icon/handshake.svg');
                        @endphp
                        @if($vUrl)
                            <a href="{{ $vUrl }}" class="tf-box-icon style-4 effect-icon" style="text-decoration:none; color:inherit; display:block;">
                        @else
                            <div class="tf-box-icon style-4 effect-icon">
                        @endif
                                <div class="heading d-flex justify-content-between gap_12">
                                    <h5 class=" text_dark fw-6">{{ $item->title ?? '' }}</h5>
                                    <div class="icon">
                                        <img src="{{ $vIconUrl }}" alt="">
                                    </div>
                                </div>
                                <p class=" text-body-1 text_dark">{{ $item->description ?? '' }}</p>
                        @if($vUrl)
                            </a>
                        @else
                            </div>
                        @endif
                        @endforeach
                        </div>
                    </div>
                </div>
            <!-- .end-section- -->

        <div class=" main-content ">

            @php
                $partners = $settings->partners ?? [];
                $partnersTitle = $partners['title'] ?? '';
                $partnersItems = $partners['items'] ?? [];
            @endphp
            @if(!empty($partnersItems))
            <div class="wrap-logo-carousel">
                <div class="heading-section ">
                    <h6 class="fw-5 text-center">
                        {{ $partnersTitle }}
                    </h6>
                </div>
                <div class="infiniteslide tf-marquee" data-speed="50" data-clone="2" data-style="right">
                    @foreach($partnersItems as $partner)
                        @php
                            $logo = $partner['logo'] ?? '';
                            $logoUrl = $logo && !str_starts_with($logo, 'http') && !str_starts_with($logo, '/')
                                ? asset('storage/' . $logo)
                                : ($logo ?: '');
                        @endphp
                        @if($logoUrl)
                    <div class="marquee-item style-2">
                        <div class="partner style-1">
                            <img src="{{ $logoUrl }}" alt="">
                        </div>
                    </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif

            <!-- .section-service -->
            <div class="section-service style-1 pb_61">
                <div class="tf-container-2">
                    <div class="heading-section d-flex gap_12 justify-content-between  flex-wrap-md mb_59">
                        <div class="left">
                            <div class="heading-tag d-flex gap_12  mb_20 text_mono-dark-8 fw-5">
                                <div class="icon">
                                    <i class="icon-customer-service"></i>
                                </div>
                                <p class="text-body-3  fw-5">Услуги</p>
                            </div>
                            <h2 class="title text_mono-dark-9 fw-5">
                                Финансовые продукты под <span class="text-gradient">ваши цели</span>
                            </h2>
                        </div>
                        <div class="right">
                            <p class="text-body-1 text_mono-gray-7">
                                Сравнивайте предложения банков и МФО по ключевым параметрам: процентной ставке, платежам, требованиям и вероятности одобрения.
                            </p>
                        </div>
                    </div>
                    <div class="top mb_48">
                        @php $services = $settings->services ?? []; @endphp
                        <div class="navigation-bar overflow-x-auto">
                            @foreach($services as $index => $service)
                                <div class="nav-item {{ $loop->first ? 'is-active' : '' }}" data-target="#item{{ $loop->iteration }}">{{ $service['title'] ?? '' }}</div>
                            @endforeach
                        </div>

                        <div class="navigation-arrows">
                            <div class="arrow" id="prevButton"><svg width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4 11.9998L20 11.9998" stroke="#141B34" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M8.99996 17C8.99996 17 4.00001 13.3176 4 12C3.99999 10.6824 9 7 9 7"
                                        stroke="#141B34" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </div>
                            <div class="arrow" id="nextButton"><svg width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20 11.9998L4 11.9998" stroke="#141B34" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M15 17C15 17 20 13.3176 20 12C20 10.6824 15 7 15 7" stroke="#141B34"
                                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>


                    </div>
                    <div class="service-accordion">
                        <div class="wrap">
                            <div class="slider-wrap">
                                @foreach($services as $index => $service)
                                <div class="service-accordion-item" id="item{{ $loop->iteration }}">
                                    <div class="item-inner">
                                        <div class="naming-list">
                                            <span class="text_mono-gray-5">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                            <div class="title text-body-3 text_mono-dark-9">{{ $service['alt_title'] ?? $service['title'] ?? '' }}</div>
                                        </div>
                                        <div class="box-service style-3">
                                            <div class="left">
                                                <h5 class="fw-5 title">{{ $service['title'] ?? '' }}</h5>
                                                <div class="bot">
                                                    <p class="text-body-1 text_mono-gray-7 mb_29">{{ $service['description'] ?? '' }}</p>
                                                    @if(!empty($service['link']))
                                                    <a href="{{ $service['link'] }}"
                                                        class="btn_link text-body-1 text_mono-dark-9 link">
                                                        <span>Подробнее</span>
                                                        <i class="icon-long-arrow-alt-right-solid"></i>
                                                    </a>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="right">
                                                @if(!empty($service['image']))
                                                <div class="img-style">
                                                    @php $imgPath = str_starts_with($service['image'], 'http') ? $service['image'] : asset('storage/' . $service['image']); @endphp
                                                    <img class="lazyload" data-src="{{ $imgPath }}"
                                                        src="{{ $imgPath }}" alt="{{ $service['title'] ?? 'service' }}">
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

            </div> <!-- /.section-service -->


  
            <!-- .section-values -->
            <div class="section-values style-1 tf-spacing-35">
                <div class="tf-container-2">
                    <div class="heading-section text-center mb_58">
                        <div class="heading-tag d-flex  gap_12  text_mono-dark-8 fw-5 fw-5 mx-auto mb_20">
                            <div class="icon">
                                <i class="icon-bulb"></i>
                            </div>
                            <p class="text-body-3 fw-5 ">Нас выбирают</p>
                        </div>
                        <h2 class="title text_mono-dark-9 fw-5">
                            {!! $settings->advantages_block_title ?? "Почему нам доверяют выбор финансовых услуг" !!}
                        </h2>
                    </div>
                    <div class="tf-grid-layout lg-col-3 md-col-2 ">
                        @php $advantages = isset($settings) && $settings && isset($settings->advantages) ? $settings->advantages : []; @endphp
                        @forelse($advantages as $adv)
                        @php
                            $adv = is_array($adv) ? (object) $adv : $adv;
                        @endphp
                        <div class="tf-box-icon style-5 effect-icon">
                            <div class="icon mb_24">
                                @if($adv->image ?? null)
                                <img src="{{ asset('storage/' . $adv->image) }}" alt="{{ $adv->title ?? '' }}">
                                @else
                                <i class="icon-bulb"></i>
                                @endif
                            </div>
                            <div class="content">
                                <div class="text-body-2 text_mono-dark-9 mb_9 fw-5">{{ $adv->title ?? '' }}</div>
                                <p class="text-body-3 text_mono-gray-7">{{ $adv->description ?? '' }}</p>
                            </div>
                        </div>
                        @empty
                        @endforelse
                        
                    </div>
                </div>
            </div><!-- /.section-values -->


            <!-- .section-case-studie -->
            <div class="section-case-studies style-1 tf-spacing-36 pb-0">
                <div class="tf-container">
                    <div class="heading-section gap_12 text-center ">
                        <div class="heading-tag d-flex gap_12 mx-auto mb_20 text_mono-dark-8 fw-5">
                            <div class="icon">
                                <i class="icon-book-bookmark-02"></i>
                            </div>
                            <p class="text-body-3 ">Case studies</p>
                        </div>
                        <h2 class="title text_mono-dark-9 fw-5">
                            Our <span class="text-gradient">case</span> studies reveal
                        </h2>
                        <p class="text-body-1 text_mono-gray-7 mt_28  wow animate__fadeInUp animate__animated"
                            data-wow-delay="0s">Helping you streamline operations, reduce
                            costs, and
                            achieve
                            measurable <br> success with proven methodologies.</p>
                    </div>
                </div>
                <div class="wrap">
                    <div class="swiper sw-layout" data-screen-xl="4.9" data-preview="3.6" data-destop="3.5"
                        data-tablet="2.5" data-mobile="1.3" data-space-lg="48" data-space-md="20" data-space="15"
                        data-slide-center="true" data-loop="true">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <div class="case-studies-item style-3 hover-image">
                                    <div class="img-style">
                                        <img class="lazyload " data-src="{{ asset('assets/images/section/case-studies-10.jpg') }}"
                                            src="{{ asset('assets/images/section/case-studies-10.jpg') }}" alt="case-studies">
                                    </div>
                                    <h6 class="title text_white">
                                        <a href="single-project.html" class="link">Boosting Leads Through Target
                                            Campaign</a>
                                    </h6>
                                    <a href="single-project.html" class="tf-btn btn-white">
                                        <span><svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12.75 5.25009L4.5 13.5001" stroke="#121416" stroke-width="1.5"
                                                    stroke-linecap="round" />
                                                <path
                                                    d="M8.25 4.59864C8.25 4.59864 12.4752 4.24246 13.1164 4.88365C13.7575 5.52483 13.4013 9.75 13.4013 9.75"
                                                    stroke="#121416" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <span class="bg-effect"></span>
                                    </a>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="case-studies-item style-3 hover-image">
                                    <div class="img-style">
                                        <img class="lazyload " data-src="{{ asset('assets/images/section/case-studies-11.jpg') }}"
                                            src="{{ asset('assets/images/section/case-studies-11.jpg') }}" alt="case-studies">
                                    </div>
                                    <h6 class="title text_white">
                                        <a href="single-project.html" class="link">Emails Маркетинг для Prudential</a>
                                    </h6>
                                    <a href="single-project.html" class="tf-btn btn-white">
                                        <span><svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12.75 5.25009L4.5 13.5001" stroke="#121416" stroke-width="1.5"
                                                    stroke-linecap="round" />
                                                <path
                                                    d="M8.25 4.59864C8.25 4.59864 12.4752 4.24246 13.1164 4.88365C13.7575 5.52483 13.4013 9.75 13.4013 9.75"
                                                    stroke="#121416" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <span class="bg-effect"></span>
                                    </a>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="case-studies-item style-3 hover-image">
                                    <div class="img-style">
                                        <img class="lazyload " data-src="{{ asset('assets/images/section/case-studies-12.jpg') }}"
                                            src="{{ asset('assets/images/section/case-studies-12.jpg') }}" alt="case-studies">
                                    </div>
                                    <h6 class="title text_white">
                                        <a href="single-project.html" class="link">KFC Brand Promo Strategy</a>
                                    </h6>
                                    <a href="single-project.html" class="tf-btn btn-white">
                                        <span><svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12.75 5.25009L4.5 13.5001" stroke="#121416" stroke-width="1.5"
                                                    stroke-linecap="round" />
                                                <path
                                                    d="M8.25 4.59864C8.25 4.59864 12.4752 4.24246 13.1164 4.88365C13.7575 5.52483 13.4013 9.75 13.4013 9.75"
                                                    stroke="#121416" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <span class="bg-effect"></span>
                                    </a>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="case-studies-item style-3 hover-image">
                                    <div class="img-style">
                                        <img class="lazyload " data-src="{{ asset('assets/images/section/case-studies-13.jpg') }}"
                                            src="{{ asset('assets/images/section/case-studies-13.jpg') }}" alt="case-studies">
                                    </div>
                                    <h6 class="title text_white">
                                        <a href="single-project.html" class="link">Udemy Website SEO Optimized </a>
                                    </h6>
                                    <a href="single-project.html" class="tf-btn btn-white">
                                        <span><svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12.75 5.25009L4.5 13.5001" stroke="#121416" stroke-width="1.5"
                                                    stroke-linecap="round" />
                                                <path
                                                    d="M8.25 4.59864C8.25 4.59864 12.4752 4.24246 13.1164 4.88365C13.7575 5.52483 13.4013 9.75 13.4013 9.75"
                                                    stroke="#121416" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <span class="bg-effect"></span>
                                    </a>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="case-studies-item style-3 hover-image">
                                    <div class="img-style">
                                        <img class="lazyload " data-src="{{ asset('assets/images/section/case-studies-14.jpg') }}"
                                            src="{{ asset('assets/images/section/case-studies-14.jpg') }}" alt="case-studies">
                                    </div>
                                    <h6 class="title text_white">
                                        <a href="single-project.html" class="link">Digital Advertisement для Coca
                                            Cola</a>
                                    </h6>
                                    <a href="single-project.html" class="tf-btn btn-white">
                                        <span><svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12.75 5.25009L4.5 13.5001" stroke="#121416" stroke-width="1.5"
                                                    stroke-linecap="round" />
                                                <path
                                                    d="M8.25 4.59864C8.25 4.59864 12.4752 4.24246 13.1164 4.88365C13.7575 5.52483 13.4013 9.75 13.4013 9.75"
                                                    stroke="#121416" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <span class="bg-effect"></span>
                                    </a>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="case-studies-item style-3 hover-image">
                                    <div class="img-style">
                                        <img class="lazyload " data-src="{{ asset('assets/images/section/case-studies-7.jpg') }}"
                                            src="{{ asset('assets/images/section/case-studies-7.jpg') }}" alt="case-studies">
                                    </div>
                                    <h6 class="title text_white">
                                        <a href="single-project.html" class="link">Преемственность бизнеса</a>
                                    </h6>
                                    <a href="single-project.html" class="tf-btn btn-white">
                                        <span><svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12.75 5.25009L4.5 13.5001" stroke="#121416" stroke-width="1.5"
                                                    stroke-linecap="round" />
                                                <path
                                                    d="M8.25 4.59864C8.25 4.59864 12.4752 4.24246 13.1164 4.88365C13.7575 5.52483 13.4013 9.75 13.4013 9.75"
                                                    stroke="#121416" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <span class="bg-effect"></span>
                                    </a>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="case-studies-item style-3 hover-image">
                                    <div class="img-style">
                                        <img class="lazyload " data-src="{{ asset('assets/images/section/case-studies-6.jpg') }}"
                                            src="{{ asset('assets/images/section/case-studies-6.jpg') }}" alt="case-studies">
                                    </div>
                                    <h6 class="title text_white">
                                        <a href="single-project.html" class="link">Управление капиталом</a>
                                    </h6>
                                    <a href="single-project.html" class="tf-btn btn-white">
                                        <span><svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12.75 5.25009L4.5 13.5001" stroke="#121416" stroke-width="1.5"
                                                    stroke-linecap="round" />
                                                <path
                                                    d="M8.25 4.59864C8.25 4.59864 12.4752 4.24246 13.1164 4.88365C13.7575 5.52483 13.4013 9.75 13.4013 9.75"
                                                    stroke="#121416" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <span class="bg-effect"></span>
                                    </a>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="case-studies-item style-3 hover-image">
                                    <div class="img-style">
                                        <img class="lazyload " data-src="{{ asset('assets/images/section/case-studies-8.jpg') }}"
                                            src="{{ asset('assets/images/section/case-studies-8.jpg') }}" alt="case-studies">
                                    </div>
                                    <h6 class="title text_white">
                                        <a href="single-project.html" class="link">Планирование наследства</a>
                                    </h6>
                                    <a href="single-project.html" class="tf-btn btn-white">
                                        <span><svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12.75 5.25009L4.5 13.5001" stroke="#121416" stroke-width="1.5"
                                                    stroke-linecap="round" />
                                                <path
                                                    d="M8.25 4.59864C8.25 4.59864 12.4752 4.24246 13.1164 4.88365C13.7575 5.52483 13.4013 9.75 13.4013 9.75"
                                                    stroke="#121416" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <span class="bg-effect"></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="wrap-sw-button d-flex gap_16 justify-content-center">
                            <div class="sw-button style-default has-bg nav-prev-layout ">
                                <i class="icon-arrow-left-02-sharp"></i>
                            </div>
                            <div class="sw-button style-default has-bg nav-next-layout ">
                                <i class="icon-arrow-right-02-sharp"></i>
                            </div>
                        </div>
                    </div>
                    <div class="shape scroll-transform">
                        <img src="{{ asset('assets/images/item/shape-10.png') }}" alt="shape">
                    </div>
                </div>
                <div class="shape-1 ">
                    <img src="{{ asset('assets/images/item/shape-11.png') }}" alt="shape">
                </div>
            </div><!-- /.section-case-studie -->


            <!-- .section-about -->
            <div class="section-about style-1 tf-spacing-30">
                <div class="tf-container-2">
                    <div class="row">
                        <div class="col-lg-6 ">
                            <div class="left">
                                <div class="heading-section gap_12 ">
                                    <div class="heading-tag d-flex gap_12 mb_20 text_mono-dark-8 fw-5">
                                        <div class="icon">
                                            <i class="icon-star"></i>
                                        </div>
                                        <p class="text-body-3 ">О сервисе</p>
                                    </div>
                                    <h2 class="title text_mono-dark-9 fw-5">
                                        <span class="text-gradient">Master</span> your Маркетинг <br> strategies
                                        with us
                                    </h2>
                                </div>
                                <div class="content">
                                    <p class="text-body-1 mb_40">Наш финансовый маркетплейс создан для того, чтобы сделать выбор банковских и финансовых продуктов простым, понятным и честным. Мы анализируем предложения банков, микрофинансовых организаций и других финансовых компаний, чтобы показывать пользователям реальные условия, а не рекламные обещания.</p>
                                    <p class="text-body-1 mb_40">Главный принцип нашей работы — прозрачность. Мы учитываем скрытые комиссии, дополнительные платежи и маркетинговые особенности ставок, чтобы вы могли принимать финансовые решения на основе достоверной информации.</p>
                                        
                                        <p class="text-body-1 mb_40">Сервис помогает подобрать кредиты, карты, вклады и займы под индивидуальные параметры пользователя, экономя время и снижая риск отказа. Наша цель — помочь каждому найти действительно выгодное финансовое решение.</p>
                                    <a href="about.html"
                                        class="tf-btn btn-primary2  wow animate__fadeInUp animate__animated"
                                        data-wow-delay="0s">
                                        <span class="text-caption-3">О нас</span>
                                        <span class="bg-effect"></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 ">
                            <div class="right position-relative">
                                <div class="img-style">
                                    <img class="lazyload " data-src="{{ asset('assets/images/section/section-about-2.jpg') }}"
                                        src="{{ asset('assets/images/section/section-about-2.jpg') }}" alt="section-about">
                                </div>
                                <div class="highlight scroll-transform" data-distance="20%">
                                    <div class="icon mb_29">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M9.20696 20.2929C9.59748 20.6834 9.59748 21.3166 9.20696 21.7071C8.81643 22.0976 8.18327 22.0976 7.79274 21.7071L4.49985 18.4142C3.7188 17.6332 3.7188 16.3668 4.49985 15.5858L7.79274 12.2929C8.18327 11.9024 8.81643 11.9024 9.20696 12.2929C9.59748 12.6834 9.59748 13.3166 9.20696 13.7071L6.91406 16L18.9998 16C19.5521 16 19.9998 16.4477 19.9998 17C19.9998 17.5523 19.5521 18 18.9998 18L6.91406 18L9.20696 20.2929Z"
                                                fill="white" />
                                            <path
                                                d="M5 6C4.44771 6 4 6.44772 4 7C4 7.55228 4.44772 8 5 8L17.0858 8L14.7929 10.2929C14.4024 10.6834 14.4024 11.3166 14.7929 11.7071C15.1834 12.0976 15.8166 12.0976 16.2071 11.7071L19.5 8.41421C20.281 7.63316 20.281 6.36683 19.5 5.58579L16.2071 2.29289C15.8166 1.90237 15.1834 1.90237 14.7929 2.29289C14.4024 2.68342 14.4024 3.31658 14.7929 3.70711L17.0858 6L5 6Z"
                                                fill="white" />
                                        </svg>
                                    </div>
                                    <div class="content">
                                        <div class="text-body-3 fw-6 total mb_2">Итого Sales
                                        </div>
                                        <div class="counter-item  style-1 flex-column align-items-start mb_6">
                                            <div class="counter-number  ">
                                                <h2 class="odometer fw-6" data-number="30">10</h2>
                                                <span class="sub fw-6">k</span>
                                                <span class="sub fw-6">+</span>
                                            </div>
                                        </div>
                                        <p class="text-body-3 fw-6"><svg width="16" height="16" viewBox="0 0 16 16"
                                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M3 7L8 2L13 7" stroke="#83BF6E" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M8 14V2" stroke="#83BF6E" stroke-width="2"
                                                    stroke-linecap="round" />
                                            </svg>
                                            <span>25.8%</span> this week
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- /.section-about -->


            <div class="banner-stripe">
                <div class="text-container scrolling-text effect-right">
                    <span class="banner-text text-display-2 text_mono-gray-5 ">Эл. почта</span>
                    <span class="banner-text text-display-2 text_mono-gray-5 "><i class="icon-Star-2"></i></span>
                    <span class="banner-text text-display-2 text_mono-gray-5 ">Бизнес</span>
                    <span class="banner-text text-display-2 text_mono-gray-5 "><i class="icon-Star-2"></i></span>
                    <span class="banner-text text-display-2 text_mono-gray-5 ">Маркетинг</span>
                    <span class="banner-text text-display-2 text_mono-gray-5 "><i class="icon-Star-2"></i></span>
                    <span class="banner-text text-display-2 text_mono-gray-5 ">Консалтинг</span>
                    <span class="banner-text text-display-2 text_mono-gray-5 "><i class="icon-Star-2"></i></span>
                    <span class="banner-text text-display-2 text_mono-gray-5 ">Money</span>
                    <span class="banner-text text-display-2 text_mono-gray-5 "><i class="icon-Star-2"></i></span>
                    <span class="banner-text text-display-2 text_mono-gray-5 ">Эл. почта</span>
                    <span class="banner-text text-display-2 text_mono-gray-5 "><i class="icon-Star-2"></i></span>
                    <span class="banner-text text-display-2 text_mono-gray-5 ">Бизнес</span>
                    <span class="banner-text text-display-2 text_mono-gray-5 "><i class="icon-Star-2"></i></span>
                    <span class="banner-text text-display-2 text_mono-gray-5 ">Маркетинг</span>
                    <span class="banner-text text-display-2 text_mono-gray-5 "><i class="icon-Star-2"></i></span>
                    <span class="banner-text text-display-2 text_mono-gray-5 ">Консалтинг</span>
                    <span class="banner-text text-display-2 text_mono-gray-5 "><i class="icon-Star-2"></i></span>
                    <span class="banner-text text-display-2 text_mono-gray-5 ">Money</span>

                </div>
            </div>

            <!-- .section-faqs -->
            <div class="section-faqs style-2 tf-spacing-1">
                <div class="tf-container">
                    <div class="heading-section gap_12 text-center mb_64">
                        <div class="heading-tag d-flex gap_12 mx-auto mb_20 text_mono-dark-8 fw-5">
                            <div class="icon">
                                <i class="icon-message-question"></i>
                            </div>
                            <p class="text-body-3 ">The Вопросы и ответы</p>
                        </div>
                        <h2 class="title text_mono-dark-9 fw-5 split-text effect-right">
                            Everything you need to <span class="text-gradient">know</span>
                        </h2>
                        <p class="text-body-1 text_mono-gray-7 mt_28  wow animate__fadeInUp animate__animated"
                            data-wow-delay="0s">Helping you streamline operations, reduce
                            costs,
                            and
                            achieve measurable <br> success with proven methodologies.</p>
                    </div>
                    <ul class="accordion-wrap style-faqs d-grid gap_23" id="accordion-faq-4">
                        <li class="accordion-item action_click style-default v4 scrolling-effect effectRight">
                            <a href="#accordion-4-1" class="action accordion-title collapsed current"
                                data-bs-toggle="collapse" aria-expanded="true" aria-controls="accordion-4-1">
                                <div class="heading">
                                    <div class="text_mono-dark-9 text-body-1 title fw-5">Do you provide
                                        personalized
                                        financial plans?</div>
                                </div>
                                <div class="icon"></div>
                            </a>
                            <div id="accordion-4-1" class="collapse " data-bs-parent="#accordion-faq-4">
                                <div class="accordion-faqs-content">
                                    <p class="text_mono-dark-9 text-body-2">Advitex предлагает широкий спектр услуг финансового консалтинга, включая персональное финансовое планирование, корпоративную стратегию, инвестиционный консалтинг и управление рисками. Мы адаптируем решения, чтобы помогать частным клиентам, компаниям и инвесторам эффективно достигать финансовых целей. </p>
                                </div>
                            </div>
                        </li>
                        <li class="accordion-item action_click active style-default v4 scrolling-effect effectRight">
                            <a href="#accordion-4-2" class="action accordion-title current" data-bs-toggle="collapse"
                                aria-expanded="true" aria-controls="accordion-4-2">
                                <div class="heading">
                                    <div class="text_mono-dark-9 text-body-1 title fw-5">How can financial
                                        Консалтинг
                                        benefit my Бизнес?
                                    </div>
                                </div>
                                <div class="icon"></div>
                            </a>
                            <div id="accordion-4-2" class="collapse show" data-bs-parent="#accordion-faq-4">
                                <div class="accordion-faqs-content">
                                    <p class="text_mono-dark-9 text-body-2">Advitex offers a range of financial
                                        Консалтинг УСЛУГИ, including personal financial planning, Корпоративный
                                        strategy, investment advisory, and risk compliance. We tailor our
                                        solutions
                                        to help individuals, Бизнесes, and investors achieve their financial
                                        goals
                                        efficiently.</p>
                                </div>
                            </div>
                        </li>
                        <li class="accordion-item action_click style-default v4 scrolling-effect effectRight">
                            <a href="#accordion-4-3" class="action accordion-title collapsed current"
                                data-bs-toggle="collapse" aria-expanded="true" aria-controls="accordion-4-3">
                                <div class="heading">
                                    <div class="text_mono-dark-9 text-body-1 title fw-5">What УСЛУГИ does
                                        Advitex
                                        offer?</div>
                                </div>
                                <div class="icon"></div>
                            </a>
                            <div id="accordion-4-3" class="collapse " data-bs-parent="#accordion-faq-4">
                                <div class="accordion-faqs-content">
                                    <p class="text_mono-dark-9 text-body-2">Advitex предлагает широкий спектр услуг финансового консалтинга, включая персональное финансовое планирование, корпоративную стратегию, инвестиционный консалтинг и управление рисками. Мы адаптируем решения, чтобы помогать частным клиентам, компаниям и инвесторам эффективно достигать финансовых целей. </p>
                                </div>
                            </div>
                        </li>
                        <li class="accordion-item action_click style-default v4 scrolling-effect effectRight">
                            <a href="#accordion-4-4" class="action accordion-title collapsed current"
                                data-bs-toggle="collapse" aria-expanded="true" aria-controls="accordion-4-4">
                                <div class="heading">
                                    <div class="text_mono-dark-9 text-body-1 title fw-5">What industries do you
                                        specialize in?
                                    </div>
                                </div>
                                <div class="icon"></div>
                            </a>
                            <div id="accordion-4-4" class="collapse " data-bs-parent="#accordion-faq-4">
                                <div class="accordion-faqs-content">
                                    <p class="text_mono-dark-9 text-body-2">Advitex предлагает широкий спектр услуг финансового консалтинга, включая персональное финансовое планирование, корпоративную стратегию, инвестиционный консалтинг и управление рисками. Мы адаптируем решения, чтобы помогать частным клиентам, компаниям и инвесторам эффективно достигать финансовых целей. </p>
                                </div>
                            </div>
                        </li>
                        <li class="accordion-item action_click style-default v4 scrolling-effect effectRight">
                            <a href="#accordion-4-5" class="action accordion-title collapsed current"
                                data-bs-toggle="collapse" aria-expanded="true" aria-controls="accordion-4-5">
                                <div class="heading">
                                    <div class="text_mono-dark-9 text-body-1 title fw-5">How long does the
                                        consultation
                                        process take?</div>
                                </div>
                                <div class="icon"></div>
                            </a>
                            <div id="accordion-4-5" class="collapse " data-bs-parent="#accordion-faq-4">
                                <div class="accordion-faqs-content">
                                    <p class="text_mono-dark-9 text-body-2">Advitex предлагает широкий спектр услуг финансового консалтинга, включая персональное финансовое планирование, корпоративную стратегию, инвестиционный консалтинг и управление рисками. Мы адаптируем решения, чтобы помогать частным клиентам, компаниям и инвесторам эффективно достигать финансовых целей. </p>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <div class="bot text-center">
                        <div class="text-body-1 text_mono-gray-7">If you can't find your answer, please <a
                                href="contact-us.html" class="link text_black fw-5 text-decoration-underline">
                                Свяжитесь с нами</a></div>
                        <a href="faqs.html" class="tf-btn btn-primary2 height-2 mx-auto">
                            <span>Все вопросы</span>
                            <span class="bg-effect"></span>
                        </a>
                    </div>
                </div>
            </div><!-- .section-faqs -->


 
            <!-- .section-blog -->
            <div class="section">
                <div class="tf-container">
                    <div class="heading-section d-flex gap_12 justify-content-between align-items-end flex-wrap-md mb_56">
                        <div class="left">
                            <div class="point text-body-1 mb_7">
                                <span class="item"></span>
                                Latest Articles
                            </div>
                            <h2 class="heading-title text_primary split-text effect-right">Get The Latest Updates</h2>
                            <p class="text_mono-gray-7 text-body-1 mt_20 wow animate__fadeInUp animate__animated" data-wow-delay="0s">We provide timely updates on industry trends,
                                regulatory changes, and <br> best practices to help you make informed decisions.</p>
                        </div>
                        <div class="right">
                            <a href="blog.html" class="tf-btn height-3 rounded-12 btn-px-28">
                                <span>View Details</span>
                                <span class="bg-effect"></span>
                            </a>
                        </div>
                    </div>
                    <div class="swiper style-pagination sw-layout" data-preview="4" data-tablet="3" data-mobile-sm="2" data-mobile="1" data-space-lg="12" data-space-md="12" data-space="12">
                        <div class="swiper-wrapper ">
                            <div class="swiper-slide">
                                <div class="blog-article-item style-4 ">
                                    <a href="single-post.html" class="article-thumb mb_11 ">
                                        <img class="lazyload " data-src="images/blog/blog-16.jpg" src="images/blog/blog-16.jpg" alt="blog">
                                        <img class="lazyload " data-src="images/blog/blog-16.jpg" src="images/blog/blog-16.jpg" alt="blog">
                                    </a>
                                    <div class="article-content">
                                        <ul class="blog-article-meta mb_8 d-flex align-items-center style-2">
                                            <li class="meta-item text-body-2">
                                                October 8, 2024
                                            </li>
                                            <li class="meta-item date text-body-2">
                                                2 Comments
                                            </li>
                                        </ul>
                                        <h6 class="title fw-5 letter-spacing-2 text_mono-dark-9 line-clamp-2"> <a href="single-post.html" class="link ">Mastering
                                                Financial
                                                Planning: Steps to Secure Your Long-Term Wealth</a>
                                        </h6>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="blog-article-item style-4 ">
                                    <a href="single-post.html" class="article-thumb mb_11 ">
                                        <img class="lazyload " data-src="images/blog/blog-17.jpg" src="images/blog/blog-17.jpg" alt="blog">
                                        <img class="lazyload " data-src="images/blog/blog-17.jpg" src="images/blog/blog-17.jpg" alt="blog">
                                    </a>
                                    <div class="article-content">
                                        <ul class="blog-article-meta mb_8 d-flex align-items-center style-2">
                                            <li class="meta-item text-body-2">
                                                October 8, 2024
                                            </li>
                                            <li class="meta-item date text-body-2">
                                                2 Comments
                                            </li>
                                        </ul>
                                        <h6 class="title fw-5 letter-spacing-2 text_mono-dark-9 line-clamp-2"> <a href="single-post.html" class="link ">Mastering
                                                Financial
                                                Planning: Steps to Secure Your Long-Term Wealth</a>
                                        </h6>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="blog-article-item style-4 ">
                                    <a href="single-post.html" class="article-thumb mb_11 ">
                                        <img class="lazyload " data-src="images/blog/blog-18.jpg" src="images/blog/blog-18.jpg" alt="blog">
                                        <img class="lazyload " data-src="images/blog/blog-18.jpg" src="images/blog/blog-18.jpg" alt="blog">
                                    </a>
                                    <div class="article-content">
                                        <ul class="blog-article-meta mb_8 d-flex align-items-center style-2">
                                            <li class="meta-item text-body-2">
                                                October 8, 2024
                                            </li>
                                            <li class="meta-item date text-body-2">
                                                2 Comments
                                            </li>
                                        </ul>
                                        <h6 class="title fw-5 letter-spacing-2 text_mono-dark-9 line-clamp-2"> <a href="single-post.html" class="link ">Mastering
                                                Financial
                                                Planning: Steps to Secure Your Long-Term Wealth</a>
                                        </h6>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="blog-article-item style-4 ">
                                    <a href="single-post.html" class="article-thumb mb_11 ">
                                        <img class="lazyload " data-src="images/blog/blog-19.jpg" src="images/blog/blog-19.jpg" alt="blog">
                                        <img class="lazyload " data-src="images/blog/blog-19.jpg" src="images/blog/blog-19.jpg" alt="blog">
                                    </a>
                                    <div class="article-content">
                                        <ul class="blog-article-meta mb_8 d-flex align-items-center style-2">
                                            <li class="meta-item text-body-2">
                                                October 8, 2024
                                            </li>
                                            <li class="meta-item date text-body-2">
                                                2 Comments
                                            </li>
                                        </ul>
                                        <h6 class="title fw-5 letter-spacing-2 text_mono-dark-9 line-clamp-2"> <a href="single-post.html" class="link ">Mastering
                                                Financial
                                                Planning: Steps to Secure Your Long-Term Wealth</a>
                                        </h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="sw-pagination sw-pagination-layout text-center  mt_20">
                        </div>
                    </div>
                </div>
            </div><!-- /.section-blog -->
            

            <footer id="footer" class="footer style-default">
            <div class="footer-wrap">
                <div class="tf-container">
                    <div class="footer-body">
                        <div class="row">
                            <div class="col-lg-4 ">
                                <div class="footer-about">
                                    <a href="index.html" class="footer-logo ">
                                        <img src="{{ asset('assets/images/logo/favicon.svg') }}" alt="logo">
                                    </a>
                                    <div class="footer-info mb_51">
                                        <a href="mailto:themesflat@gmail.com" class="link text-body-2 text_black">themesflat@gmail.com</a>
                                        <div class="text-body-2">152 Thatcher Road St, Manhattan, NY 10463, <br>
                                            США</div>
                                        <div class="text-body-2">(+068) 568 9696</div>
                                    </div>
                                    <div class="tf-social">
                                        <a href="#" class="icon-twitter-x"></a>
                                        <a href="#" class="icon-facebook-f"></a>
                                        <a href="#" class="icon-github"></a>
                                        <a href="#" class="icon-instagram"></a>
                                        <a href="#" class="icon-youtube"></a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <div class="footer-col-block">
                                    <h6 class="footer-heading  footer-heading-mobile">
                                        Компания
                                    </h6>
                                    <div class="tf-collapse-content">
                                        <ul class="footer-menu-list">
                                            <li class="text-body-2 text_mono-gray-6">
                                                <a href="about.html" class=" link footer-menu_item">О компании
                                                    Advitex</a>
                                            </li>
                                            <li class="text-body-2 text_mono-gray-6">
                                                <a href="contact-us.html" class=" link footer-menu_item">Свяжитесь с нами</a>
                                            </li>
                                            <li class="text-body-2 text_mono-gray-6">
                                                <a href="portfolio.html" class=" link footer-menu_item">Портфолио</a>
                                            </li>
                                            <li class="text-body-2 text_mono-gray-6">
                                                <a href="faqs.html" class=" link footer-menu_item">Как мы работаем</a>
                                            </li>
                                            <li class="text-body-2 text_mono-gray-6">
                                                <a href="career.html" class=" link footer-menu_item">Карьера</a>
                                            </li>
                                            <li class="text-body-2 text_mono-gray-6">
                                                <a href="team.html" class=" link footer-menu_item">Наша команда</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <div>
                                    <div class="footer-col-block">
                                        <h6 class="footer-heading  footer-heading-mobile">
                                            Ссылки
                                        </h6>
                                        <div class="tf-collapse-content">
                                            <ul class="footer-menu-list">
                                                <li class="text-body-2 text_mono-gray-6">
                                                    <a href="contact-us.html" class=" link footer-menu_item">Центр поддержки</a>
                                                </li>
                                                <li class="text-body-2 text_mono-gray-6">
                                                    <a href="pricing.html" class=" link footer-menu_item">Политика конфиденциальности</a>
                                                </li>
                                                <li class="text-body-2 text_mono-gray-6">
                                                    <a href="#" class=" link footer-menu_item">Условия использования</a>
                                                </li>
                                                <li class="text-body-2 text_mono-gray-6">
                                                    <a href="faqs.html" class=" link footer-menu_item">Вопросы и ответы</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class=" footer-newsletter">
                                    <h6 class="footer-heading   ">
                                        Подпишитесь на нашу рассылку
                                    </h6>
                                    <div class="tf-collapse-content">
                                        <div class="wrap-newsletter">
                                            <p class="text-body-2 text_mono-gray-6 mb_29">Подпишитесь, чтобы получать новости, акции и полезные материалы!
                                            </p><form id="subscribe-form" action="#" class="form-newsletter style-1 subscribe-form mb_10" method="post" accept-charset="utf-8" data-mailchimp="true">
                                                <div id="subscribe-content" class="subscribe-content">
                                                    <fieldset class="email">
                                                        <input id="subscribe-email" type="email" name="email-form" class="subscribe-email style-2" placeholder="Введите ваш email" tabindex="0" aria-required="true">
                                                    </fieldset>
                                                    <div class="button-submit">
                                                        <button id="subscribe-button" class="subscribe-button tf-btn rounded-12 btn-primary2 " type="button" style="--button-width: 142.433px;">
                                                            <span>Подписаться</span>
                                                            <span class="bg-effect"></span>
                                                        </button>
                                                    </div>
                                                    <div class="icon">
                                                        <i class="icon-envelope-solid"></i>
                                                    </div>
                                                </div>
                                                <div id="subscribe-msg" class="subscribe-msg"></div>
                                            </form>
                                            <p class="description text-body-2">Подписываясь, вы принимаете нашу
                                                <a href="#" class="link-black text_primary ">Политика конфиденциальности</a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="tf-container">
                    <div class="row">
                        <div class="col-12">
                            <div class="wrapper d-flex align-items-center flex-wrap gap_12 ">
                                <p class="text-body-2">© 2025. Все права принадлежат <a href="#" class="link-black text_primary text-body-3">Themesflat</a>.
                                    Все права защищены.</p>
                                <ul class="right d-flex align-items-center">
                                    <li><a href="index.html" class="link text_mono-gray-5 text-body-1">Главная</a></li>
                                    <li><a href="about.html" class="link text_mono-gray-5 text-body-1">О компании</a>
                                    </li>
                                    <li><a href="services.html" class="link text_mono-gray-5 text-body-1">Услуги</a>
                                    </li>
                                    <li><a href="blog.html" class="link text_mono-gray-5 text-body-1">Блог</a></li>
                                    <li><a href="contact-us.html" class="link text_mono-gray-5 text-body-1">Контакты</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </footer>

    </div> <!-- /wrapper -->

    <!-- .prograss -->
    <div class="scrollTop effect-icon">
        <div class="icon">
            <i class="icon-long-arrow-alt-up-solid"></i>
        </div>
        <div class="liquid">
            <svg viewBox="0 0 560 20" class="liquid_wave liquid_wave_back">
                <use xlink:href="#wave"></use>
            </svg>
            <svg viewBox="0 0 560 20" class="liquid_wave liquid_wave_front">
                <use xlink:href="#liquid"></use>
            </svg>
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                viewBox="0 0 560 20" style="display: none;">
                <symbol id="liquid">
                    <path
                        d="M420,20c21.5-0.4,38.8-2.5,51.1-4.5c13.4-2.2,26.5-5.2,27.3-5.4C514,6.5,518,4.7,528.5,2.7c7.1-1.3,17.9-2.8,31.5-2.7c0,0,0,0,0,0v20H420z"
                        fill="#"
                        style="transition: stroke-dashoffset 10ms linear; stroke-dasharray: 301.839, 301.839; stroke-dashoffset: 251.895px;">
                    </path>
                    <path
                        d="M420,20c-21.5-0.4-38.8-2.5-51.1-4.5c-13.4-2.2-26.5-5.2-27.3-5.4C326,6.5,322,4.7,311.5,2.7C304.3,1.4,293.6-0.1,280,0c0,0,0,0,0,0v20H420z"
                        fill="#"></path>
                    <path
                        d="M140,20c21.5-0.4,38.8-2.5,51.1-4.5c13.4-2.2,26.5-5.2,27.3-5.4C234,6.5,238,4.7,248.5,2.7c7.1-1.3,17.9-2.8,31.5-2.7c0,0,0,0,0,0v20H140z"
                        fill="#"></path>
                    <path
                        d="M140,20c-21.5-0.4-38.8-2.5-51.1-4.5c-13.4-2.2-26.5-5.2-27.3-5.4C46,6.5,42,4.7,31.5,2.7C24.3,1.4,13.6-0.1,0,0c0,0,0,0,0,0l0,20H140z"
                        fill="#"></path>
                </symbol>
            </svg>

        </div>
    </div>
    <!-- /.prograss -->

    <!-- Start Mobile Menu -->
    <div class="offcanvas offcanvas-start canvas-mb" id="menu-mobile">
        <div class="offcanvas-header top-nav-mobile justify-content-between">
            <a href="index.html" class="logo">
                <svg width="140" height="40" viewBox="0 0 140 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M18.2183 15.7033L29.395 34.7289C29.4936 34.8967 29.6741 34.9994 29.8687 34.9985L41.1397 34.9463C41.3308 34.9455 41.5075 34.8448 41.6058 34.6809L47.5 24.8527L35.944 24.8365C35.7518 24.8362 35.5738 24.7349 35.4754 24.5698L23.9713 5.2667C23.8727 5.10131 23.6944 5 23.5019 5L12.3005 5C12.1083 5 11.9302 5.10099 11.8315 5.26594L5.91087 15.1639L0.167756 24.7208C0.0644005 24.8928 0.0636223 25.1076 0.165723 25.2804L5.75222 34.7316C5.85055 34.8979 6.02941 35 6.22264 35L16.7708 35C17.1948 35 17.4573 34.538 17.2402 34.1738L5.91087 15.1639L17.276 15.1639C17.6635 15.1639 18.022 15.3691 18.2183 15.7033Z"
                        fill="url(#paint0_linear_4689_6079)" />
                    <path
                        d="M58.22 28.5L64.412 11.7H66.548L72.716 28.5H70.58L65.468 14.22L60.332 28.5H58.22ZM61.052 24.18L61.628 22.548H69.236L69.812 24.18H61.052ZM79.5733 28.788C78.4053 28.788 77.3733 28.516 76.4773 27.972C75.5973 27.412 74.9173 26.652 74.4373 25.692C73.9573 24.732 73.7173 23.644 73.7173 22.428C73.7173 21.212 73.9573 20.132 74.4373 19.188C74.9173 18.228 75.5973 17.476 76.4773 16.932C77.3733 16.388 78.4133 16.116 79.5973 16.116C80.5733 16.116 81.4293 16.316 82.1653 16.716C82.9173 17.116 83.5013 17.676 83.9173 18.396V11.22H85.9333V28.5H84.1093L83.9173 26.508C83.6613 26.908 83.3333 27.284 82.9333 27.636C82.5333 27.972 82.0533 28.252 81.4933 28.476C80.9493 28.684 80.3093 28.788 79.5733 28.788ZM79.7893 27.036C80.5893 27.036 81.2933 26.844 81.9013 26.46C82.5093 26.076 82.9813 25.548 83.3173 24.876C83.6693 24.188 83.8453 23.38 83.8453 22.452C83.8453 21.54 83.6693 20.74 83.3173 20.052C82.9813 19.364 82.5093 18.828 81.9013 18.444C81.2933 18.06 80.5893 17.868 79.7893 17.868C79.0213 17.868 78.3253 18.06 77.7013 18.444C77.0933 18.828 76.6213 19.364 76.2853 20.052C75.9493 20.74 75.7813 21.54 75.7813 22.452C75.7813 23.38 75.9493 24.188 76.2853 24.876C76.6213 25.548 77.0933 26.076 77.7013 26.46C78.3253 26.844 79.0213 27.036 79.7893 27.036ZM92.5081 28.5L87.9241 16.404H90.0361L93.6841 26.556L97.3561 16.404H99.4201L94.8361 28.5H92.5081ZM101.636 28.5V16.404H103.652V28.5H101.636ZM102.644 13.836C102.244 13.836 101.908 13.708 101.636 13.452C101.38 13.18 101.252 12.844 101.252 12.444C101.252 12.044 101.38 11.724 101.636 11.484C101.908 11.228 102.244 11.1 102.644 11.1C103.028 11.1 103.356 11.228 103.628 11.484C103.9 11.724 104.036 12.044 104.036 12.444C104.036 12.844 103.9 13.18 103.628 13.452C103.356 13.708 103.028 13.836 102.644 13.836ZM111.722 28.5C111.002 28.5 110.378 28.388 109.85 28.164C109.322 27.94 108.914 27.564 108.626 27.036C108.354 26.492 108.218 25.764 108.218 24.852V18.108H106.106V16.404H108.218L108.482 13.476H110.234V16.404H113.738V18.108H110.234V24.852C110.234 25.604 110.386 26.116 110.69 26.388C110.994 26.644 111.53 26.772 112.298 26.772H113.594V28.5H111.722ZM121.104 28.788C119.984 28.788 118.984 28.524 118.104 27.996C117.224 27.468 116.536 26.732 116.04 25.788C115.544 24.828 115.296 23.716 115.296 22.452C115.296 21.172 115.536 20.06 116.016 19.116C116.512 18.172 117.2 17.436 118.08 16.908C118.976 16.38 120 16.116 121.152 16.116C122.336 16.116 123.344 16.38 124.176 16.908C125.008 17.436 125.64 18.132 126.072 18.996C126.52 19.844 126.744 20.78 126.744 21.804C126.744 21.964 126.744 22.132 126.744 22.308C126.744 22.484 126.736 22.684 126.72 22.908H116.808V21.348H124.752C124.704 20.244 124.336 19.38 123.648 18.756C122.976 18.132 122.128 17.82 121.104 17.82C120.432 17.82 119.8 17.98 119.208 18.3C118.616 18.604 118.144 19.06 117.792 19.668C117.44 20.26 117.264 21.004 117.264 21.9V22.572C117.264 23.564 117.44 24.396 117.792 25.068C118.16 25.74 118.632 26.244 119.208 26.58C119.8 26.916 120.432 27.084 121.104 27.084C121.952 27.084 122.648 26.9 123.192 26.532C123.752 26.148 124.16 25.628 124.416 24.972H126.408C126.2 25.708 125.856 26.364 125.376 26.94C124.896 27.5 124.296 27.948 123.576 28.284C122.872 28.62 122.048 28.788 121.104 28.788ZM127.438 28.5L131.542 22.452L127.438 16.404H129.646L132.91 21.348L136.222 16.404H138.382L134.278 22.452L138.382 28.5H136.222L132.91 23.532L129.646 28.5H127.438Z"
                        fill="#121416" />
                    <defs>
                        <linearGradient id="paint0_linear_4689_6079" x1="-7.99999" y1="20" x2="38.4226" y2="21.8957"
                            gradientUnits="userSpaceOnUse">
                            <stop stop-color="#FF3A2D" />
                            <stop offset="1" stop-color="#FFA13F" />
                        </linearGradient>
                    </defs>
                </svg>
            </a>
            <div class="close-menu" data-bs-dismiss="offcanvas">
                <i class=" icon-times-solid"></i>
            </div>
        </div>
        <div class="mb-canvas-content">
            <div class="mb-body">
                <div class="mb-content-top">
                    <ul class="nav-ul-mb" id="wrapper-menu-navigation">
                        <li class="nav-mb-item active">
                            <a href="#dropdown-menu-home" class="collapsed mb-menu-link" data-bs-toggle="collapse"
                                aria-expanded="true" aria-controls="dropdown-menu-home">
                                <span>Главная</span>
                                <span class="btn-open-sub"></span>
                            </a>
                            <div id="dropdown-menu-home" class="collapse" data-bs-parent="#menu-mobile">
                                <ul class="sub-nav-menu">
                                    <li><a href="index.html" class="sub-nav-link ">Бизнес-консалтинг</a></li>
                                    <li><a href="finance-consulting.html" class="sub-nav-link">Финансовый консалтинг</a>
                                    </li>
                                    <li><a href="finance-advisor.html" class="sub-nav-link">Финансовый советник</a>
                                    </li>
                                    <li><a href="insurance-consulting.html" class="sub-nav-link">Страховой консалтинг</a>
                                    </li>
                                    <li><a href="marketing-consulting.html" class="sub-nav-link active">Маркетинговый консалтинг</a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-mb-item ">
                            <a href="#dropdown-menu-pages" class="collapsed mb-menu-link" data-bs-toggle="collapse"
                                aria-expanded="true" aria-controls="dropdown-menu-pages">
                                <span>Страницы</span>
                                <span class="btn-open-sub"></span>
                            </a>
                            <div id="dropdown-menu-pages" class="collapse" data-bs-parent="#menu-mobile">
                                <ul class="sub-nav-menu">
                                    <li><a href="about.html" class="sub-nav-link ">О нас</a></li>
                                    <li><a href="portfolio.html" class="sub-nav-link">Кейсы</a>
                                    </li>
                                    <li><a href="single-project.html" class="sub-nav-link">Проект</a>
                                    </li>
                                    <li><a href="pricing.html" class="sub-nav-link">Тарифы</a>
                                    </li>
                                    <li><a href="faqs.html" class="sub-nav-link">Вопросы и ответы</a>
                                    </li>
                                    <li><a href="team.html" class="sub-nav-link ">Команда</a>
                                    </li>
                                    <li><a href="career.html" class="sub-nav-link ">Карьера</a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-mb-item">
                            <a href="#dropdown-menu-УСЛУГИ" class="collapsed mb-menu-link" data-bs-toggle="collapse"
                                aria-expanded="true" aria-controls="dropdown-menu-УСЛУГИ">
                                <span>Услуги</span>
                                <span class="btn-open-sub"></span>
                            </a>
                            <div id="dropdown-menu-УСЛУГИ" class="collapse" data-bs-parent="#menu-mobile">
                                <ul class="sub-nav-menu">
                                    <li><a href="services.html" class="sub-nav-link ">Услуги</a></li>
                                    <li><a href="service-details.html" class="sub-nav-link">Детали услуги</a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-mb-item">
                            <a href="#dropdown-menu-blog" class="collapsed mb-menu-link" data-bs-toggle="collapse"
                                aria-expanded="true" aria-controls="dropdown-menu-blog">
                                <span>Блог</span>
                                <span class="btn-open-sub"></span>
                            </a>
                            <div id="dropdown-menu-blog" class="collapse" data-bs-parent="#menu-mobile">
                                <ul class="sub-nav-menu">
                                    <li><a href="blog.html" class="sub-nav-link ">Блог</a></li>
                                    <li><a href="single-post.html" class="sub-nav-link">Статья</a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-mb-item">
                            <a href="#dropdown-menu-shop" class="collapsed mb-menu-link" data-bs-toggle="collapse"
                                aria-expanded="true" aria-controls="dropdown-menu-shop">
                                <span>Магазин</span>
                                <span class="btn-open-sub"></span>
                            </a>
                            <div id="dropdown-menu-shop" class="collapse" data-bs-parent="#menu-mobile">
                                <ul class="sub-nav-menu">
                                    <li><a href="shop.html" class="sub-nav-link ">Магазин</a></li>
                                    <li><a href="product-Детали.html" class="sub-nav-link">Детали товара</a>
                                    </li>
                                    <li><a href="cart.html" class="sub-nav-link">Корзина</a>
                                    </li>
                                    <li><a href="checkout.html" class="sub-nav-link">Оформление</a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-mb-item">
                            <a href="contact-us.html" class="mb-menu-link">Контакты</a>
                        </li>
                    </ul>
                </div>
                <div class="mb-other-content ">
                    <ul class="mb-info mb_20">
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
                                <a href="mailto:themesflat@@gmail.com">
                                    <span class="fw-5 text_mono-gray-5">themesflat@@gmail.com</span>
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
                    </ul>
                    <div class="mb-wrap-btn d-flex gap_12">
                        <a href="pricing.html" class="tf-btn btn-border h36  ">
                            <span class="text-body-4 ">Начать</span>
                            <span class="bg-effect"></span>
                        </a>
                        <a href="contact-us.html" class="tf-btn btn-dark  h36 ">
                            <span class="text-body-4 ">Связаться с нами</span>
                            <span class="bg-effect"></span>
                        </a>
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
    <script src="{{ asset('assets/js/infinityslide.js') }}"></script>
    <script src="{{ asset('assets/js/wow.min.js') }}"></script>
    <script src="{{ asset('assets/js/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/carousel.js') }}"></script>
    <script src="{{ asset('assets/js/odometer.min.js') }}"></script>
    <script src="{{ asset('assets/js/counter.js') }}"></script>
    <script src="{{ asset('assets/js/gsap.min.js') }}"></script>
    <script src="{{ asset('assets/js/ScrollTrigger.min.js') }}"></script>
    <script src="{{ asset('assets/js/ScrollToPlugin.min.js') }}"></script>
    <script src="{{ asset('assets/js/Splitetext.js') }}"></script>
    <script src="{{ asset('assets/js/handleeffectgsap.js') }}"></script>
    <script src="{{ asset('assets/js/ScrollSmooth.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    <!-- /Javascript -->

</body>

</html>