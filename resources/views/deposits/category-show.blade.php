@extends('layouts.main')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $section->title ?? 'Вклады',
    'subtitle' => $section->subtitle ?? null,
    'showCitySelect' => true,
    'citySelectBase' => 'vklady/' . $category->slug,
    'breadcrumbs' => [
        ['url' => url('/'), 'label' => 'Главная'],
        ['url' => $sectionIndexUrl ?? route('deposits.index'), 'label' => $sectionIndexTitle ?? 'Вклады'],
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
            @endphp
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

            <div class="d-grid gap_40">
                @if(isset($items) && $items->isNotEmpty())
                    @foreach ($items as $item)
                        <x-deposit-card :item="$item" />
                    @endforeach
                @else
                    <p class="text-body-1 text_mono-gray-7">В этой категории пока нет вкладов.</p>
                @endif
            </div>
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
