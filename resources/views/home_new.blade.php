@extends('layouts.main')

@push('styles')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/odometer.min.css') }}">
@endpush

@section('content')
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
                <div class="heading-section d-flex gap_12 justify-content-between  flex-wrap-md mb_59">
                    <div class="left">
            <div class="heading-tag d-flex gap_12  mb_20 text_mono-dark-8 fw-5">
                        <div class="icon">
                            <i class="icon-bulb"></i>
                        </div>
                        <p class="text-body-3 fw-5 ">Нас выбирают</p>
                    </div>
                    <h2 class="title text_mono-dark-9 fw-5">
                        {!! $settings->advantages_block_title ?? "Почему нам доверяют выбор финансовых услуг" !!}
                    </h2>
                </div>
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
                <div class="tf-container-2">
                    <div class="heading-section d-flex gap_12 justify-content-between  flex-wrap-md mb_59">
                    <div class="left">
                        <div class="heading-tag d-flex gap_12  mb_20 text_mono-dark-8 fw-5">
                            <div class="icon">
                                <i class="icon-book-bookmark-02"></i>
                            </div>
                            <p class="text-body-3 ">Case studies</p>
                        </div>
                        <h2 class="title text_mono-dark-9 fw-5">
                            Our <span class="text-gradient">case</span> studies reveal
                        </h2>
                        </div> 
                        <div class="right">
                        <p class="text-body-1 text_mono-gray-7 mt_28  wow animate__fadeInUp animate__animated"
                            data-wow-delay="0s">Helping you streamline operations, reduce
                            costs, and
                            achieve
                            measurable <br> success with proven methodologies.</p>
                   </div></div>
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
                        <h2 class="title text_mono-dark-9 fw-5">
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
                <div class="tf-container-2">
                    <div class="heading-section d-flex gap_12 justify-content-between align-items-end flex-wrap-md mb_56">
                        <div class="left">
                        <div class="heading-tag d-flex gap_12  mb_20 text_mono-dark-8 fw-5">
                        <div class="icon">
                            <i class="icon-bulb"></i>
                        </div>
                        <p class="text-body-3 fw-5 ">Последние статьи</p>
                    </div>
                            <h2 class="title text_mono-dark-9 fw-5">Get The Latest Updates</h2>
                            <p class="text_mono-gray-7 text-body-1 mt_20 wow animate__fadeInUp animate__animated" data-wow-delay="0s">We provide timely updates on industry trends,
                                regulatory changes, and <br> best practices to help you make informed decisions.</p>
                        </div>
                        <div class="right">
                            <a href="{{ url('/blog') }}" class="tf-btn height-3 rounded-12 btn-px-28">
                                <span>View Details</span>
                                <span class="bg-effect"></span>
                            </a>
                        </div>
                    </div>
                    </div>
                    <div class="tf-container">
                    <div class="swiper style-pagination sw-layout" data-preview="4" data-tablet="3" data-mobile-sm="2" data-mobile="1" data-space-lg="12" data-space-md="12" data-space="12">
                        <div class="swiper-wrapper ">
                            @foreach($posts ?? [] as $post)
                            <div class="swiper-slide">
                                <div class="blog-article-item style-4 ">
                                    <a href="{{ url('/blog/'.$post->slug) }}" class="article-thumb mb_11 ">
                                        @if($post->image)
                                        <img class="lazyload " data-src="{{ asset('storage/'.$post->image) }}" src="{{ asset('storage/'.$post->image) }}" alt="{{ $post->title }}">
                                        <img class="lazyload " data-src="{{ asset('storage/'.$post->image) }}" src="{{ asset('storage/'.$post->image) }}" alt="{{ $post->title }}">
                                        @else
                                        <img class="lazyload " data-src="{{ asset('assets/images/blog/blog-1.jpg') }}" src="{{ asset('assets/images/blog/blog-1.jpg') }}" alt="{{ $post->title }}">
                                        @endif
                                    </a>
                                    <div class="article-content">
                                        <ul class="blog-article-meta mb_8 d-flex align-items-center style-2">
                                            <li class="meta-item text-body-2">
                                                {{ ($post->published_at ?? $post->created_at)->format('d F Y') }}
                                            </li>
                                            <li class="meta-item date text-body-2">
                                                0 комментариев
                                            </li>
                                        </ul>
                                        <h6 class="title fw-5 letter-spacing-2 text_mono-dark-9 line-clamp-2"> <a href="{{ url('/blog/'.$post->slug) }}" class="link ">{{ $post->title }}</a>
                                        </h6>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="sw-pagination sw-pagination-layout text-center  mt_20">
                        </div>
                    </div>
                </div>
            </div><!-- /.section-blog -->
            

@endsection

@push('scripts')
    <script src="{{ asset('assets/js/wow.min.js') }}"></script>
    <script src="{{ asset('assets/js/odometer.min.js') }}"></script>
    <script src="{{ asset('assets/js/counter.js') }}"></script>
    <script src="{{ asset('assets/js/gsap.min.js') }}"></script>
    <script src="{{ asset('assets/js/ScrollTrigger.min.js') }}"></script>
    <script src="{{ asset('assets/js/ScrollToPlugin.min.js') }}"></script>
    <script src="{{ asset('assets/js/Splitetext.js') }}"></script>
    <script src="{{ asset('assets/js/handleeffectgsap.js') }}"></script>
@endpush
