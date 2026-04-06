@extends('layouts.app')
@include('layouts.partials.redirect-city-push')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $section->title ?? $bank->name ?? 'Банк',
    'subtitle' => $section->subtitle ?? null,
    'showCitySelect' => true,
    'citySelectBase' => 'banki/' . $bank->slug,
    'allowedCitySlugs' => isset($availableCities) ? $availableCities->pluck('slug')->values()->all() : [],
    'city' => $currentCity ?? null,
    'cityName' => 'Вся Россия',
    'breadcrumbs' => [
        ['url' => url('/'), 'label' => 'Главная'],
        ['url' => url_section('banki'), 'label' => 'Банки'],
        ['label' => $section->title ?? ($bank->name ?? 'Банк')],
    ],
])
@endsection

@section('content')
<div class="main-content style-1">
    <div class="section-opportunities tf-spacing-27">
        <div class="tf-container">
            <div class="content">
                <section class="credit-offer-card loan-offer-card">
                    <div class="credit-offer-card__top">
                        <div class="credit-offer-card__headline">
                <h2 class="credit-offer-card__title">Банк {{ $bank->name }}</h2>
                <div class="credit-offer-card__rating" aria-label="Рейтинг банка">
                    <div class="credit-offer-card__stars" aria-hidden="true">
                        @for ($star = 1; $star <= 5; $star++)
                            <span class="credit-offer-card__star"></span>
                        @endfor
                    </div>
                    <span class="credit-offer-card__rating-text">Без рейтинга</span>
                </div>
            </div>
        </div>

        <div class="credit-offer-card__body">
            <div class="credit-offer-card__brand">
                <div class="credit-offer-card__logo-wrap">
                    @if($bank->logo || $bank->logo_square)
                        <img class="credit-offer-card__logo" src="{{ asset('storage/' . ($bank->logo_square ?? $bank->logo)) }}" alt="{{ $bank->name }}" width="200" height="200">
                    @else
                        <div class="credit-offer-card__logo-fallback">{{ mb_substr($bank->name, 0, 1) }}</div>
                    @endif
                </div>
            </div>

            <div class="credit-offer-card__content">
                <div class="credit-offer-card__summary">
                    @if($bank->website)
                    <div class="credit-offer-card__summary-item">
                        <span class="credit-offer-card__summary-label">Сайт</span>
                        <span class="credit-offer-card__summary-value"><a href="{{ (str_starts_with($bank->website, 'http') ? $bank->website : 'https://' . ltrim($bank->website, '/')) }}" target="_blank" rel="noopener noreferrer">{{ $bank->website }}</a></span>
                    </div>
                    @endif

                    @if($bank->phone)
                    <div class="credit-offer-card__summary-item">
                        <span class="credit-offer-card__summary-label">Телефон</span>
                        <span class="credit-offer-card__summary-value"><a href="tel:{{ preg_replace('/[^\d+]/', '', $bank->phone) }}" class="link">{{ $bank->phone }}</a></span>
                    </div>
                    @endif

                    @if($bank->head_office)
                    <div class="credit-offer-card__summary-item">
                        <span class="credit-offer-card__summary-label">Головной офис</span>
                        <span class="credit-offer-card__summary-value">{{ $bank->head_office }}</span>
                    </div>
                    @endif

                    @if($bank->license_number)
                    <div class="credit-offer-card__summary-item">
                        <span class="credit-offer-card__summary-label">Номер лицензии</span>
                        <span class="credit-offer-card__summary-value">{{ $bank->license_number }}</span>
                    </div>
                    @endif

                    @if($bank->license_date)
                    <div class="credit-offer-card__summary-item">
                        <span class="credit-offer-card__summary-label">Дата лицензии</span>
                        <span class="credit-offer-card__summary-value">{{ $bank->license_date->format('d.m.Y') }}</span>
                    </div>
                    @endif

                    <div class="credit-offer-card__toolbar">
                        <a href="{{ $bank->website ?: '#' }}" class="credit-offer-card__button" @if($bank->website) target="_blank" rel="nofollow noopener" @endif>Перейти на сайт</a>
                        <span class="credit-offer-card__updated">Обновлено: {{ $bank->updated_at?->format('d.m.Y') ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bank Navigation Tabs -->
    <div class="bank-page-nav">
        <div class="tf-container">
            <div class="widget-tabs style-1">
                <ul class="widget-menu-tab overflow-x-auto">
                    <li class="item-title h6 active">
                        <a href="javascript:void(0)" class="text-whitespace nav-tab-link active">О банке</a>
                    </li>
                    <li class="item-title h6">
                        <a href="{{ route('banks.reviews', $bank->slug) }}" class="text-whitespace nav-tab-link">Отзывы ({{ $bank->reviews->count() }})</a>
                    </li>
                    <li class="item-title h6">
                        @php
                            $branchesTabCount = $branchesCount ?? $bank->branches->count();
                            $branchesTabUrl = !empty($currentCity)
                                ? route('banks.branches.city', ['slug' => $bank->slug, 'citySlug' => $currentCity->slug])
                                : route('banks.branches', $bank->slug);
                        @endphp
                        <a href="{{ $branchesTabUrl }}" class="text-whitespace nav-tab-link">Отделения ({{ $branchesTabCount }})</a>
                    </li>
                    <li class="item-title h6">
                        <a href="{{ route('banks.deposits', $bank->slug) }}" class="text-whitespace nav-tab-link">Вклады ({{ $bank->deposits->count() }})</a>
                    </li>
                    <li class="item-title h6">
                        <a href="{{ route('banks.cards', $bank->slug) }}" class="text-whitespace nav-tab-link">Карты ({{ $bank->cards->count() }})</a>
                    </li>
                    <li class="item-title h6">
                        <a href="{{ route('banks.credits', $bank->slug) }}" class="text-whitespace nav-tab-link">Кредиты ({{ $bank->credits->count() }})</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="credit-offer-card__section tf-spacing-27">
        <h3 class="credit-offer-card__section-title">Описание банка</h3>
        <div class="credit-offer-card__description">
            @if(filled($bank->description))
                {!! description_to_html($bank->description) !!}
            @else
                <p>Описание банка пока не добавлено.</p>
            @endif
        </div>
    </div>

    @include('partials.reviews-section', [
        'reviewable' => $bank,
        'sectionTitle' => 'Отзывы по банковскому обслуживанию',
        'serviceLabel' => 'Банковское обслуживание',
        'productName' => $bank->name,
        'formAction' => route('banks.reviews.store', $bank),
        'bankId' => $bank->id,
        'bankName' => $bank->name,
        'formId' => 'bank-' . $bank->id,
    ])

            </div>
        </div>
    </div>
</div>

<style>
.bank-page-nav {
    margin: 38px 0 0 0;
}

.widget-tabs.style-1 .widget-menu-tab {
    display: flex;
    gap: 43px;
    padding-bottom: 0;
    margin-bottom: 0;
    border-bottom: none;
}

.bank-page-nav .widget-menu-tab {
    list-style: none;
    display: flex;
    gap: 0;
    padding: 0;
    margin: 0;
    border: none;
}

.bank-page-nav .item-title {
    padding: 16px 24px;
    margin: 0;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
}

.bank-page-nav .item-title.active {
    border-bottom-color: var(--Primary-ic-blue);
}

.bank-page-nav .item-title:hover {
    background-color: var(--Mono-gray-1);
}

.bank-page-nav .nav-tab-link {
    text-decoration: none;
    color: var(--Mono-gray-7);
    display: block;
}

.bank-page-nav .item-title.active .nav-tab-link {
    color: var(--Primary-ic-blue);
}

.bank-page-nav .overflow-x-auto {
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
}

/* Bank Offer Card Styles */
.bank-offer-card {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 32px;
    padding: 32px;
    border: 1px solid var(--Mono-gray-2);
    border-radius: 12px;
    background: #fff;
}

.bank-offer-card__body {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.bank-offer-card__brand {
    display: flex;
    gap: 20px;
    align-items: flex-start;
}

.bank-offer-card__logo-wrap {
    flex-shrink: 0;
    width: 120px;
    height: 120px;
    background: var(--Mono-gray-1);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.bank-offer-card__logo {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.bank-offer-card__logo--empty {
    font-size: 48px;
    color: var(--Mono-gray-5);
}

.bank-offer-card__bank-meta {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.bank-offer-card__bank-label {
    font-size: 12px;
    text-transform: uppercase;
    color: var(--Mono-gray-5);
    font-weight: 600;
    letter-spacing: 0.5px;
}

.bank-offer-card__bank-name {
    font-size: 20px;
    font-weight: 600;
    color: var(--Black);
    line-height: 1.3;
}

.bank-offer-card__content {
    line-height: 1.6;
    color: var(--Mono-gray-7);
}

.bank-offer-card__content p {
    margin: 0 0 16px 0;
}

.bank-offer-card__content p:last-child {
    margin-bottom: 0;
}

.bank-offer-card__content a {
    color: var(--Primary-ic-blue);
    text-decoration: none;
}

.bank-offer-card__content a:hover {
    text-decoration: underline;
}

.bank-offer-card__description {
    line-height: 1.6;
    color: var(--Mono-gray-7);
}

/* Bank Detail Rows */
.bank-offer-card__column {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.bank-offer-card__row {
    padding: 16px 0;
    border-bottom: 1px solid var(--Mono-gray-2);
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    align-items: start;
}

.bank-offer-card__row:last-child {
    border-bottom: none;
}

.bank-offer-card__cell {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.bank-offer-card__cell--label {
    color: var(--Mono-gray-7);
    font-weight: 500;
}

.bank-offer-card__cell--value {
    color: var(--Black);
    font-weight: 500;
    word-break: break-word;
}

.bank-offer-card__cell--value .link {
    color: var(--Primary-ic-blue);
    text-decoration: none;
}

.bank-offer-card__cell--value .link:hover {
    text-decoration: underline;
}

.bank-offer-card__icon {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--Primary-ic-blue);
}

.bank-offer-card__icon svg {
    width: 100%;
    height: 100%;
}

/* Responsive */
@media (max-width: 1024px) {
    .bank-offer-card {
        grid-template-columns: 1fr;
        gap: 24px;
    }
}

@media (max-width: 768px) {
    .bank-page-nav .item-title {
        padding: 12px 16px;
        font-size: 14px;
    }

    .bank-offer-card {
        padding: 20px;
    }

    .bank-offer-card__brand {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .bank-offer-card__logo-wrap {
        width: 100px;
        height: 100px;
    }

    .bank-offer-card__row {
        grid-template-columns: 1fr;
        padding: 12px 0;
    }

    .bank-offer-card__cell {
        justify-content: space-between;
    }
}
</style>

@endsection
