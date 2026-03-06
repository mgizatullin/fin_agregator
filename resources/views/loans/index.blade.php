@extends('layouts.main')

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

            @if(isset($categories) && $categories->count())
            <div class="category-nav overflow-x-auto mb_40">
                @php
                    $sectionPath = 'zaimy';
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

            <div class="loan-cards-grid">
                @if(isset($items) && $items->isNotEmpty())
                    @foreach ($items as $item)
                        <x-loan-card :item="$item" />
                    @endforeach
                @else
                    <p class="text-body-1 text_mono-gray-7">Нет займов.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="tf-container">
        <div class="content mb-0">
            {!! filled($page_content ?? null) ? $page_content : description_to_html($section->description ?? '') !!}
        </div>
    </div>
        </div>

@endsection
