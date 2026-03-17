@extends('layouts.section-index')
@include('layouts.partials.redirect-city-push')

@section('content')
    @php
        $categories = \App\Models\CardCategory::orderBy('title')->get();
        $sectionPath = 'karty';
        $currentCity = $city ?? null;
        $currentCategory = $category->slug ?? null;
        $cardsCount = isset($items) ? (method_exists($items, 'total') ? $items->total() : $items->count()) : 0;
        $cardsWord = match (true) {
            $cardsCount % 100 >= 11 && $cardsCount % 100 <= 14 => 'карт',
            $cardsCount % 10 === 1 => 'карта',
            $cardsCount % 10 >= 2 && $cardsCount % 10 <= 4 => 'карты',
            default => 'карт',
        };
        $foundWord = ($cardsCount % 100 < 11 || $cardsCount % 100 > 14) && $cardsCount % 10 === 1
            ? 'Найдена'
            : 'Найдено';
        $categoryTitle = trim((string) ($category->title ?? ''));
        $categoryLabel = $categoryTitle !== ''
            ? mb_strtolower(mb_substr($categoryTitle, 0, 1)) . mb_substr($categoryTitle, 1)
            : '';
    @endphp
    <div class="section-opportunities tf-spacing-27">
        <div class="tf-container">
            @if($categories->count())
            <div class="category-nav overflow-x-auto mb_40">
                <div class="category-item {{ !$currentCategory ? 'active' : '' }}">
                    <a href="{{ $currentCity ? url_section($sectionPath . '/' . $currentCity->slug) : url_section($sectionPath) }}">Все</a>
                </div>
                @foreach($categories as $cat)
                <div class="category-item {{ $currentCategory === $cat->slug ? 'active' : '' }}">
                    <a href="{{ $currentCity ? url_section($sectionPath . '/category/' . $cat->slug . '/' . $currentCity->slug) : url_section($sectionPath . '/category/' . $cat->slug) }}">{{ $cat->title }}</a>
                </div>
                @endforeach
            </div>
            @endif

            <div class="mb_24 text-body-2">
                {{ $foundWord }} {{ $cardsCount }} {{ $cardsWord }}{{ $categoryLabel !== '' ? ' ' . $categoryLabel : '' }}
            </div>

            <div class="d-grid gap_10" id="cards-list">
                @if(isset($items) && $items->count() > 0)
                    @include('cards.partials.list-items', ['items' => $items])
                @else
                    <p class="text-body-1 text_mono-gray-7">В этой категории пока нет карт.</p>
                @endif
            </div>
            @if(isset($items) && $items->count() > 0)
                @include('partials.load-more-button', ['paginator' => $items, 'targetId' => 'cards-list'])
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
@endsection
