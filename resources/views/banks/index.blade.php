@extends('layouts.main')
@include('layouts.partials.redirect-city-push')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $page_h1 ?? $title ?? $section->title ?? 'Банки',
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
                $banksCount = isset($items) ? (method_exists($items, 'total') ? $items->total() : $items->count()) : 0;
                $banksWord = match (true) {
                    $banksCount % 100 >= 11 && $banksCount % 100 <= 14 => 'банков',
                    $banksCount % 10 === 1 => 'банк',
                    $banksCount % 10 >= 2 && $banksCount % 10 <= 4 => 'банка',
                    default => 'банков',
                };
                $foundWord = ($banksCount % 100 < 11 || $banksCount % 100 > 14) && $banksCount % 10 === 1
                    ? 'Найден'
                    : 'Найдено';
            @endphp
            <div class="mb_24 text-body-2">
                {{ $foundWord }} {{ $banksCount }} {{ $banksWord }}
            </div>
            <div class="d-grid gap_10" id="banks-list">
                @if(isset($items) && $items->count() > 0)
                    @include('banks.partials.list-items', ['items' => $items, 'variant' => 'index'])
                @else
                    <p class="text-body-1 text_mono-gray-7">Нет банков.</p>
                @endif
            </div>
            @if(isset($items) && $items->count() > 0)
                @include('partials.load-more-button', ['paginator' => $items, 'targetId' => 'banks-list'])
            @endif
        </div>
    </div>

    @if(!empty($section->description) || filled($page_content ?? null))
    <div class="tf-container">
        <div class="content mb-0">
            {!! filled($page_content ?? null) ? $page_content : $section->description !!}
        </div>
    </div>
    @endif
        </div>

@endsection
