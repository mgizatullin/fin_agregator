@extends('layouts.main')

@section('page-header')
@php
    $title = $page_h1 ?? $title ?? $section->title ?? 'Вклады';
    if (request()->has('bank')) {
        $bankName = \App\Models\Bank::where('slug', request('bank'))->first()?->name;
        if ($bankName) {
            $title = 'Вклады в ' . $bankName;
        }
    }
@endphp
@include('layouts.partials.page-header', [
    'title' => $title,
    'breadcrumbs' => [
        ['url' => url('/'), 'label' => 'Главная'],
        ['label' => 'Вклады'],
    ],
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

            @if(false && isset($categories) && $categories->count())
            <div class="category-nav overflow-x-auto mb_40">
                @php
                    $sectionPath = 'vklady';
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
