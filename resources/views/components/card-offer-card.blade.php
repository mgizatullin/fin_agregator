@props(['card'])

@php
    /** @var \App\Models\Card $card */
    $detailSections = [
        'Условия' => \App\Support\CardData::normalizeDetailItems($card->conditions_items ?? ($card->conditions_text ?? null)),
        'Проценты' => \App\Support\CardData::normalizeDetailItems($card->rates_items ?? ($card->rates_text ?? null)),
        'Кешбэк' => \App\Support\CardData::normalizeDetailItems($card->cashback_details_items ?? ($card->cashback_details_text ?? null)),
    ];
    $bank = $card->bank;
    $bankName = $bank?->name ?: 'Банк';
    $bankDetailUrl = filled($bank?->slug) ? url_section('banki/' . $bank->slug) : null;

    $cardImage = filled($card->image ?? null)
        ? (str_starts_with($card->image, 'http') ? $card->image : asset('storage/' . $card->image))
        : null;
    $bankLogoSource = $bank && filled($bank->logo_square ?? null) ? $bank->logo_square : ($bank && filled($bank->logo ?? null) ? $bank->logo : null);
    $bankLogoUrl = $bankLogoSource ? (str_starts_with($bankLogoSource, 'http') ? $bankLogoSource : asset('storage/' . $bankLogoSource)) : null;
    $bankWebsite = filled($bank?->website)
        ? (str_starts_with($bank->website, 'http') ? $bank->website : 'https://' . ltrim($bank->website, '/'))
        : null;

    $reviews = $card->reviews->where('is_published', true)->values();
    $rating = $reviews->count() ? (float) $reviews->avg('rating') : (float) ($bank?->rating ?? 0);
    $ratingValue = $rating > 0 ? number_format($rating, 1, '.', ' ') : null;
    $reviewCount = (int) $reviews->count();
    $updatedAt = $card->updated_at?->format('d.m.Y');

    $limit = filled($card->credit_limit) ? number_format((float) $card->credit_limit, 0, '', ' ') . ' ₽' : '—';
    $gracePeriod = filled($card->grace_period) ? $card->grace_period . ' дн.' : '—';
    $serviceCost = filled($card->annual_fee_text) ? $card->annual_fee_text : '—';
    $cashback = filled($card->cashback) ? $card->cashback : '—';
    $psk = filled($card->psk_text) ? $card->psk_text : '—';
    $cardType = filled($card->card_type) ? $card->card_type : '—';
    $atmWithdrawal = filled($card->atm_withdrawal_text) ? $card->atm_withdrawal_text : '—';
    $decisionText = filled($card->decision_text) ? $card->decision_text : '—';

    $fullTitle = ($card->name ?: 'Кредитная карта') . (filled($bankName) ? ' от ' . $bankName : '');

    $summaryItems = [
        'Кредитный лимит' => $limit,
        'Льготный период' => $gracePeriod,
        'Кэшбэк' => $cashback,
        'ПСК' => $psk,
        'Тип карты' => $cardType,
    ];

    $parameterItems = [
        'Организация' => $bankName,
        'Тип карты' => $cardType,
        'Кредитный лимит' => $limit,
        'Льготный период' => $gracePeriod,
        'Кэшбэк' => $cashback,
        'Стоимость обслуживания' => $serviceCost,
        'Снятие в банкомате' => $atmWithdrawal,
        'ПСК' => $psk,
        'Срок рассмотрения' => $decisionText,
    ];

    $faqItems = is_array($card->faq ?? null) ? $card->faq : (is_string($card->faq ?? null) ? json_decode($card->faq, true) : []);
    $faqItems = $faqItems ?: [];
@endphp

