@php
    $detailSections = [
        'Условия' => \App\Support\CardData::normalizeDetailItems($card->conditions_items ?? ($card->conditions_text ?? null)),
        'Проценты' => \App\Support\CardData::normalizeDetailItems($card->rates_items ?? ($card->rates_text ?? null)),
        'Кешбэк' => \App\Support\CardData::normalizeDetailItems($card->cashback_details_items ?? ($card->cashback_details_text ?? null)),
    ];
    $cardImage = filled($card->image ?? null)
        ? (str_starts_with($card->image, 'http') ? $card->image : asset('storage/' . $card->image))
        : null;
@endphp

<div class="card-product-hero tf-spacing-11">
    <div class="tf-container card-product-hero__container">
        <div class="row align-items-center">
            <div class="col-lg-4 mb_24 mb-lg-0">
                @if($cardImage)
                    <div class="card-product-hero__img-wrap">
                        <img class="card-product-hero__img" src="{{ $cardImage }}" alt="{{ $card->name }}" width="300" height="190">
                    </div>
                @else
                    <div class="card-product-hero__img-placeholder"></div>
                @endif
            </div>
            <div class="col-lg-8">
                <table class="table card-product-specs mb_24">
                    <tbody>
                        <tr>
                            <td class="card-product-specs__cell card-product-specs__cell--label"><span class="karty-card__label">Кредитный лимит</span></td>
                            <td class="card-product-specs__cell card-product-specs__cell--value"><span class="karty-card__value">{{ filled($card->credit_limit) ? number_format((float)$card->credit_limit, 0, '', ' ') . ' ₽' : '—' }}</span></td>
                            <td class="card-product-specs__cell card-product-specs__cell--label"><span class="karty-card__label">Кэшбэк</span></td>
                            <td class="card-product-specs__cell card-product-specs__cell--value"><span class="karty-card__value">{{ filled($card->cashback) ? $card->cashback : '—' }}</span></td>
                        </tr>
                        <tr>
                            <td class="card-product-specs__cell card-product-specs__cell--label"><span class="karty-card__label">Льготный период (дней)</span></td>
                            <td class="card-product-specs__cell card-product-specs__cell--value"><span class="karty-card__value">{{ filled($card->grace_period) ? $card->grace_period : '—' }}</span></td>
                            <td class="card-product-specs__cell card-product-specs__cell--label"><span class="karty-card__label">Стоимость обслуживания</span></td>
                            <td class="card-product-specs__cell card-product-specs__cell--value"><span class="karty-card__value">{{ filled($card->annual_fee_text) ? $card->annual_fee_text : '—' }}</span></td>
                        </tr>
                        <tr>
                            <td class="card-product-specs__cell card-product-specs__cell--label"><span class="karty-card__label">Снятие в банкомате</span></td>
                            <td class="card-product-specs__cell card-product-specs__cell--value"><span class="karty-card__value">{{ filled($card->atm_withdrawal_text) ? $card->atm_withdrawal_text : '—' }}</span></td>
                            <td class="card-product-specs__cell card-product-specs__cell--label"><span class="karty-card__label">Срок рассмотрения</span></td>
                            <td class="card-product-specs__cell card-product-specs__cell--value"><span class="karty-card__value">{{ filled($card->decision_text) ? $card->decision_text : '—' }}</span></td>
                        </tr>
                        <tr>
                            <td class="card-product-specs__cell card-product-specs__cell--label"><span class="karty-card__label">ПСК</span></td>
                            <td class="card-product-specs__cell card-product-specs__cell--value"><span class="karty-card__value">{{ filled($card->psk_text) ? $card->psk_text : '—' }}</span></td>
                            <td class="card-product-specs__cell card-product-specs__cell--label"><span class="karty-card__label">Тип карты</span></td>
                            <td class="card-product-specs__cell card-product-specs__cell--value"><span class="karty-card__value">{{ filled($card->card_type) ? $card->card_type : '—' }}</span></td>
                        </tr>
                    </tbody>
                </table>
                <div class="card-product-hero__actions d-flex flex-wrap align-items-center gap_16 mb_24">
                    <a href="#" class="tf-btn btn-primary2">
                        <span>Оформить карту</span>
                        <span class="bg-effect"></span>
                    </a>
                    @if($card->updated_at)
                        <span class="card-product-meta text-body-2 text_mono-gray-7">Дата обновления: {{ $card->updated_at->format('d.m.Y') }}</span>
                    @endif
                </div>
                <div class="card-product-meta text-body-2 text_mono-gray-7">
                    @if($card->bank && $card->bank->phone)
                        <p class="mb_8">Телефон банка: <a href="tel:{{ preg_replace('/\s+/', '', $card->bank->phone) }}" class="link">{{ $card->bank->phone }}</a></p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $tariffLabels = [
        'Платежная система' => $card->payment_system,
        'Тип карты' => $card->card_type,
        'Максимальный лимит' => $card->max_limit !== null ? number_format((float)$card->max_limit, 0, '', ' ') . ' ₽' : null,
        'Минимальный лимит' => $card->min_limit !== null ? number_format((float)$card->min_limit, 0, '', ' ') . ' ₽' : null,
        'Диапазон ПСК' => $card->psk_range,
        'Стоимость обслуживания' => filled($card->annual_fee_text) ? $card->annual_fee_text : null,
        'Льготный период' => $card->grace_period !== null ? $card->grace_period . ' дн.' : null,
        'Комиссия за снятие' => $card->cash_withdrawal_fee,
        'Кэшбэк' => $card->cashback,
        'Минимальный платеж' => $card->min_payment,
        'Время рассмотрения' => $card->approval_time,
        'Доставка карты' => $card->delivery_type,
        'Возраст' => $card->age_requirement,
        'Подтверждение дохода' => $card->income_proof,
        'Кредитный лимит' => $card->credit_limit !== null ? number_format((float)$card->credit_limit, 0, '', ' ') . ' ₽' : null,
        'ПСК' => filled($card->psk_text) ? $card->psk_text : null,
        'Снятие в банкомате' => filled($card->atm_withdrawal_text) ? $card->atm_withdrawal_text : null,
        'Срок рассмотрения' => filled($card->decision_text) ? $card->decision_text : null,
    ];
    $tariffRows = array_filter($tariffLabels, fn($v) => $v !== null && $v !== '');
    $visibleCount = 6;
    $hasHidden = count($tariffRows) > $visibleCount;
