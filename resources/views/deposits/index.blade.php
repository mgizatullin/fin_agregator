@extends('layouts.main')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $page_h1 ?? $title ?? $section->title ?? 'Вклады',
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
            @endphp

            @if(isset($categories) && $categories->count())
            <div class="category-nav overflow-x-auto mb_40">
                @php
                    $sectionPath = 'vklady';
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

            <div class="mb_24 text-body-2">
                {{ $foundWord }} {{ $depositsCount }} {{ $depositsWord }}
            </div>

            <div class="d-grid gap_10" id="deposits-list">
                @if(isset($items) && $items->count() > 0)
                    @include('deposits.partials.list-items', ['items' => $items])
                @else
                    <p class="text-body-1 text_mono-gray-7">Нет вкладов.</p>
                @endif
            </div>
            @if(isset($items) && $items->count() > 0)
                @include('partials.load-more-button', ['paginator' => $items, 'targetId' => 'deposits-list'])
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
