@extends('layouts.main')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $section->title ?? 'Банки',
    'subtitle' => $section->subtitle ?? null,
    'showCitySelect' => true,
    'citySelectBase' => 'banki/' . $category->slug,
    'breadcrumbs' => [
        ['url' => url('/'), 'label' => 'Главная'],
        ['url' => $sectionIndexUrl ?? route('banks.index'), 'label' => $sectionIndexTitle ?? 'Банки'],
        ['label' => $section->title ?? ''],
    ],
])
@endsection

@section('content')

        <div class="main-content style-1 ">
    <div class="section-opportunities tf-spacing-27">
        <div class="tf-container">
            <div class="d-grid gap_40">
                @if(isset($items) && $items->isNotEmpty())
                    @foreach ($items as $bank)
                        @php
                            $logoUrl = $bank->logo ? (str_starts_with($bank->logo, 'http') ? $bank->logo : asset('storage/' . $bank->logo)) : null;
                        @endphp
                        <div class="karty-card">
                            <div class="karty-card__col karty-card__name">
                                <div class="karty-card__name-inner">
                                    @if($logoUrl)
                                        <img class="karty-card__image" src="{{ $logoUrl }}" alt="{{ $bank->name }}" width="101" height="66">
                                    @else
                                        <div class="karty-card__image karty-card__image-placeholder">—</div>
                                    @endif
                                    <div class="karty-card__name-block">
                                        <div class="karty-card__name-text">{{ $bank->name }}</div>
                                        <span class="karty-card__label">Банк</span>
                                    </div>
                                </div>
                            </div>
                            <div class="karty-card__col karty-card__action">
                                @if($bank->website)
                                    <a href="{{ $bank->website }}" class="tf-btn btn-primary2 btn-px-28 height-2 rounded-12" target="_blank" rel="noopener">
                                        <span>Сайт</span>
                                        <span class="bg-effect"></span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-body-1 text_mono-gray-7">В этой категории пока нет банков.</p>
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
