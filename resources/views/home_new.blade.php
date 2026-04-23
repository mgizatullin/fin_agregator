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

            @include('home.partials.currency-rates-widget')

            <!-- .section-service -->
            <div class="section-service style-1 pb_61">
                <div class="tf-container-2">
                    <div class="heading-section d-flex gap_12 justify-content-between  flex-wrap-md mb_59">
                        <div class="left">
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
            <div class="section-values style-1">
                <div class="tf-container-2">
                <div class="heading-section d-flex gap_12 justify-content-between  flex-wrap-md mb_59">
                    <div class="left">
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
            <!-- .section-about -->
            <div class="section-about style-1">
                <div class="tf-container-2">
                    <div class="row">
                        <div class="col-lg-7 ">
                            <div class="left">
                                <div class="heading-section gap_12 ">
                                    @php
                                        $aboutTitle = $settings->about_title ?? '<span class="text-gradient">Master</span> your Маркетинг <br> strategies with us';
                                    @endphp
                                    <h2 class="title text_mono-dark-9 fw-5">
                                        {!! $aboutTitle !!}
                                    </h2>
                                </div>
                                <div class="content">
                                    @php
                                        $aboutDescription = $settings->about_description ?? null;
                                    @endphp
                                    @if(filled($aboutDescription))
                                        {!! $aboutDescription !!}
                                    @else
                                        <p class="text-body-1 mb_40">Наш финансовый маркетплейс создан для того, чтобы сделать выбор банковских и финансовых продуктов простым, понятным и честным. Мы анализируем предложения банков, микрофинансовых организаций и других финансовых компаний, чтобы показывать пользователям реальные условия, а не рекламные обещания.</p>
                                        <p class="text-body-1 mb_40">Главный принцип нашей работы — прозрачность. Мы учитываем скрытые комиссии, дополнительные платежи и маркетинговые особенности ставок, чтобы вы могли принимать финансовые решения на основе достоверной информации.</p>

                                        <p class="text-body-1 mb_40">Сервис помогает подобрать кредиты, карты, вклады и займы под индивидуальные параметры пользователя, экономя время и снижая риск отказа. Наша цель — помочь каждому найти действительно выгодное финансовое решение.</p>
                                    @endif
                                    <a href="/about/"
                                        class="tf-btn btn-primary2 btn-px-28 height-2 rounded-12 wow animate__fadeInUp animate__animated"
                                        data-wow-delay="0s">
                                        <span>Подробнее о проекте</span>
                                        <span class="bg-effect"></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5 ">
                            <div class="right position-relative">
                                <div class="img-style">
                                    @php
                                        $aboutImage = $settings->about_image ?? null;
                                        $aboutImageUrl = $aboutImage
                                            ? (str_starts_with($aboutImage, 'http') ? $aboutImage : asset('storage/' . $aboutImage))
                                            : asset('assets/images/section/section-about-2.jpg');
                                    @endphp
                                    <img class="lazyload" data-src="{{ $aboutImageUrl }}"
                                        src="{{ $aboutImageUrl }}" alt="section-about">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- /.section-about -->

            <!-- .section-blog -->
            <div class="section">
                    @php
                        $blogBlockTitle = $settings->blog_block_title ?? 'Get The Latest Updates';
                        $blogBlockDescription = $settings->blog_block_description ?? 'We provide timely updates on industry trends, regulatory changes, and best practices to help you make informed decisions.';
                        $blogBlockLinkText = $settings->blog_block_link_text ?? 'View Details';
                    @endphp
                    <div class="tf-container-2">
                    <div class="heading-section d-flex gap_12 justify-content-between align-items-end flex-wrap-md mb_56">
                            <div class="left">
                            <h2 class="title text_mono-dark-9 fw-5">{!! nl2br(e($blogBlockTitle)) !!}</h2>
                            <p class="text_mono-gray-7 text-body-1 mt_20 wow animate__fadeInUp animate__animated" data-wow-delay="0s">{!! nl2br(e($blogBlockDescription)) !!}</p>
                            </div>
                            <div class="right">
                            <a href="{{ url_section('blog') }}" class="tf-btn height-2 rounded-12 btn-px-28 btn-primary2">
                                <span>{{ $blogBlockLinkText }}</span>
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
                                    <a href="{{ url_section('blog/'.$post->slug) }}" class="article-thumb mb_11 ">
                                        @if($post->image)
                                        @php
                                            $previewPath = 'blog/previews/' . basename($post->image);
                                            $previewExists = str_starts_with($post->image, 'blog/') && \Illuminate\Support\Facades\Storage::disk('public')->exists($previewPath);
                                            $imgSrc = $previewExists ? 'storage/' . $previewPath : 'storage/' . $post->image;
                                        @endphp
                                        <img class="lazyload " data-src="{{ asset($imgSrc) }}" src="{{ asset($imgSrc) }}" alt="{{ $post->title }}">
                                        @else
                                        <img class="lazyload " data-src="{{ asset('assets/images/blog/blog-1.jpg') }}" src="{{ asset('assets/images/blog/blog-1.jpg') }}" alt="{{ $post->title }}">
                                        @endif
                                        </a>
                                        <div class="article-content">
                                        <ul class="blog-article-meta mb_8 d-flex align-items-center style-2">
                                            <li class="meta-item text-body-2">
                                                {{ ($post->published_at ?? $post->created_at)->locale('ru')->translatedFormat('d F Y') }}
                                                </li>
                                            <li class="meta-item date text-body-2">
                                                0 комментариев
                                                </li>
                                            </ul>
                                        <h6 class="title fw-5 letter-spacing-2 text_mono-dark-9 line-clamp-2"> <a href="{{ url_section('blog/'.$post->slug) }}" class="link ">{{ $post->title }}</a>
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


            <!-- .section-case-studie -->
            @php
                $caseServicesTitle = $settings->case_services_title ?? 'Our <span class="text-gradient">case</span> studies reveal';
                $caseServicesDescription = $settings->case_services_description ?? 'Helping you streamline operations, reduce costs, and achieve measurable success with proven methodologies.';
                $caseServicesItems = is_array($settings->case_services_items ?? null) ? $settings->case_services_items : [];
            @endphp
            @if(!empty($caseServicesItems))
            <div class="section-case-studies style-1 tf-spacing-36 pb-0">
                <div class="tf-container-2">
                    <div class="heading-section d-flex gap_12 justify-content-between  flex-wrap-md mb_59">
                    <div class="left">
                        <h2 class="title text_mono-dark-9 fw-5">
                            {!! $caseServicesTitle !!}
                        </h2>
                        </div> 
                        <div class="right">
                        <p class="text-body-1 text_mono-gray-7 mt_28  wow animate__fadeInUp animate__animated"
                            data-wow-delay="0s">{!! nl2br(e($caseServicesDescription)) !!}</p>
                   </div></div>
                </div>
                <div class="wrap">
                    <div class="swiper sw-layout" data-screen-xl="4.9" data-preview="3.6" data-destop="3.5"
                        data-tablet="2.5" data-mobile="1.3" data-space-lg="48" data-space-md="20" data-space="15"
                        data-slide-center="true" data-loop="true">
                        <div class="swiper-wrapper">
                            @foreach($caseServicesItems as $item)
                                @php
                                    $itemTitle = $item['title'] ?? '';
                                    $itemLink = $item['link'] ?? '';
                                    $itemImage = $item['image'] ?? '';
                                    $itemImageUrl = $itemImage
                                        ? (str_starts_with($itemImage, 'http') ? $itemImage : asset('storage/' . ltrim($itemImage, '/')))
                                        : asset('assets/images/section/case-studies-10.jpg');
                                @endphp
                                <div class="swiper-slide">
                                    <div class="case-studies-item style-3 hover-image">
                                        <div class="img-style">
                                            <img class="lazyload" data-src="{{ $itemImageUrl }}" src="{{ $itemImageUrl }}" alt="{{ $itemTitle ?: 'service' }}">
                                        </div>
                                        <h6 class="title text_white">
                                            @if($itemLink)
                                                <a href="{{ $itemLink }}" class="link">{{ $itemTitle }}</a>
                                            @else
                                                <span>{{ $itemTitle }}</span>
                                            @endif
                                        </h6>
                                        @if($itemLink)
                                            <a href="{{ $itemLink }}" class="tf-btn btn-white">
                                                <span><svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M12.75 5.25009L4.5 13.5001" stroke="#121416" stroke-width="1.5" stroke-linecap="round" />
                                                    <path d="M8.25 4.59864C8.25 4.59864 12.4752 4.24246 13.1164 4.88365C13.7575 5.52483 13.4013 9.75 13.4013 9.75" stroke="#121416" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg></span>
                                                <span class="bg-effect"></span>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
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
            @endif

            @php
                $faqItems = is_array($settings->faq_items ?? null) ? $settings->faq_items : [];
                $faqTitle = $settings->faq_title ?? 'Вопросы и ответы';
                $faqDescription = $settings->faq_description ?? '';
            @endphp

            <!-- .section-faqs -->
            @if(!empty($faqItems) || filled($faqTitle) || filled($faqDescription))
            <div class="section-faqs style-2">
                <div class="tf-container-2">
                    <div class="heading-section d-flex gap_12 justify-content-between flex-wrap-md mb_59">
                        <div class="left">
                            <h2 class="title text_mono-dark-9 fw-5">
                                {!! nl2br(e($faqTitle)) !!}
                            </h2>
                        </div>
                        @if(filled($faqDescription))
                        <div class="right">
                            <p class="text-body-1 text_mono-gray-7 wow animate__fadeInUp animate__animated"
                                data-wow-delay="0s">{!! nl2br(e($faqDescription)) !!}</p>
                        </div>
                        @endif
                    </div>
                    <ul class="accordion-wrap style-faqs d-grid gap_23" id="accordion-faq-4">
                        @foreach($faqItems as $index => $item)
                            @php
                                $question = $item['question'] ?? '';
                                $answer = $item['answer'] ?? '';
                                $accordionId = 'accordion-4-' . ($index + 1);
                            @endphp
                            @if(filled($question) || filled($answer))
                            <li class="accordion-item action_click {{ $loop->first ? 'active' : '' }} style-default v4 scrolling-effect effectRight">
                                <a href="#{{ $accordionId }}" class="action accordion-title {{ $loop->first ? 'current' : 'collapsed current' }}"
                                    data-bs-toggle="collapse" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="{{ $accordionId }}">
                                    <div class="heading">
                                        <div class="text_mono-dark-9 text-body-1 title fw-5">{{ $question }}</div>
                                    </div>
                                    <div class="icon"></div>
                                </a>
                                <div id="{{ $accordionId }}" class="collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#accordion-faq-4">
                                    <div class="accordion-faqs-content">
                                        <p class="text_mono-dark-9 text-body-2">{!! nl2br(e($answer)) !!}</p>
                                    </div>
                                </div>
                            </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </div><!-- .section-faqs -->
            @endif
            

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
