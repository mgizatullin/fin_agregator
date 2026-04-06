@extends('layouts.app')

@push('styles')
    <style>
        @media (min-width: 992px) {
            .section-team.style-1 .tf-grid-layout-2 {
                gap: 89px 100px;
            }
        }
    </style>
@endpush

@section('page-header')
    @include('layouts.partials.page-header', [
        'title' => $siteSettings->about_project_page_title ?: ($title ?? 'О проекте'),
        'subtitle' => $siteSettings->about_project_page_subtitle ?: null,
        'breadcrumbs' => [
            ['url' => url('/'), 'label' => 'Главная'],
            ['label' => $siteSettings->about_project_page_title ?: ($title ?? 'О проекте')],
        ],
    ])
@endsection

@section('content')
    <div class="main-content style-1 ">
        @php
            $facts = is_array($siteSettings->about_project_facts ?? null) ? ($siteSettings->about_project_facts ?? []) : [];
            $teamItems = is_array($siteSettings->about_project_team_items ?? null) ? ($siteSettings->about_project_team_items ?? []) : [];
            $approachItems = is_array($siteSettings->about_project_approach_items ?? null) ? ($siteSettings->about_project_approach_items ?? []) : [];
            $reviewsItems = is_array($siteSettings->about_project_reviews_items ?? null) ? ($siteSettings->about_project_reviews_items ?? []) : [];
        @endphp

        <!-- .section-about / Описание (1) -->
        <div class="text-with-img-2 tf-spacing-4">
            <div class="tf-container">
                <div class="row ">
                    <div class="col-lg-6">
                        <div class="wrap-img">
                            <div class="shape-img-bg shape-border">
                                <img
                                    src="{{ asset('template/images/section/img-with-shape-3.jpg') }}"
                                    alt="shape"
                                    class="img-custom-anim-left wow"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 ">
                        <div class="content mb-0">
                            {!! $siteSettings->about_project_description_1 ?? '' !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- .section-about / Описание (2) -->
        <div class="text-with-img-2 tf-spacing-5 pt-0">
            <div class="tf-container">
                <div class="row ">
                    <div class="col-xl-6 ">
                        <div class="content">
                            {!! $siteSettings->about_project_description_2 ?? '' !!}
                        </div>
                    </div>

                    <div class="col-xl-6">
                        <div class="wrap-img mb-0">
                            <div class="shape-img-bg">
                                <img
                                    src="{{ asset('template/images/section/img-with-shape-4.jpg') }}"
                                    alt="shape"
                                    class="img-custom-anim-right wow"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display:none">
                    <!--
                        Оригинальный шаблон имеет сложную анимационную SVG-обвязку.
                        Для натяжки достаточно вывода контента из админки.
                    -->
                </div>

                <!-- Факты -->
                <div class="wrap-counter style-4">
                    <div class="row">
                        @foreach(array_slice($facts, 0, 3) as $fact)
                            @php
                                $text1 = (string) ($fact['text_1'] ?? '');
                                $text2 = (string) ($fact['text_2'] ?? '');
                                $text3 = (string) ($fact['text_3'] ?? '');

                                $hasPlus = str_contains($text2, '+');
                                $numberValue = preg_replace('/[^0-9.]/', '', $text2);
                            @endphp

                            <div class="col-md-4">
                                <div class="counter-item style-default">
                                    @if($text1 !== '')
                                        <div class="sub-heading text_black text-uppercase mb_22 ">{{ $text1 }}</div>
                                    @endif

                                    <div class="counter-number ">
                                        @if($numberValue !== '')
                                            <div class="text_primary mb_15">{{ $numberValue }}</div>
                                        @endif
                                        @if($hasPlus)
                                            <span class="sub text_primary">+</span>
                                        @endif
                                    </div>

                                    @if($text3 !== '')
                                        <p class="sub-heading text_mono-gray-5">
                                            {{ $text3 }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- .section-team / Команда -->
        <div class="section-team style-1 tf-spacing-6 ">
            <div class="tf-container">
                <div class="heading-secion mb_88">
                    <h2 class="heading-title split-text effect-right">
                        {{ $siteSettings->about_project_team_title ?: 'Наша команда' }}
                    </h2>
                    @if(!empty($siteSettings->about_project_team_description))
                        <p class="text-body-1 text_mono-gray-7 mt_20  wow animate__fadeInUp animate__animated" data-wow-delay="0s">
                            {{ $siteSettings->about_project_team_description }}
                        </p>
                    @endif
                </div>

                <div class="tf-grid-layout-2 lg-col-4">
                    @if(isset($specialists) && $specialists->isNotEmpty())
                        @foreach($specialists as $item)
                            @php
                                $name = (string) ($item->name ?? '');
                                $role = (string) ($item->position ?? '');
                                $photo = filled($item->photo)
                                    ? asset('storage/' . ltrim((string) $item->photo, '/'))
                                    : asset('template/images/item/team-emty.png');
                            @endphp
                            @if($name === '' && $role === '')
                                @continue
                            @endif

                            <div class="team-item style-default hover-border hover-image">
                                <a href="#" class="img-style mb_19">
                                    <img src="{{ $photo }}" alt="{{ $name !== '' ? $name : 'avatar' }}">
                                </a>
                                <div class="content">
                                    @if($name !== '')
                                        <h3 class="name ">
                                            <a href="#" class="link hover-line-text">{{ $name }}</a>
                                        </h3>
                                    @endif
                                    @if($role !== '')
                                        <p class="text-body-1">{{ $role }}</p>
                                    @endif
                                    @if(filled($item->short_description))
                                        <p class="text-body-2 text_mono-gray-7 mt_8">{{ $item->short_description }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        @foreach($teamItems as $item)
                            @php
                                $name = (string) ($item['name'] ?? '');
                                $role = (string) ($item['role'] ?? '');
                            @endphp
                            @if($name === '' && $role === '')
                                @continue
                            @endif

                            <div class="team-item style-default hover-border hover-image">
                                <a href="#" class="img-style mb_19">
                                    <img
                                        src="{{ asset('template/images/item/team-emty.png') }}"
                                        alt="avatar"
                                    >
                                </a>
                                <div class="content">
                                    @if($name !== '')
                                        <h3 class="name ">
                                            <a href="#" class="link hover-line-text">{{ $name }}</a>
                                        </h3>
                                    @endif
                                    @if($role !== '')
                                        <p class="text-body-1">{{ $role }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <!-- .section-process / Наш подход -->
        <div class="section-process style-1 tf-spacing-5 pb-0">
            <div class="tf-container">
                <div class="wrap">
                    <div class="row align-items-end">
                        <div class="col-xl-6">
                            <div class="left">
                                <div class="heading-secion mb_86">
                                    <h1 class="heading-title split-text effect-right">
                                        {{ $siteSettings->about_project_approach_title ?: 'Наш подход' }}
                                    </h1>
                                    @if(!empty($siteSettings->about_project_approach_description))
                                        <p class="text-body-1 text_mono-gray-7 mt_20 wow animate__fadeInUp animate__animated" data-wow-delay="0s">
                                            {{ $siteSettings->about_project_approach_description }}
                                        </p>
                                    @endif
                                </div>

                                @if(count($approachItems) > 0)
                                    <ul class="accordion-wrap gap-16 style-faqs" id="accordion-approach">
                                        @foreach(array_values($approachItems) as $i => $item)
                                            @php
                                                $titleItem = (string) ($item['title'] ?? '');
                                                $descItem = (string) ($item['description'] ?? '');
                                                $idx = $i + 1;
                                            @endphp

                                            <li class="accordion-item action_click scrolling-effect effectBottom style-default {{ $i === 0 ? 'active' : '' }}">
                                                <a
                                                    href="#accordion-approach-{{ $idx }}"
                                                    class="accordion-title action {{ $i === 0 ? 'current' : 'collapsed' }}"
                                                    data-bs-toggle="collapse"
                                                    aria-expanded="{{ $i === 0 ? 'true' : 'false' }}"
                                                    aria-controls="accordion-approach-{{ $idx }}"
                                                >
                                                    <div class="heading">
                                                        <h3 class="text_mono-gray-5 title">
                                                            <span class="text_primary">{{ $idx }}.</span>{{ $titleItem }}
                                                        </h3>
                                                    </div>
                                                    <span class="icon"></span>
                                                </a>

                                                <div
                                                    id="accordion-approach-{{ $idx }}"
                                                    class="collapse {{ $i === 0 ? 'show' : '' }}"
                                                    data-bs-parent="#accordion-approach"
                                                >
                                                    <div class="accordion-faqs-content">
                                                        @if($descItem !== '')
                                                            <p class="text-body-1">{{ $descItem }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>

                        <div class="col-xl-6 right">
                            <div class="shape-img-bg shape-border style-2 scroll-transform">
                                <img
                                    src="{{ asset('template/images/section/img-with-shape-5.jpg') }}"
                                    alt="img"
                                    class="wow img-custom-anim-right"
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- .section-ОТЗЫВЫ -->
        <div class="section-ОТЗЫВЫ style-1">
            <div class="tf-container">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="wrap">
                            <div class="heading-section">
                                <h2 class="heading-title split-text split-lines-rotation-x">
                                    {{ $siteSettings->about_project_reviews_title ?: 'Отзывы' }}
                                </h2>
                                @if(!empty($siteSettings->about_project_reviews_description))
                                    <p class="text-body-1 text_mono-gray-7 mt_20 wow animate__fadeInUp animate__animated" data-wow-delay="0s">
                                        {{ $siteSettings->about_project_reviews_description }}
                                    </p>
                                @endif
                            </div>
                            <div class="item scroll-transform" data-direction="left">
                                <img src="{{ asset('template/images/item/item-testimonial.png') }}" alt="item">
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="tf-grid-layout md-col-2">
                            @foreach($reviewsItems as $review)
                                @php
                                    $img = $review['image'] ?? null;
                                    if (is_array($img)) {
                                        $img = $img[0] ?? null;
                                    }
                                    $img = is_string($img) ? trim($img) : null;
                                @endphp

                                @continue($img === null || $img === '')

                                <div class="ОТЗЫВЫ style-default">
                                    {{-- Вместо testimonial: показываем только картинку --}}
                                    <img
                                        src="{{ asset('storage/' . ltrim($img, '/')) }}"
                                        alt="review"
                                        class="w-full h-auto"
                                        loading="lazy"
                                    >
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

