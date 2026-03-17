@extends('layouts.main')
@include('layouts.partials.redirect-city-push')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $page_h1 ?? $title ?? $section->title ?? 'Займы',
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
                $loansCount = isset($items) ? (method_exists($items, 'total') ? $items->total() : $items->count()) : 0;
                $loansWord = match (true) {
                    $loansCount % 100 >= 11 && $loansCount % 100 <= 14 => 'займов',
                    $loansCount % 10 === 1 => 'займ',
                    $loansCount % 10 >= 2 && $loansCount % 10 <= 4 => 'займа',
                    default => 'займов',
                };
                $foundWord = ($loansCount % 100 < 11 || $loansCount % 100 > 14) && $loansCount % 10 === 1
                    ? 'Найден'
                    : 'Найдено';
            @endphp

            @if(isset($categories) && $categories->count())
            <div class="category-nav overflow-x-auto mb_40">
                @php
                    $sectionPath = 'zaimy';
                    $currentCity = $city ?? null;
                    $currentCategory = null;
                @endphp
                <div class="category-item {{ !$currentCategory ? 'active' : '' }}">
                    <a href="{{ $currentCity ? url_section($sectionPath . '/' . $currentCity->slug) : url_section($sectionPath) }}">Все</a>
                </div>
                @foreach($categories as $category)
                <div class="category-item {{ $currentCategory === $category->slug ? 'active' : '' }}">
                    <a href="{{ $currentCity ? url_section($sectionPath . '/' . $category->slug . '/' . $currentCity->slug) : url_section($sectionPath . '/' . $category->slug) }}">{{ $category->title }}</a>
                </div>
                @endforeach
            </div>
            @endif

            <div class="mb_24 text-body-2">
                {{ $foundWord }} {{ $loansCount }} {{ $loansWord }}
            </div>

            <div class="loan-cards-grid" id="loans-list">
                @if(isset($items) && $items->count() > 0)
                    @include('loans.partials.list-items', ['items' => $items])
                @else
                    <p class="text-body-1 text_mono-gray-7">Нет займов.</p>
                @endif
            </div>
            @if(isset($items) && $items->count() > 0)
                @include('partials.load-more-button', ['paginator' => $items, 'targetId' => 'loans-list'])
            @endif
        </div>
    </div>

    <div class="tf-container">
        <div class="content mb-0">
            {!! filled($page_content ?? null) ? $page_content : description_to_html($section->description ?? '') !!}
        </div>
    </div>
        </div>

@endsection