<section class="credit-offer-card card-offer-card">
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
            <div class="credit-offer-card__logo-wrap card-offer-card__logo-wrap">
                @if($cardImage)
                    <img class="credit-offer-card__logo card-offer-card__logo" src="{{ $cardImage }}" alt="{{ $card->name }}" width="320" height="200">
                @else
                    <div class="credit-offer-card__logo-fallback" aria-hidden="true">{{ mb_substr($card->name ?? 'К', 0, 1) }}</div>
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
                    <a href="{{ $bankWebsite ?: '#card-parameters' }}" class="credit-offer-card__button" @if($bankWebsite) target="_blank" rel="nofollow noopener" @endif>Оформить карту</a>
                    <span class="credit-offer-card__updated">Информация обновлена: {{ $updatedAt ?: '—' }}</span>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="credit-page-row">
    <div class="credit-page-main">
        <div class="credit-offer-card__section" id="card-parameters">
            <h3 class="credit-offer-card__section-title">Параметры карты</h3>
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

        @foreach($detailSections as $sectionTitle => $items)
            @if($items !== [])
                <div class="credit-offer-card__section">
                    <h3 class="credit-offer-card__section-title">{{ $sectionTitle }}</h3>
                    <div class="credit-offer-card__table">
                        @foreach(array_chunk($items, (int) ceil(count($items) / 2)) as $detailColumn)
                            <div class="credit-offer-card__column">
                                @foreach($detailColumn as $item)
                                    <div class="credit-offer-card__row">
                                        <div class="credit-offer-card__cell credit-offer-card__cell--label">
                                            <span class="credit-offer-card__icon" aria-hidden="true">
                                                <svg viewBox="0 0 20 20" fill="none">
                                                    <path d="M10 2.5L17 6.1V13.9L10 17.5L3 13.9V6.1L10 2.5Z" stroke="currentColor" stroke-width="1.4"/>
                                                    <path d="M7.2 10L9 11.8L12.8 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </span>
                                            <span>{{ $item['parameter'] }}</span>
                                        </div>
                                        <div class="credit-offer-card__cell credit-offer-card__cell--value">{{ $item['value'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach

        <div class="credit-offer-card__section">
            <h3 class="credit-offer-card__section-title">Описание карты</h3>
            <div class="credit-offer-card__description">
                @if(filled($card->description))
                    {!! description_to_html($card->description) !!}
                @else
                    <p>Описание карты пока не добавлено.</p>
                @endif
            </div>
        </div>

        @if(count($faqItems) > 0)
            <div class="credit-offer-card__section">
                <h3 class="credit-offer-card__section-title">Вопросы и ответы</h3>
                <ul class="accordion-wrap gap-16 style-faqs d-grid gap_16" id="accordion-card-faq">
                    @foreach($faqItems as $idx => $item)
                        @php
                            $question = is_array($item) ? ($item['question'] ?? $item['q'] ?? '') : '';
                            $answer = is_array($item) ? ($item['answer'] ?? $item['a'] ?? '') : '';
                            $accordionId = 'accordion-card-faq-' . $idx;
                        @endphp
                        @if(filled($question))
                            <li class="accordion-item action_click scrolling-effect effectBottom style-default v2 {{ $loop->first ? 'active' : '' }}">
                                <a href="#{{ $accordionId }}" class="accordion-title action {{ $loop->first ? 'current' : 'collapsed current' }}" data-bs-toggle="collapse" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="{{ $accordionId }}">
                                    <div class="heading">
                                        <h5 class="title">{{ $question }}</h5>
                                    </div>
                                    <span class="icon"></span>
                                </a>
                                <div id="{{ $accordionId }}" class="collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#accordion-card-faq">
                                    <div class="accordion-faqs-content">
                                        <p class="text-body-2 lh-20">{!! nl2br(e($answer)) !!}</p>
                                    </div>
                                </div>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endif

        @include('partials.reviews-section', [
            'reviewable' => $card,
            'sectionTitle' => 'Отзывы по карте',
            'serviceLabel' => filled($card->card_type) && stripos($card->card_type, 'дебет') !== false ? 'Дебетовая карта' : 'Кредитная карта',
            'productName' => $card->name,
            'formAction' => route('cards.reviews.store', $card),
            'bankId' => $card->bank_id,
            'bankName' => $card->bank?->name ?? '—',
            'formId' => 'card-' . $card->id,
        ])
    </div>

    <div class="credit-page-sidebar">
        <aside class="card-offer-sidebar">
            <div class="card-offer-sidebar__top">
                @if($bankLogoUrl)
                    <div class="card-offer-sidebar__logo-wrap">
                        <img class="card-offer-sidebar__logo" src="{{ $bankLogoUrl }}" alt="{{ $bankName }}" width="120" height="120">
                    </div>
                @endif

                <div class="card-offer-sidebar__items">
                    <div class="card-offer-sidebar__item">
                        <span class="card-offer-sidebar__label">Организация</span>
                        <strong>{{ $bankName }}</strong>
                    </div>

                    @if($bankWebsite)
                        <div class="card-offer-sidebar__item">
                            <span class="card-offer-sidebar__label">Официальный сайт</span>
                            <a href="{{ $bankWebsite }}" class="link" target="_blank" rel="noopener">{{ $bankWebsite }}</a>
                        </div>
                    @endif

                    @if(filled($bank?->phone))
                        <div class="card-offer-sidebar__item">
                            <span class="card-offer-sidebar__label">Телефон</span>
                            <a href="tel:{{ preg_replace('/\s+/', '', $bank->phone) }}" class="link">{{ $bank->phone }}</a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card-offer-sidebar__actions">
                <a href="{{ $bankWebsite ?: '#' }}" class="tf-btn btn-primary2 btn-px-28 height-2 rounded-12 w-full" @if($bankWebsite) target="_blank" rel="nofollow noopener" @endif>
                    <span>Оформить карту онлайн</span>
                    <span class="bg-effect"></span>
                </a>
            </div>
        </aside>
    </div>
</div>
