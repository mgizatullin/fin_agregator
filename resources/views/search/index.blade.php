@extends('layouts.main')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $q !== '' ? 'Результаты поиска: ' . e($q) : 'Поиск',
    'subtitle' => $q !== '' && $total > 0 ? 'Найдено: ' . $total : ($q !== '' ? 'Ничего не найдено' : 'Введите запрос в поле поиска'),
])
@endsection

@section('content')
<div class="main-content style-1">
    <div class="tf-container tf-spacing-2">
        <form class="form-search style-line-bot style-1 mb_40" action="{{ url('/search') }}" method="get" role="search" accept-charset="UTF-8" style="max-width: 600px;">
            <fieldset class="text">
                <input type="search" placeholder="Поиск..." name="q" value="{{ e($q) }}" autocomplete="off">
            </fieldset>
            <button type="submit" aria-label="Искать">
                <i class="icon icon-search-solid"></i>
            </button>
        </form>

        @if($q !== '')
            @if($total === 0)
                <p class="text-body-1 text_mono-gray-7">По запросу «{{ e($q) }}» ничего не найдено. Попробуйте изменить запрос.</p>
            @else
                <div class="search-results">
                    @if($credits->isNotEmpty())
                        <section class="search-results__group mb_40">
                            <h2 class="search-results__title mb_20">Кредиты</h2>
                            <ul class="search-results__list">
                                @foreach($credits as $item)
                                    <li><a href="{{ url('/kredity/' . $item->slug) }}" class="link">{{ $item->name }}</a>@if($item->bank)<span class="text_mono-gray-6"> — {{ $item->bank->name }}</span>@endif</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if($deposits->isNotEmpty())
                        <section class="search-results__group mb_40">
                            <h2 class="search-results__title mb_20">Вклады</h2>
                            <ul class="search-results__list">
                                @foreach($deposits as $item)
                                    <li><a href="{{ url('/vklady/' . $item->slug) }}" class="link">{{ $item->name }}</a>@if($item->bank)<span class="text_mono-gray-6"> — {{ $item->bank->name }}</span>@endif</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if($cards->isNotEmpty())
                        <section class="search-results__group mb_40">
                            <h2 class="search-results__title mb_20">Карты</h2>
                            <ul class="search-results__list">
                                @foreach($cards as $item)
                                    <li><a href="{{ url('/karty/' . $item->slug) }}" class="link">{{ $item->name }}</a>@if($item->bank)<span class="text_mono-gray-6"> — {{ $item->bank->name }}</span>@endif</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if($loans->isNotEmpty())
                        <section class="search-results__group mb_40">
                            <h2 class="search-results__title mb_20">Займы</h2>
                            <ul class="search-results__list">
                                @foreach($loans as $item)
                                    <li><a href="{{ url('/zaimy/' . $item->slug) }}" class="link">{{ $item->name }}</a>@if($item->company_name)<span class="text_mono-gray-6"> — {{ $item->company_name }}</span>@endif</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if($banks->isNotEmpty())
                        <section class="search-results__group mb_40">
                            <h2 class="search-results__title mb_20">Банки</h2>
                            <ul class="search-results__list">
                                @foreach($banks as $item)
                                    <li><a href="{{ url('/banki/' . $item->slug) }}" class="link">{{ $item->name }}</a></li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if($articles->isNotEmpty())
                        <section class="search-results__group mb_40">
                            <h2 class="search-results__title mb_20">Блог</h2>
                            <ul class="search-results__list">
                                @foreach($articles as $item)
                                    <li><a href="{{ url('/blog/' . $item->slug) }}" class="link">{{ $item->title }}</a></li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
