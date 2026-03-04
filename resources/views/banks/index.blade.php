@extends('layouts.main')

@section('content')

            <!-- .page-title -->
            <div class="page-title style-default">
                <div class="tf-container">
                    <div class="row">
                        <div class="col-12">
                            <div class="heading mb_51">
                                <h1 class="text_black mb_18 letter-spacing-1 ">{{ $section->title ?? 'Банки' }}</h1>
                                <p class="sub-heading text_mono-gray-7">{{ $section->subtitle ?? '' }}</p>
                            </div>
                            <ul class="breadcrumb">
                                <li><a href="{{ url('/') }}" class="link">Главная</a></li>
                                <li>{{ $section->title ?? 'Банки' }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div><!-- /.page-title -->

        </div>

        <div class="main-content style-1 ">
    <div class="section-opportunities tf-spacing-27">
        <div class="tf-container">
            <div class="d-grid gap_40">
                @if(isset($items) && $items->isNotEmpty())
                    @foreach ($items as $bank)
                        @php
                            $logoUrl = ($bank->logo_square ?? $bank->logo) ? asset('storage/' . ($bank->logo_square ?? $bank->logo)) : null;
                            $bankSlug = $bank->slug ? url('/banki/' . $bank->slug) : '#';
                            $branchesCount = $bank->branches_count ?? $bank->branches->count();
                            $rating = $bank->rating !== null && $bank->rating !== '' ? (float) $bank->rating : null;
                        @endphp
                        <div class="credit-card credit-card--bank">
                            <div class="credit-card__col credit-card__name">
                                <div class="credit-card__name-inner">
                                    @if($logoUrl)
                                        <img class="credit-card__logo" src="{{ $logoUrl }}" alt="{{ $bank->name }}" width="64" height="64">
                                    @else
                                        <div class="credit-card__logo credit-card__logo-placeholder">—</div>
                                    @endif
                                    <div class="credit-card__name-block">
                                        <div class="credit-card__name-text">{{ $bank->name }}</div>
                                        @if($rating !== null)
                                            <div class="credit-card__rating">
                                                ⭐ {{ number_format($rating, 1, '.', '') }}@if(filled($bank->reviews_count ?? null) && (int) $bank->reviews_count > 0) ({{ number_format((int) $bank->reviews_count, 0, '', ' ') }} отзывов)@endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="credit-card__col">
                                <span class="credit-card__label">Телефон</span>
                                <span class="credit-card__value">{{ $bank->phone ?: '—' }}</span>
                            </div>
                            <div class="credit-card__col">
                                <span class="credit-card__label">Отделения</span>
                                <span class="credit-card__value">{{ $branchesCount }}</span>
                            </div>
                            <div class="credit-card__col">
                                <span class="credit-card__label">Рег номер</span>
                                <span class="credit-card__value">{{ $bank->license_number ?: '—' }}</span>
                            </div>
                            <div class="credit-card__col credit-card__action">
                                <a href="{{ $bankSlug }}" class="tf-btn btn-primary2 btn-px-28 height-2 rounded-12">
                                    <span>Подробнее</span>
                                    <span class="bg-effect"></span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-body-1 text_mono-gray-7">Нет банков.</p>
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