@endphp

<div class="section tf-spacing-9">
    <div class="tf-container">
        <h2 class="heading-section mb_24">Актуальные тарифы</h2>
        @if(count($tariffRows) > 0)
            <div class="card-tariffs-table-wrap">
                <table class="table card-tariffs-table card-product-specs">
                    <tbody>
                        @foreach($tariffRows as $label => $value)
                            <tr class="{{ $hasHidden && $loop->iteration > $visibleCount ? 'card-tariffs-table__row-hidden' : '' }}">
                                <td class="card-product-specs__cell card-product-specs__cell--label"><span class="karty-card__label">{{ $label }}</span></td>
                                <td class="card-product-specs__cell card-product-specs__cell--value"><span class="karty-card__value">{{ $value }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($hasHidden)
                    <button type="button" class="tf-btn btn-border card-tariffs-expand-btn mt_16" data-expand-target=".card-tariffs-table__row-hidden" data-expand-label="Развернуть" data-collapse-label="Свернуть">
                        <span>Развернуть</span>
                        <span class="bg-effect"></span>
                    </button>
                @endif
            </div>
        @else
            <p class="text-body-2 text_mono-gray-7">Тарифы не указаны.</p>
        @endif
    </div>
</div>

@foreach($detailSections as $title => $items)
    @if($items !== [])
        <div class="section tf-spacing-9">
            <div class="tf-container">
                <h2 class="heading-section mb_24">{{ $title }}</h2>
                <div class="credit-offer-card__table">
                    @foreach(array_chunk($items, (int) ceil(count($items) / 2)) as $detailColumn)
                        <div class="credit-offer-card__column">
                            @foreach($detailColumn as $item)
                                <div class="credit-offer-card__row">
                                    <div class="credit-offer-card__cell credit-offer-card__cell--label">
                                        <span class="credit-offer-card__icon" aria-hidden="true">
                                            <svg viewBox="0 0 20 20" fill="none">
                                                <path d="M10 2.5L17 6.1V13.9L10 17.5L3 13.9V6.1L10 2.5Z" stroke="currentColor" stroke-width="1.4"></path>
                                                <path d="M7.2 10L9 11.8L12.8 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"></path>
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
        </div>
    @endif
@endforeach

@if(filled($card->description))
    <div class="tf-container tf-spacing-9">
        <div class="content card-description">
            {!! description_to_html($card->description) !!}
        </div>
    </div>
@endif

@php
    $faqItems = is_array($card->faq) ? $card->faq : (is_string($card->faq) ? json_decode($card->faq, true) : []);
    $faqItems = $faqItems ?: [];
@endphp
@if(count($faqItems) > 0)
    <div class="section-faqs style-1 tf-spacing-8">
        <div class="tf-container">
            <div class="heading-section mb_40">
                <h2 class="heading-title">Вопросы и ответы</h2>
            </div>
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
    </div>
@endif
