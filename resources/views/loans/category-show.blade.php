@extends('layouts.main')
@include('layouts.partials.redirect-city-push')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $section->title ?? 'Займы',
    'subtitle' => $section->subtitle ?? null,
    'showCitySelect' => true,
    'citySelectBase' => 'zaimy/' . $category->slug,
    'breadcrumbs' => [
        ['url' => url('/'), 'label' => 'Главная'],
        ['url' => $sectionIndexUrl ?? url_canonical(route('loans.index')), 'label' => $sectionIndexTitle ?? 'Займы'],
        ['label' => $section->title ?? ''],
    ],
])
@endsection

@section('content')

        <div class="main-content style-1 ">
    <div class="section-opportunities tf-spacing-27">
        <div class="tf-container">
            @php
                $categories = \App\Models\LoanCategory::orderBy('title')->get();
                $sectionPath = 'zaimy';
                $currentCity = $city ?? null;
                $currentCategory = $category->slug ?? null;
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
                $categoryTitle = trim((string) ($category->title ?? ''));
                $categoryLabel = $categoryTitle !== ''
                    ? mb_strtolower(mb_substr($categoryTitle, 0, 1)) . mb_substr($categoryTitle, 1)
                    : '';
            @endphp
            @if($categories->count())
            <div class="category-nav overflow-x-auto mb_40">
                <div class="category-item {{ !$currentCategory ? 'active' : '' }}">
                    <a href="{{ $currentCity ? url_section($sectionPath . '/' . $currentCity->slug) : url_section($sectionPath) }}">Все</a>
                </div>
                @foreach($categories as $cat)
                <div class="category-item {{ $currentCategory === $cat->slug ? 'active' : '' }}">
                    <a href="{{ $currentCity ? url_section($sectionPath . '/' . $cat->slug . '/' . $currentCity->slug) : url_section($sectionPath . '/' . $cat->slug) }}">{{ $cat->title }}</a>
                </div>
                @endforeach
            </div>
            @endif

            <div class="mb_24 text-body-2">
                {{ $foundWord }} {{ $loansCount }} {{ $loansWord }}{{ $categoryLabel !== '' ? ' ' . $categoryLabel : '' }}
            </div>

            <div class="loan-cards-grid" id="loans-list">
                @if(isset($items) && $items->count() > 0)
                    @include('loans.partials.list-items', ['items' => $items])
                @else
                    <p class="text-body-1 text_mono-gray-7">В этой категории пока нет займов.</p>
                @endif
            </div>
            @if(isset($items) && $items->count() > 0)
                @include('partials.load-more-button', ['paginator' => $items, 'targetId' => 'loans-list'])
            @endif
        </div>
    </div>

    @if(!empty($section->description))
    <div class="tf-container">
        <div class="content mb-0">
            {!! $section->description !!}
        </div>
    </div>
    @endif
        </div>

@endsection
