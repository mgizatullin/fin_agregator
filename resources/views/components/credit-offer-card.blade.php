@props(['credit'])

@php
    /** @var \App\Models\Credit $credit */
    $bank = $credit->bank;
    $bankName = $bank?->name ?: 'Банк';
    $logoSource = $bank && filled($bank->logo_square ?? null) ? $bank->logo_square : ($bank && filled($bank->logo ?? null) ? $bank->logo : null);
    $logoPath = $logoSource ? (str_starts_with($logoSource, 'http') ? $logoSource : asset('storage/' . $logoSource)) : null;
    $bankWebsite = filled($bank?->website)
        ? (str_starts_with($bank->website, 'http') ? $bank->website : 'https://' . ltrim($bank->website, '/'))
        : null;
    $bankDetailUrl = filled($bank?->slug) ? url('/banki/' . $bank->slug) : null;

    $rating = (float) ($credit->review_rating ?? $bank?->rating ?? 0);
    $ratingValue = $rating > 0 ? number_format($rating, 1, '.', ' ') : null;
    $reviewCount = (int) ($credit->review_count ?? 0);
    $updatedAt = $credit->updated_at?->format('d.m.Y');

    $maxAmount = filled($credit->max_amount) ? 'до ' . number_format((float) $credit->max_amount, 0, '', ' ') . ' ₽' : '—';
    $minAmount = filled($credit->min_amount) ? number_format((float) $credit->min_amount, 0, '', ' ') . ' ₽' : '—';
    $rate = filled($credit->rate) ? 'от ' . rtrim(rtrim(number_format((float) $credit->rate, 2, '.', ''), '0'), '.') . '%' : '—';
    $psk = filled($credit->psk) ? 'до ' . rtrim(rtrim(number_format((float) $credit->psk, 2, '.', ''), '0'), '.') . '%' : '—';

    $termFrom = $credit->min_term_months ?? $credit->term_months;
    $termTo = $credit->max_term_months ?? $credit->term_months;
    $term = $termFrom && $termTo
        ? ($termFrom === $termTo ? $termFrom . ' мес.' : $termFrom . '-' . $termTo . ' мес.')
        : '—';

    $ageValues = collect([$credit->age_min, $credit->age_max])->filter(fn ($value) => filled($value))->values();
    $age = $ageValues->isNotEmpty() ? $ageValues->implode('-') . ' лет' : '—';

    $decision = filled($credit->decision) ? $credit->decision : '—';
    $fullTitle = 'Кредит ' . $credit->name . (filled($bankName) ? ' от ' . $bankName : '');

    $summaryItems = [
        'Сумма до' => $maxAmount,
        'Ставка' => $rate,
        'ПСК до' => $psk,
        'Срок кредита' => $term,
        'Сумма от' => $minAmount,
        'Возраст' => $age,
    ];

    $parameterItems = [
        'Сумма до' => $maxAmount,
        'Ставка' => $rate,
        'ПСК до' => $psk,
        'Срок кредита' => $term,
        'Сумма от' => $minAmount,
        'Возраст' => $age,
        'Решение' => $decision,
        'Тип выплат' => filled($credit->payment_type) ? $credit->payment_type : '—',
        'Штраф' => filled($credit->penalty) ? $credit->penalty : '—',
        'Без залога' => $credit->no_collateral ? 'Да' : 'Нет',
        'Без поручителей' => $credit->no_guarantors ? 'Да' : 'Нет',
    ];
@endphp

<section class="credit-offer-card">
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
                    <img class="credit-offer-card__logo" src="{{ $logoPath }}" alt="{{ $bankName }}" width="200" height="200">
                @else
                    <div class="credit-offer-card__logo-fallback" aria-hidden="true">{{ mb_substr($bankName, 0, 1) }}</div>
                @endif
            </div>
            <div class="credit-offer-card__bank-meta">
                <span class="credit-offer-card__bank-label">Банк</span>
                <div class="credit-offer-card__bank-name">
                    @if($bankDetailUrl)
                        <a href="{{ $bankDetailUrl }}" class="credit-offer-card__bank-link">{{ $bankName }}</a>
                    @else
                        {{ $bankName }}
                    @endif
                </div>
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
                    <a href="{{ $bankWebsite ?: '#credit-parameters' }}" class="credit-offer-card__button" @if($bankWebsite) target="_blank" rel="nofollow noopener" @endif>Оформить</a>
                    <span class="credit-offer-card__updated">Информация обновлена: {{ $updatedAt ?: '—' }}</span>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="credit-offer-card__section" id="credit-parameters">
    <h3 class="credit-offer-card__section-title">Параметры кредита</h3>
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
    <h3 class="credit-offer-card__section-title">Описание кредита</h3>
    <div class="credit-offer-card__description">
        @if(filled($credit->description))
            {!! description_to_html($credit->description) !!}
        @else
            <p>Описание кредита пока не добавлено.</p>
        @endif
    </div>
</div>

@include('partials.reviews-section', [
    'reviewable' => $credit,
    'sectionTitle' => 'Отзывы по кредиту',
    'serviceLabel' => 'Кредит',
    'productName' => $credit->name,
    'formAction' => route('credits.reviews.store', $credit),
    'bankId' => $credit->bank_id,
    'bankName' => $credit->bank?->name ?? '—',
    'formId' => 'credit-' . $credit->id,
])
