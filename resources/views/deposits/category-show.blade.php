@extends('layouts.main')
@include('layouts.partials.redirect-city-push')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $section->title ?? 'Вклады',
    'subtitle' => $section->subtitle ?? null,
    'showCitySelect' => true,
    'citySelectBase' => 'vklady/' . $category->slug,
    'breadcrumbs' => [
        ['url' => url('/'), 'label' => 'Главная'],
        ['url' => $sectionIndexUrl ?? url_canonical(route('deposits.index')), 'label' => $sectionIndexTitle ?? 'Вклады'],
        ['label' => $section->title ?? ''],
    ],
])
@endsection

@section('content')

        <div class="main-content style-1 ">
    <div class="section-opportunities tf-spacing-27">
        <div class="tf-container">
            @php
                $categories = \App\Models\DepositCategory::orderBy('title')->get();
                $sectionPath = 'vklady';
                $currentCity = $city ?? null;
                $currentCategory = $category->slug ?? null;
                $depositsCount = isset($items) ? (method_exists($items, 'total') ? $items->total() : $items->count()) : 0;
                $depositsWord = match (true) {
                    $depositsCount % 100 >= 11 && $depositsCount % 100 <= 14 => 'вкладов',
                    $depositsCount % 10 === 1 => 'вклад',
                    $depositsCount % 10 >= 2 && $depositsCount % 10 <= 4 => 'вклада',
                    default => 'вкладов',
                };
                $foundWord = ($depositsCount % 100 < 11 || $depositsCount % 100 > 14) && $depositsCount % 10 === 1
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
                {{ $foundWord }} {{ $depositsCount }} {{ $depositsWord }}{{ $categoryLabel !== '' ? ' ' . $categoryLabel : '' }}
            </div>

            <div class="d-grid gap_10" id="deposits-list">
                @if(isset($items) && $items->count() > 0)
                    @include('deposits.partials.list-items', ['items' => $items])
                @else
                    <p class="text-body-1 text_mono-gray-7">В этой категории пока нет вкладов.</p>
                @endif
            </div>
            @if(isset($items) && $items->count() > 0)
                @include('partials.load-more-button', ['paginator' => $items, 'targetId' => 'deposits-list'])
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
