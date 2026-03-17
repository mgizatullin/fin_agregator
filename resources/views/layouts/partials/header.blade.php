            <!-- header -->
            <header id="header-main" class="header style-default  ">
                <div class="header-inner">
                    <div class="tf-container">
                        <div class="row">
                            <div class="col-12">
                                <div class="header-inner-wrap">
                                    <div class="header-left d-flex align-items-center">
                                        <div class="header-logo">
                                            <a href="/" class="site-logo">
                                                @php
                                                    $headerLogoUrl = null;
                                                    if (!empty($siteSettings->logo ?? null)) {
                                                        $headerLogoUrl = str_starts_with($siteSettings->logo, 'http') ? $siteSettings->logo : asset('storage/' . $siteSettings->logo);
                                                    }
                                                @endphp
                                                @if($headerLogoUrl)
                                                    <img src="{{ $headerLogoUrl }}" alt="{{ config('app.name') }}" width="149" height="46" style="height: 46px; width: auto; display: block;">
                                                @else
                                                <svg width="149" height="46" viewbox="0 0 149 46" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <rect x="0.625" y="0.625" width="147.75" height="44.75" rx="22.375" fill="white"></rect>
                                                    <rect x="0.625" y="0.625" width="147.75" height="44.75" rx="22.375" stroke="white" stroke-width="1.25"></rect>
                                                    <path d="M32.3775 20.311L39.7515 32.7325C39.8499 32.8983 40.0285 33 40.2214 33L47.6138 33C47.8054 33 47.983 32.8997 48.0819 32.7356L52 26.2351L44.3144 26.2245C44.123 26.2242 43.9458 26.1239 43.847 25.9599L36.2013 13.2645C36.1024 13.1004 35.9248 13 35.7332 13L28.3861 13C28.1948 13 28.0174 13.1 27.9184 13.2638L23.9821 19.776L20.1708 26.052C20.0657 26.2251 20.0649 26.4421 20.1688 26.6159L23.823 32.7338C23.9217 32.8989 24.0998 33 24.2922 33L30.9792 33C31.4043 33 31.6666 32.5358 31.4473 32.1716L23.9821 19.776L31.4378 19.776C31.8235 19.776 32.1806 19.9793 32.3775 20.311Z" fill="#0075FF"></path>
                                                    <path d="M60.56 30L65.7 16H67.88L73 30H70.88L66.78 18.4L62.64 30H60.56ZM62.88 26.54L63.44 24.94H70.02L70.56 26.54H62.88ZM78.4873 30.24C77.5273 30.24 76.674 30.0133 75.9273 29.56C75.194 29.1067 74.6207 28.4867 74.2073 27.7C73.8073 26.9 73.6073 26 73.6073 25C73.6073 23.9867 73.8073 23.0867 74.2073 22.3C74.6207 21.5133 75.2007 20.9 75.9473 20.46C76.694 20.0067 77.5473 19.78 78.5073 19.78C79.294 19.78 79.9873 19.94 80.5873 20.26C81.2007 20.5667 81.674 21 82.0073 21.56V15.6H84.0073V30H82.2073L82.0073 28.44C81.794 28.7467 81.5273 29.04 81.2073 29.32C80.8873 29.5867 80.5007 29.8067 80.0473 29.98C79.594 30.1533 79.074 30.24 78.4873 30.24ZM78.8073 28.5C79.434 28.5 79.9873 28.3533 80.4673 28.06C80.9473 27.7667 81.3207 27.36 81.5873 26.84C81.854 26.3067 81.9873 25.6933 81.9873 25C81.9873 24.3067 81.854 23.7 81.5873 23.18C81.3207 22.66 80.9473 22.2533 80.4673 21.96C79.9873 21.6667 79.434 21.52 78.8073 21.52C78.194 21.52 77.6473 21.6667 77.1673 21.96C76.6873 22.2533 76.314 22.66 76.0473 23.18C75.7807 23.7 75.6473 24.3067 75.6473 25C75.6473 25.6933 75.7807 26.3067 76.0473 26.84C76.314 27.36 76.6873 27.7667 77.1673 28.06C77.6473 28.3533 78.194 28.5 78.8073 28.5ZM89.1417 30L85.3817 20.02H87.4817L90.3217 28.08L93.1817 20.02H95.2417L91.5017 30H89.1417ZM96.7648 30V20.02H98.7648V30H96.7648ZM97.7648 18.08C97.3915 18.08 97.0782 17.96 96.8248 17.72C96.5715 17.48 96.4448 17.1733 96.4448 16.8C96.4448 16.44 96.5715 16.1467 96.8248 15.92C97.0782 15.68 97.3915 15.56 97.7648 15.56C98.1382 15.56 98.4515 15.68 98.7048 15.92C98.9582 16.1467 99.0848 16.44 99.0848 16.8C99.0848 17.1733 98.9582 17.48 98.7048 17.72C98.4515 17.96 98.1382 18.08 97.7648 18.08ZM105.345 30C104.705 30 104.152 29.9 103.685 29.7C103.218 29.5 102.858 29.1667 102.605 28.7C102.352 28.2333 102.225 27.6 102.225 26.8V21.7H100.505V20.02H102.225L102.485 17.52H104.225V20.02H107.065V21.7H104.225V26.82C104.225 27.3933 104.345 27.7867 104.585 28C104.825 28.2 105.238 28.3 105.825 28.3H106.965V30H105.345ZM113.159 30.24C112.185 30.24 111.325 30.0267 110.579 29.6C109.832 29.16 109.245 28.5533 108.819 27.78C108.405 26.9933 108.199 26.08 108.199 25.04C108.199 23.9867 108.405 23.0667 108.819 22.28C109.232 21.4933 109.812 20.88 110.559 20.44C111.319 20 112.192 19.78 113.179 19.78C114.165 19.78 115.012 20 115.719 20.44C116.439 20.8667 116.985 21.44 117.359 22.16C117.745 22.88 117.939 23.6733 117.939 24.54C117.939 24.6733 117.939 24.82 117.939 24.98C117.939 25.1267 117.932 25.2933 117.919 25.48H109.659V24.06H115.959C115.919 23.2467 115.639 22.6067 115.119 22.14C114.599 21.6733 113.945 21.44 113.159 21.44C112.625 21.44 112.125 21.56 111.659 21.8C111.205 22.04 110.839 22.3933 110.559 22.86C110.292 23.3267 110.159 23.9133 110.159 24.62V25.2C110.159 25.9333 110.292 26.5533 110.559 27.06C110.839 27.5533 111.205 27.9333 111.659 28.2C112.112 28.4533 112.605 28.58 113.139 28.58C113.779 28.58 114.305 28.44 114.719 28.16C115.145 27.8667 115.459 27.48 115.659 27H117.659C117.485 27.6133 117.192 28.1667 116.779 28.66C116.365 29.14 115.852 29.5267 115.239 29.82C114.639 30.1 113.945 30.24 113.159 30.24ZM118.234 30L121.734 25L118.234 20.02H120.394L123.054 23.92L125.734 20.02H127.894L124.394 25L127.894 30H125.734L123.054 26.1L120.394 30H118.234Z" fill="black"></path>
                                                </svg>
                                                @endif
                                            </a>
                                        </div>
                                        @include('layouts.partials.main-menu')
                                    </div>
                                    <div class="header-right d-flex align-items-center ">
                                        <div class="popup-show-form">
                                            <a href="javascript:void(0)" class="tf-btn btn-white btn-find btn-show ">
                                                <i class="icon-search-solid"></i>
                                                <span class="bg-effect"></span>
                                            </a>
                                            <div class="popup-show popup-form-search">
                                                <div class="close-form">
                                                    <i class="icon-times-solid"></i>
                                                </div>
                                                <form class="form-search style-line-bot style-1" action="{{ url_section('search') }}" method="get" role="search" accept-charset="UTF-8">
                                                    <fieldset class="text">
                                                        <input type="search" placeholder="Поиск..." class="" name="q" tabindex="0" value="{{ request()->get('q', '') }}" autocomplete="off">
                                                    </fieldset>
                                                    <button type="submit" aria-label="Искать">
                                                        <i class="icon icon-search-solid"></i>
                                                    </button>
                                                </form>
                                                <ul class="wrap-tag d-flex align-items-center flex-wrap gap_16">
                                                    <li class="tag-item d-flex align-items-center gap_12">
                                                        <span class="sub-heading text_black">Блог</span>
                                                        <span class="remove-item">
                                                            <i class="icon-times-solid"></i>
                                                        </span>
                                                    </li>
                                                    <li class="tag-item d-flex align-items-center gap_12">
                                                        <span class="sub-heading text_black">Статья</span>
                                                        <span class="remove-item">
                                                            <i class="icon-times-solid"></i>
                                                        </span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        @if(!empty($currencyRates))
                                        <div class="header-rates d-flex align-items-center gap_16 hide-sm" aria-label="Курсы валют ЦБ РФ">
                                            @if(isset($currencyRates['USD']))<span class="header-rates__item">USD {{ number_format((float)$currencyRates['USD'], 4, '.', '') }}</span>@endif
                                            @if(isset($currencyRates['EUR']))<span class="header-rates__item">EUR {{ number_format((float)$currencyRates['EUR'], 4, '.', '') }}</span>@endif
                                            @if(isset($currencyRates['CNY']))<span class="header-rates__item">CNY {{ number_format((float)$currencyRates['CNY'], 4, '.', '') }}</span>@endif
                                        </div>
                                        @endif
                                        <button type="button" class="hide-sm city-select-btn" id="header-city-btn" data-section-base="{{ $headerCitySelectBase ?? '' }}">
                                            <span class="header-city-label">@isset($city){{ $city->name }}@else{{ $cityName ?? 'Вся Россия' }}@endisset</span>
                                            <span class="bg-effect"></span>
                                        </button>
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
            </header> <!-- /header -->
