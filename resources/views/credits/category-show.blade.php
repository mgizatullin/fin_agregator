@extends('layouts.main')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $section->title ?? 'Кредиты',
    'subtitle' => $section->subtitle ?? null,
    'showCitySelect' => true,
    'citySelectBase' => 'kredity/' . $category->slug,
    'breadcrumbs' => [
        ['url' => url('/'), 'label' => 'Главная'],
        ['url' => $sectionIndexUrl ?? route('credits.index'), 'label' => $sectionIndexTitle ?? 'Кредиты'],
        ['label' => $section->title ?? ''],
    ],
])
@endsection

@section('content')

        <div class="main-content style-1 ">
    <div class="section-opportunities tf-spacing-27">
        <div class="tf-container">
            @php
                $categories = \App\Models\CreditCategory::orderBy('title')->get();
                $sectionPath = 'kredity';
                $currentCity = $city ?? null;
                $currentCategory = $category->slug ?? null;
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
                $categoryTitle = trim((string) ($category->title ?? ''));
                $categoryLabel = $categoryTitle !== ''
                    ? mb_strtolower(mb_substr($categoryTitle, 0, 1)) . mb_substr($categoryTitle, 1)
                    : '';
            @endphp

            @include('credits.partials.filter-panel', [
                'items' => $items ?? collect(),
                'summaryMode' => 'category',
                'categoryLabel' => $categoryLabel,
            ])

            @if($categories->count())
            <div class="category-nav overflow-x-auto mb_40">
                <div class="category-item {{ !$currentCategory ? 'active' : '' }}">
                    <a href="{{ $currentCity ? url($sectionPath . '/' . $currentCity->slug) : url($sectionPath) }}">Все</a>
                </div>
                @foreach($categories as $cat)
                <div class="category-item {{ $currentCategory === $cat->slug ? 'active' : '' }}">
                    <a href="{{ $currentCity ? url($sectionPath . '/' . $cat->slug . '/' . $currentCity->slug) : url($sectionPath . '/' . $cat->slug) }}">{{ $cat->title }}</a>
                </div>
                @endforeach
            </div>
            @endif

            <div class="mb_24 text-body-2" data-credits-summary>
                {{ $foundWord }} {{ $creditsCount }} {{ $creditsWord }}{{ $categoryLabel !== '' ? ' ' . $categoryLabel : '' }}
            </div>

            <div class="d-grid gap_10" id="credits-list" data-credits-list>
                @if(isset($items) && $items->count() > 0)
                    @include('credits.partials.list-items', ['items' => $items])
                @else
                    <p class="text-body-1 text_mono-gray-7">В этой категории пока нет кредитов.</p>
                @endif
                <p class="text-body-1 text_mono-gray-7" data-credits-empty style="display: none;">По заданным фильтрам кредиты не найдены.</p>
            </div>
            @if(isset($items) && $items->count() > 0)
                @include('partials.load-more-button', ['paginator' => $items, 'targetId' => 'credits-list'])
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
