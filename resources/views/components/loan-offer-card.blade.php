@props(['loan'])

@php
    /** @var \App\Models\Loan $loan */
    $companyName = $loan->company_name ?: 'МФО';
    $logoSource = filled($loan->logo) ? $loan->logo : null;
    $logoPath = $logoSource ? (str_starts_with($logoSource, 'http') ? $logoSource : asset('storage/' . $logoSource)) : null;
    $website = filled($loan->website) ? (str_starts_with($loan->website, 'http') ? $loan->website : 'https://' . ltrim($loan->website, '/')) : null;

    $rating = (float) ($loan->rating ?? 0);
    $ratingValue = $rating > 0 ? number_format($rating, 1, '.', ' ') : null;
    $reviewCount = (int) ($loan->reviews_count ?? 0);
    $updatedAt = $loan->updated_at?->format('d.m.Y');

    $minAmountValue = $loan->min_amount !== null ? (float) $loan->min_amount : 1.0;
    $maxAmountValue = $loan->max_amount !== null ? (float) $loan->max_amount : null;

    $minAmount = 'от ' . number_format((float) $minAmountValue, 0, '', ' ') . ' ₽';
    $maxAmount = $maxAmountValue !== null
        ? 'до ' . number_format((float) $maxAmountValue, 0, '', ' ') . ' ₽'
        : '—';
    $amountRange = $maxAmountValue !== null
        ? 'от ' . number_format((float) $minAmountValue, 0, '', ' ') . ' до ' . number_format((float) $maxAmountValue, 0, '', ' ') . ' ₽'
        : $minAmount;

    $minTermValue = $loan->term_days_min !== null ? (int) $loan->term_days_min : 1;
    $maxTermValue = $loan->term_days !== null ? (int) $loan->term_days : null;

    $termNoInterest = filled($loan->term_no_interest) ? (int) $loan->term_no_interest . ' дн.' : '—';
    $rate = filled($loan->rate) ? rtrim(rtrim(number_format((float) $loan->rate, 2, '.', ''), '0'), '.') . '%' : '—';
    $psk = filled($loan->psk) ? rtrim(rtrim(number_format((float) $loan->psk, 2, '.', ''), '0'), '.') . '%' : '—';

    $termRange = $maxTermValue !== null
        ? 'от ' . number_format((int) $minTermValue, 0, '', ' ') . ' до ' . number_format((int) $maxTermValue, 0, '', ' ') . ' дней'
        : 'от ' . number_format((int) $minTermValue, 0, '', ' ') . ' дней';
    $minTerm = 'от ' . number_format((int) $minTermValue, 0, '', ' ') . ' дней';
    $maxTerm = $maxTermValue !== null
        ? 'до ' . number_format((int) $maxTermValue, 0, '', ' ') . ' дней'
        : '—';

    $fullTitle = 'Займ ' . $loan->name . (filled($companyName) ? ' — ' . $companyName : '');

    $summaryItems = [
        'Сумма' => $amountRange,
        'Срок' => $termRange,
        'Срок без процентов' => $termNoInterest,
        'ПСК' => $psk,
        'Ставка' => $rate,
    ];

    $parameterItems = [
        'Мин. сумма' => $minAmount,
        'Макс. сумма' => $maxAmount,
        'Мин. срок' => $minTerm,
        'Макс. срок' => $maxTerm,
        'Срок без процентов' => $termNoInterest,
        'ПСК' => $psk,
        'Ставка' => $rate,
        'Организация' => $companyName,
    ];
@endphp

<section class="credit-offer-card loan-offer-card">
    <div class="credit-offer-card__top">
        <div class="credit-offer-card__headline">
            <h2 class="credit-offer-card__title">{{ $fullTitle }}</h2>
            <div class="credit-offer-card__rating" aria-label="Рейтинг предложения">
                <div class="credit-offer-card__stars" aria-hidden="true">
                    @for ($star = 1; $star <= 5; $star++)
                        <span class="credit-offer-card__star {{ $rating >= $star ? 'is-active' : '' }}">★</span>
                    @endfor
                </div>
                <span class="credit-offer-card__rating-text">
                    {{ $ratingValue ?: 'Без рейтинга' }}
                    @if($reviewCount > 0)
                        <span>· {{ $reviewCount }} отзывов</span>
                    @endif
                </span>
            </div>
        </div>
    </div>

    <div class="credit-offer-card__body">
        <div class="credit-offer-card__brand">
            <div class="credit-offer-card__logo-wrap">
                @if($logoPath)
                    <img class="credit-offer-card__logo" src="{{ $logoPath }}" alt="{{ $companyName }}" width="200" height="200">
                @else
                    <div class="credit-offer-card__logo-fallback" aria-hidden="true">{{ mb_substr($companyName, 0, 1) }}</div>
                @endif
            </div>
            <div class="credit-offer-card__bank-meta">
                <span class="credit-offer-card__bank-label">МФО</span>
                <div class="credit-offer-card__bank-name">{{ $companyName }}</div>
            </div>
        </div>

        <div class="credit-offer-card__content">
            <div class="credit-offer-card__summary">
                @foreach($summaryItems as $label => $value)
                    <div class="credit-offer-card__summary-item">
                        <span class="credit-offer-card__summary-label">{{ $label }}</span>
                        <span class="credit-offer-card__summary-value">{{ $value }}</span>
                    </div>
                @endforeach

                <div class="credit-offer-card__toolbar">
                    <a href="{{ $website ?: '#loan-parameters' }}" class="credit-offer-card__button" @if($website) target="_blank" rel="nofollow noopener" @endif>Оформить</a>
                    <span class="credit-offer-card__updated">Информация обновлена: {{ $updatedAt ?: '—' }}</span>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="credit-page-row">
    <div class="credit-page-main">
<div class="credit-offer-card__section" id="loan-parameters">
    <h3 class="credit-offer-card__section-title">Параметры займа</h3>
    <div class="credit-offer-card__table">
        @foreach(array_chunk($parameterItems, (int) ceil(count($parameterItems) / 2), true) as $parameterColumn)
            <div class="credit-offer-card__column">
                @foreach($parameterColumn as $label => $value)
                    <div class="credit-offer-card__row">
                        <div class="credit-offer-card__cell credit-offer-card__cell--label">
                            <span class="credit-offer-card__icon" aria-hidden="true">
                                <svg viewBox="0 0 20 20" fill="none">
                                    <path d="M10 2.5L17 6.1V13.9L10 17.5L3 13.9V6.1L10 2.5Z" stroke="currentColor" stroke-width="1.4"/>
                                    <path d="M7.2 10L9 11.8L12.8 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span>{{ $label }}</span>
                        </div>
                        <div class="credit-offer-card__cell credit-offer-card__cell--value">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>

<div class="credit-offer-card__section">
    <h3 class="credit-offer-card__section-title">Описание займа</h3>
    <div class="credit-offer-card__description">
        @if(filled($loan->description))
            {!! description_to_html($loan->description) !!}
        @else
            <p>Описание займа пока не добавлено.</p>
        @endif
    </div>
</div>

@include('partials.reviews-section', [
    'reviewable' => $loan,
    'sectionTitle' => 'Отзывы по займу',
    'serviceLabel' => 'Займ',
    'productName' => $loan->name,
    'formAction' => route('loans.reviews.store', $loan),
    'bankId' => null,
    'bankName' => $companyName,
    'formId' => 'loan-' . $loan->id,
])
    </div>
    <div class="credit-page-sidebar">
        {{ $sidebar ?? '' }}
    </div>
</div>
