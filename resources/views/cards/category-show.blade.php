@extends('layouts.section-index')

@section('content')
    @php
        $categories = \App\Models\CardCategory::orderBy('title')->get();
        $sectionPath = 'karty';
        $currentCity = $city ?? null;
        $currentCategory = $category->slug ?? null;
    @endphp
    <div class="section-opportunities tf-spacing-27">
        <div class="tf-container">
            @if($categories->count())
            <div class="category-nav overflow-x-auto mb_40">
                <div class="category-item {{ !$currentCategory ? 'active' : '' }}">
                    <a href="{{ $currentCity ? url($sectionPath . '/' . $currentCity->slug) : url($sectionPath) }}">Все</a>
                </div>
                @foreach($categories as $cat)
                <div class="category-item {{ $currentCategory === $cat->slug ? 'active' : '' }}">
                    <a href="{{ $currentCity ? url($sectionPath . '/category/' . $cat->slug . '/' . $currentCity->slug) : url($sectionPath . '/category/' . $cat->slug) }}">{{ $cat->title }}</a>
                </div>
                @endforeach
            </div>
            @endif

            <div class="d-grid gap_40">
                @if(isset($items) && $items->isNotEmpty())
                    @foreach ($items as $card)
                        @php
                            $bankName = $card->bank ? ($card->bank->name ?: '-') : '-';
                            $cardName = $card->name ?: '-';
                            $gracePeriod = $card->grace_period !== null && $card->grace_period !== '' ? $card->grace_period . ' дн.' : '-';
                            $creditLimit = $card->credit_limit !== null && $card->credit_limit !== '' ? 'до ' . number_format((float) $card->credit_limit, 0, '', ' ') . ' ₽' : '-';
                            $annualFee = $card->annual_fee !== null && $card->annual_fee !== '' ? $card->annual_fee . ' ₽' : '-';
                            $rate = $card->rate !== null && $card->rate !== '' ? $card->rate . '%' : '-';
                            $cardUrl = $card->slug ? route('cards.show', $card->slug) : '#';
                            $cardImage = $card->image ? asset('storage/' . $card->image) : null;
                        @endphp
                        <div class="karty-card">
                            <div class="karty-card__col karty-card__name">
                                <div class="karty-card__name-inner">
                                    @if($cardImage)
                                        <img class="karty-card__image" src="{{ $cardImage }}" alt="{{ $cardName }}" width="101" height="66">
                                    @else
                                        <div class="karty-card__image karty-card__image-placeholder">—</div>
                                    @endif
                                    <div class="karty-card__name-block">
                                        <div class="karty-card__name-text">{{ $bankName }}</div>
                                        <span class="karty-card__label">{{ $cardName }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="karty-card__col">
                                <span class="karty-card__label">Льготный период</span>
                                <span class="karty-card__value">{{ $gracePeriod }}</span>
                            </div>
                            <div class="karty-card__col">
                                <span class="karty-card__label">Кредитный лимит</span>
                                <span class="karty-card__value">{{ $creditLimit }}</span>
                            </div>
                            <div class="karty-card__col">
                                <span class="karty-card__label">Годовое обслуживание</span>
                                <span class="karty-card__value">{{ $annualFee }}</span>
                            </div>
                            <div class="karty-card__col">
                                <span class="karty-card__label">Ставка</span>
                                <span class="karty-card__value">{{ $rate }}</span>
                            </div>
                            <div class="karty-card__col karty-card__action">
                                <a href="{{ $cardUrl }}" class="tf-btn btn-primary2 btn-px-28 height-2 rounded-12">
                                    <span>Подробнее</span>
                                    <span class="bg-effect"></span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-body-1 text_mono-gray-7">В этой категории пока нет карт.</p>
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
@endsection
