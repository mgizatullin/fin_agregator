@extends('layouts.main')
@include('layouts.partials.redirect-city-push')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $page_h1 ?? $title ?? $section->title ?? 'Кредиты',
    'subtitle' => $section->subtitle ?? null,
    'showCitySelect' => true,
    'citySelectBase' => isset($city) && $city ? implode('/', array_slice(request()->segments(), 0, -1)) : request()->path(),
])
@endsection

@section('content')

        <div class="main-content style-1 ">
    <div class="section-opportunities tf-spacing-27">
        <div class="tf-container">
            @php
                $creditsCount = isset($items) ? (method_exists($items, 'total') ? $items->total() : $items->count()) : 0;
                $creditsWord = match (true) {
                    $creditsCount % 100 >= 11 && $creditsCount % 100 <= 14 => 'кредитов',
                    $creditsCount % 10 === 1 => 'кредит',
                    $creditsCount % 10 >= 2 && $creditsCount % 10 <= 4 => 'кредита',
                    default => 'кредитов',
                };
                $foundWord = ($creditsCount % 100 < 11 || $creditsCount % 100 > 14) && $creditsCount % 10 === 1
                    ? 'Найден'
                    : 'Найдено';
            @endphp

            @include('credits.partials.filter-panel', [
                'items' => $items ?? collect(),
                'summaryMode' => 'index',
            ])

            @if(isset($categories) && $categories->count())
            <div class="category-nav overflow-x-auto mb_40">
                @php
                    $sectionPath = 'kredity';
                    $currentCity = $city ?? null;
                    $currentCategory = null;
                @endphp
                <div class="category-item {{ !$currentCategory ? 'active' : '' }}">
                    <a href="{{ $currentCity ? url($sectionPath . '/' . $currentCity->slug) : url($sectionPath) }}">Все</a>
                </div>
                @foreach($categories as $category)
                <div class="category-item {{ $currentCategory === $category->slug ? 'active' : '' }}">
                    <a href="{{ $currentCity ? url($sectionPath . '/' . $category->slug . '/' . $currentCity->slug) : url($sectionPath . '/' . $category->slug) }}">{{ $category->title }}</a>
                </div>
                @endforeach
            </div>
            @endif

            <div class="mb_24 text-body-2" data-credits-summary>
                {{ $foundWord }} {{ $creditsCount }} {{ $creditsWord }}
            </div>

            <div class="d-grid gap_10" id="credits-list" data-credits-list>
                @if(isset($items) && $items->count() > 0)
                    @include('credits.partials.list-items', ['items' => $items])
                @else
                    <p class="text-body-1 text_mono-gray-7">Нет кредитов.</p>
                @endif
                <p class="text-body-1 text_mono-gray-7" data-credits-empty style="display: none;">По заданным фильтрам кредиты не найдены.</p>
            </div>
            @if(isset($items) && $items->count() > 0)
                @include('partials.load-more-button', ['paginator' => $items, 'targetId' => 'credits-list'])
            @endif
        </div>
    </div>

    <div class="tf-container">
        <div class="content mb-0 text-body">
            {!! filled($page_content ?? null) ? $page_content : description_to_html($section->description ?? '') !!}
        </div>
    </div>
        </div>

@endsection
