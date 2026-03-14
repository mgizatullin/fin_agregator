@extends('layouts.main')
@include('layouts.partials.redirect-city-push')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $page_h1 ?? $title ?? $section->title ?? 'Кредитные карты',
    'subtitle' => $section->subtitle ?? null,
    'showCitySelect' => true,
    'citySelectBase' => isset($city) && $city ? implode('/', array_slice(request()->segments(), 0, -1)) : request()->path(),
])
@endsection

@section('content')

        <div class="main-content style-1 ">


            <!-- .section-opportunities -->
            <div class="section-opportunities tf-spacing-27">
                <div class="tf-container">

                    @if(isset($categories) && $categories->count())
                    <div class="category-nav overflow-x-auto mb_40">
                        @php
                            $sectionPath = 'karty';
                            $currentCity = $city ?? null;
                            $currentCategory = null;
                            $cardsCount = isset($cards) ? (method_exists($cards, 'total') ? $cards->total() : $cards->count()) : 0;
                            $cardsWord = match (true) {
                                $cardsCount % 100 >= 11 && $cardsCount % 100 <= 14 => 'карт',
                                $cardsCount % 10 === 1 => 'карта',
                                $cardsCount % 10 >= 2 && $cardsCount % 10 <= 4 => 'карты',
                                default => 'карт',
                            };
                            $foundWord = ($cardsCount % 100 < 11 || $cardsCount % 100 > 14) && $cardsCount % 10 === 1
                                ? 'Найдена'
                                : 'Найдено';
                        @endphp
                        <div class="category-item {{ !$currentCategory ? 'active' : '' }}">
                            <a href="{{ $currentCity ? url($sectionPath . '/' . $currentCity->slug) : url($sectionPath) }}">Все</a>
                        </div>
                        @foreach($categories as $category)
                        <div class="category-item {{ $currentCategory === $category->slug ? 'active' : '' }}">
                            <a href="{{ $currentCity ? url($sectionPath . '/category/' . $category->slug . '/' . $currentCity->slug) : url($sectionPath . '/category/' . $category->slug) }}">{{ $category->title }}</a>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    <div class="mb_24 text-body-2">
                        {{ $foundWord }} {{ $cardsCount }} {{ $cardsWord }}
                    </div>

                    <div class="d-grid gap_10" id="cards-list">
                        @if(isset($cards) && $cards->count() > 0)
                            @include('cards.partials.list-items', ['items' => $cards])
                        @else
                            <p class="text-body-1 text_mono-gray-7">Нет карт.</p>
                        @endif
                    </div>
                    @if(isset($cards) && $cards->count() > 0)
                        @include('partials.load-more-button', ['paginator' => $cards, 'targetId' => 'cards-list'])
                    @endif
                </div>
            </div><!-- /.section-opportunities -->



            <!-- .unique-benefits -->
            <div class="unique-benefits tf-spacing-37 pt-0">
                <div class="tf-container">
                    <div class="heading-section mb_88">
                        <h2>Преимущества работы с нами</h2>
                        <p class="text-body-1 text_mono-gray-7 mt_20">Мы предлагаем расширенный пакет льгот для профессионального и личного благополучия. <br>
                            Что вы получите:</p>
                    </div>
                    <div class="swiper tf-sw-mobile  bg_1" data-screen="768" data-preview="1" data-space="15">
                        <div class="swiper-wrapper tf-grid-layout-md lg-col-3 gap_25  md-col-2">
                            <div class="swiper-slide ">
                                <div class="tf-box-icon style-7 v2 effec-icon">
                                    <div class="icon mb_23">
                                        <i class="icon-competitive-compensation"></i>
                                    </div>
                                    <h5 class="mb_12 title">Конкурентная оплата</h5>
                                    <p class="text-body-1 text_mono-gray-7">Конкурентная зарплата, бонусы за результат и опционные программы.</p>
                                </div>
                            </div>
                            <div class="swiper-slide ">
                                <div class="tf-box-icon style-7 v2 effec-icon">
                                    <div class="icon mb_23">
                                        <i class="icon-comprehensive-benefits"></i>
                                    </div>
                                    <h5 class="mb_12 title">Расширенные льготы</h5>
                                    <p class="text-body-1 text_mono-gray-7">Медстраховка, пенсионные программы и гибкий график отдыха.</p>
                                </div>
                            </div>
                            <div class="swiper-slide ">
                                <div class="tf-box-icon style-7 v2 effec-icon">
                                    <div class="icon mb_23">
                                        <i class="icon-professional-development"></i>
                                    </div>
                                    <h5 class="mb_12 title">Профессиональное развитие</h5>
                                    <p class="text-body-1 text_mono-gray-7">Обучение, менторство и возможности сертификации.</p>
                                </div>
                            </div>
                            <div class="swiper-slide ">
                                <div class="tf-box-icon style-7 v2 effec-icon">
                                    <div class="icon mb_23">
                                        <i class="icon-work-life-balance"></i>
                                    </div>
                                    <h5 class="mb_12 title">Баланс работы и жизни</h5>
                                    <p class="text-body-1 text_mono-gray-7">Maintain a healthy Баланс работы и жизни with
                                        flexible work
                                        arrangements and wellness programs.</p>
                                </div>
                            </div>
                            <div class="swiper-slide ">
                                <div class="tf-box-icon style-7 v2 effec-icon">
                                    <div class="icon mb_23">
                                        <i class="icon-collaborative-culture"></i>
                                    </div>
                                    <h5 class="mb_12 title">Командная культура</h5>
                                    <p class="text-body-1 text_mono-gray-7">Работайте с сильной командой в поддерживающей среде.</p>
                                </div>
                            </div>
                            <div class="swiper-slide ">
                                <div class="tf-box-icon style-7 v2 effec-icon">
                                    <div class="icon mb_23">
                                        <i class="icon-global-opportunities"></i>
                                    </div>
                                    <h5 class="mb_12 title">Международные возможности</h5>
                                    <p class="text-body-1 text_mono-gray-7">Участвуйте в международных проектах и ускоряйте карьерный рост.</p>
                                </div>
                            </div>
                        </div>
                        <div class="sw-dots style-default sw-pagination-mb mt_20 justify-content-center d-flex d-md-none">
                        </div>
                    </div>

                    
                </div>
            </div><!-- /.unique-benefits -->
			
			<!-- /.about -->
			<div class="tf-container">
			<div class="content mb-0 text-body">
                {!! filled($page_content ?? null) ? $page_content : description_to_html($section->description ?? '') !!}
            </div>
				</div>
			<!-- /.about -->
        </div>

@endsection
