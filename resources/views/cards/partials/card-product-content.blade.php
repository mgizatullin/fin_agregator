<div class="card-product-hero tf-spacing-11">
    <div class="tf-container card-product-hero__container">
        <div class="row align-items-center">
            <div class="col-lg-4 mb_24 mb-lg-0">
                @if($card->image)
                    <div class="card-product-hero__img-wrap">
                        <img class="card-product-hero__img" src="{{ asset('storage/' . $card->image) }}" alt="{{ $card->name }}" width="300" height="190">
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
                            <td class="card-product-specs__cell card-product-specs__cell--label"><span class="karty-card__label">Стоимость выпуска</span></td>
                            <td class="card-product-specs__cell card-product-specs__cell--value"><span class="karty-card__value">{{ filled($card->issue_cost) ? $card->issue_cost . ' ₽' : '—' }}</span></td>
                        </tr>
                        <tr>
                            <td class="card-product-specs__cell card-product-specs__cell--label"><span class="karty-card__label">Годовое обслуживание</span></td>
                            <td class="card-product-specs__cell card-product-specs__cell--value"><span class="karty-card__value">{{ filled($card->annual_fee) ? $card->annual_fee . ' ₽' : '—' }}</span></td>
                            <td class="card-product-specs__cell card-product-specs__cell--label"><span class="karty-card__label">Снятие в банкомате</span></td>
                            <td class="card-product-specs__cell card-product-specs__cell--value"><span class="karty-card__value">{{ $card->atm_withdrawal !== null ? ($card->atm_withdrawal ? 'Да' : 'Нет') : '—' }}</span></td>
                        </tr>
                        <tr>
                            <td class="card-product-specs__cell card-product-specs__cell--label"><span class="karty-card__label">ПСК</span></td>
                            <td class="card-product-specs__cell card-product-specs__cell--value"><span class="karty-card__value">{{ filled($card->psk) ? $card->psk . '%' : '—' }}</span></td>
                            <td class="card-product-specs__cell card-product-specs__cell--label"><span class="karty-card__label">Тип карты</span></td>
                            <td class="card-product-specs__cell card-product-specs__cell--value"><span class="karty-card__value">{{ filled($card->card_type) ? $card->card_type : '—' }}</span></td>
                        </tr>
                        <tr>
                            <td class="card-product-specs__cell card-product-specs__cell--label"><span class="karty-card__label">Ставка</span></td>
                            <td class="card-product-specs__cell card-product-specs__cell--value"><span class="karty-card__value">{{ filled($card->rate) ? $card->rate . '%' : '—' }}</span></td>
                            <td class="card-product-specs__cell card-product-specs__cell--label"></td>
                            <td class="card-product-specs__cell card-product-specs__cell--value"></td>
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
        'Стоимость обслуживания' => $card->annual_fee !== null ? $card->annual_fee . ' ₽' : null,
        'Льготный период' => $card->grace_period !== null ? $card->grace_period . ' дн.' : null,
        'Комиссия за снятие' => $card->cash_withdrawal_fee,
        'Кэшбэк' => $card->cashback,
        'Минимальный платеж' => $card->min_payment,
        'Время рассмотрения' => $card->approval_time,
        'Доставка карты' => $card->delivery_type,
        'Возраст' => $card->age_requirement,
        'Подтверждение дохода' => $card->income_proof,
        'Кредитный лимит' => $card->credit_limit !== null ? number_format((float)$card->credit_limit, 0, '', ' ') . ' ₽' : null,
        'ПСК' => $card->psk !== null ? $card->psk . '%' : null,
        'Ставка' => $card->rate !== null ? $card->rate . '%' : null,
        'Стоимость выпуска' => $card->issue_cost !== null ? $card->issue_cost . ' ₽' : null,
        'Снятие в банкомате' => $card->atm_withdrawal !== null ? ($card->atm_withdrawal ? 'Да' : 'Нет') : null,
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
