@props(['deposit'])

@php
    /** @var \App\Models\Deposit $deposit */
    $deposit->loadMissing(['currencies.conditions']);
    $bank = $deposit->bank;
    $bankName = $bank?->name ?: 'Банк';
    $logoSource = $bank && filled($bank->logo_square ?? null) ? $bank->logo_square : ($bank && filled($bank->logo ?? null) ? $bank->logo : null);
    $logoPath = $logoSource ? (str_starts_with($logoSource, 'http') ? $logoSource : asset('storage/' . $logoSource)) : null;
    $bankWebsite = filled($bank?->website)
        ? (str_starts_with($bank->website, 'http') ? $bank->website : 'https://' . ltrim($bank->website, '/'))
        : null;
    $bankDetailUrl = filled($bank?->slug) ? url('/banki/' . $bank->slug) : null;

    $rating = (float) ($bank?->rating ?? 0);
    $ratingValue = $rating > 0 ? number_format($rating, 1, '.', ' ') : null;
    $reviewCount = (int) ($bank?->reviews_count ?? 0);
    $updatedAt = $deposit->updated_at?->format('d.m.Y');

    use App\Services\DepositConditionsMapper\DepositCurrencySummary;
    use App\Services\DepositConditionsMapper\DepositRatesMatrix;
    $currenciesWithRates = $deposit->currencies->filter(fn ($c) => $c->conditions->where('is_active', true)->isNotEmpty())->values();
    $currenciesData = [];
    foreach ($currenciesWithRates as $currency) {
        $currenciesData[$currency->currency_code] = DepositCurrencySummary::forCurrency($currency);
    }
    $firstCurrencyCode = $currenciesWithRates->first()?->currency_code ?? null;
    $firstData = $firstCurrencyCode ? ($currenciesData[$firstCurrencyCode] ?? []) : [];
    $currencySymbols = ['RUB' => '₽', 'USD' => '$', 'EUR' => '€', 'CNY' => '¥'];

    $boolText = static fn (bool $value): string => $value ? 'Да' : 'Нет';
    $fullTitle = 'Вклад ' . $deposit->name . (filled($bankName) ? ' от ' . $bankName : '');

    $rateLabel = function (array $d) {
        if (($d['min_rate'] ?? null) !== null && ($d['max_rate'] ?? null) !== null) {
            return 'от ' . rtrim(rtrim(number_format((float)$d['min_rate'], 2, '.', ''), '0'), '.') . '% до ' . rtrim(rtrim(number_format((float)$d['max_rate'], 2, '.', ''), '0'), '.') . '%';
        }
        if (($d['max_rate'] ?? null) !== null) {
            return 'до ' . rtrim(rtrim(number_format((float)$d['max_rate'], 2, '.', ''), '0'), '.') . '%';
        }
        if (($d['min_rate'] ?? null) !== null) {
            return 'от ' . rtrim(rtrim(number_format((float)$d['min_rate'], 2, '.', ''), '0'), '.') . '%';
        }
        return '—';
    };
    $termLabel = function (array $d) {
        $min = $d['min_term'] ?? null;
        $max = $d['max_term'] ?? null;
        if ($min !== null && $max !== null && (int)$min !== (int)$max) {
            return $min . '-' . $max . ' дн.';
        }
        if ($max !== null) return $max . ' дн.';
        if ($min !== null) return $min . ' дн.';
        return '—';
    };
    $amountLabel = function (array $d, string $symbol = '₽') {
        $min = $d['min_amount'] ?? null;
        $max = $d['max_amount'] ?? null;
        if ($min !== null && $max !== null) {
            return 'от ' . number_format((float)$min, 0, '', ' ') . ' ' . $symbol . ' до ' . number_format((float)$max, 0, '', ' ') . ' ' . $symbol;
        }
        if ($min !== null) {
            return 'от ' . number_format((float)$min, 0, '', ' ') . ' ' . $symbol;
        }
        if ($max !== null) {
            return 'до ' . number_format((float)$max, 0, '', ' ') . ' ' . $symbol;
        }
        return '—';
    };

    $summaryRateText = $rateLabel($firstData);
    $summaryTermText = $termLabel($firstData);
    $summaryAmountText = $firstCurrencyCode ? $amountLabel($firstData, $currencySymbols[$firstCurrencyCode] ?? $firstCurrencyCode) : '—';
@endphp

<script id="deposit-data" type="application/json">@json([
    'currencies' => $currenciesData,
    'symbols' => $currencySymbols,
])</script>

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
            @if(count($currenciesData) > 0)
            <div class="deposit-currency-tabs" role="tablist">
                @foreach(array_keys($currenciesData) as $code)
                    <button type="button" class="deposit-currency-tabs__btn {{ $code === $firstCurrencyCode ? 'is-active' : '' }}" data-currency="{{ $code }}" aria-selected="{{ $code === $firstCurrencyCode ? 'true' : 'false' }}">{{ $currencySymbols[$code] ?? $code }}</button>
                @endforeach
            </div>
            @endif
            <div class="credit-offer-card__summary">
                <div class="credit-offer-card__summary-item">
                    <span class="credit-offer-card__summary-label">Ставка</span>
                    <span class="credit-offer-card__summary-value" id="deposit-summary-rate" data-deposit="rate">{{ $summaryRateText }}</span>
                </div>
                <div class="credit-offer-card__summary-item">
                    <span class="credit-offer-card__summary-label">Срок вклада</span>
                    <span class="credit-offer-card__summary-value" id="deposit-summary-term" data-deposit="term">{{ $summaryTermText }}</span>
                </div>
                <div class="credit-offer-card__summary-item">
                    <span class="credit-offer-card__summary-label">Сумма</span>
                    <span class="credit-offer-card__summary-value" id="deposit-summary-amount" data-deposit="amount">{{ $summaryAmountText }}</span>
                </div>
                <div class="credit-offer-card__summary-item">
                    <span class="credit-offer-card__summary-label">Пополнение</span>
                    <span class="credit-offer-card__summary-value">{{ $boolText((bool) $deposit->replenishment) }}</span>
                </div>
                <div class="credit-offer-card__summary-item">
                    <span class="credit-offer-card__summary-label">Частичное снятие</span>
                    <span class="credit-offer-card__summary-value">{{ $boolText((bool) $deposit->partial_withdrawal) }}</span>
                </div>

                <div class="credit-offer-card__toolbar">
                    <a href="{{ $bankWebsite ?: '#deposit-parameters' }}" class="credit-offer-card__button" @if($bankWebsite) target="_blank" rel="nofollow noopener" @endif>Оформить</a>
                    <span class="credit-offer-card__updated">Информация обновлена: {{ $updatedAt ?: '—' }}</span>
                </div>
            </div>
        </div>
    </div>
</section>

@php
    $parameterItems = [
        'Ставка' => $summaryRateText,
        'Срок вклада' => $summaryTermText,
        'Сумма' => $summaryAmountText,
        'Тип вклада' => filled($deposit->deposit_type) ? $deposit->deposit_type : '—',
        'Капитализация' => $boolText((bool) $deposit->capitalization),
        'Онлайн открытие' => $boolText((bool) $deposit->online_opening),
        'Выплата процентов ежемесячно' => $boolText((bool) $deposit->monthly_interest_payment),
        'Пополнение' => $boolText((bool) $deposit->replenishment),
        'Частичное снятие' => $boolText((bool) $deposit->partial_withdrawal),
        'Досрочное расторжение' => $boolText((bool) $deposit->early_termination),
        'Автопролонгация' => $boolText((bool) $deposit->auto_prolongation),
        'Страхование' => $boolText((bool) $deposit->insurance),
    ];
@endphp

<script>
(function() {
    var script = document.getElementById('deposit-data');
    if (!script) return;
    var data = JSON.parse(script.textContent);
    var currencies = data.currencies || {};
    var codes = Object.keys(currencies);
    if (codes.length === 0) return;
    var labelsByCode = {};
    codes.forEach(function(code, i) {
        var d = currencies[code];
        var sym = (data.symbols && data.symbols[code]) || code;
        labelsByCode[code] = {
            rate: d.min_rate != null && d.max_rate != null ? ('от ' + Number(d.min_rate).toFixed(2).replace(/\.?0+$/, '') + '% до ' + Number(d.max_rate).toFixed(2).replace(/\.?0+$/, '') + '%') : (d.max_rate != null ? ('до ' + Number(d.max_rate).toFixed(2).replace(/\.?0+$/, '') + '%') : (d.min_rate != null ? ('от ' + Number(d.min_rate).toFixed(2).replace(/\.?0+$/, '') + '%') : '—')),
            term: d.min_term != null && d.max_term != null && d.min_term !== d.max_term ? (d.min_term + '-' + d.max_term + ' дн.') : (d.max_term != null ? (d.max_term + ' дн.') : (d.min_term != null ? (d.min_term + ' дн.') : '—')),
            amount: d.min_amount != null && d.max_amount != null ? ('от ' + Number(d.min_amount).toLocaleString('ru-RU').replace(/,/g, ' ') + ' ' + sym + ' до ' + Number(d.max_amount).toLocaleString('ru-RU').replace(/,/g, ' ') + ' ' + sym) : (d.min_amount != null ? ('от ' + Number(d.min_amount).toLocaleString('ru-RU').replace(/,/g, ' ') + ' ' + sym) : (d.max_amount != null ? ('до ' + Number(d.max_amount).toLocaleString('ru-RU').replace(/,/g, ' ') + ' ' + sym) : '—'))
        };
    });
    var rateEl = document.getElementById('deposit-summary-rate');
    var termEl = document.getElementById('deposit-summary-term');
    var amountEl = document.getElementById('deposit-summary-amount');
    function showCurrency(code) {
        var L = labelsByCode[code];
        if (!L) return;
        if (rateEl) rateEl.textContent = L.rate;
        if (termEl) termEl.textContent = L.term;
        if (amountEl) amountEl.textContent = L.amount;
    }
    document.querySelectorAll('.deposit-currency-tabs__btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var code = this.getAttribute('data-currency');
            document.querySelectorAll('.deposit-currency-tabs__btn').forEach(function(b) { b.classList.remove('is-active'); b.setAttribute('aria-selected', 'false'); });
            this.classList.add('is-active'); this.setAttribute('aria-selected', 'true');
            showCurrency(code);
        });
    });
})();
</script>

<div class="credit-offer-card__section" id="deposit-parameters">
    <h3 class="credit-offer-card__section-title">Параметры вклада</h3>
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

@if($currenciesWithRates->isNotEmpty())
<div class="credit-offer-card__section deposit-rates-calculator-row" id="deposit-rates">
    <div class="deposit-rates-col">
    <h3 class="credit-offer-card__section-title">Ставки по срокам и суммам</h3>
    <div class="deposit-rates-tabs">
        <div class="deposit-rates-tabs__head" role="tablist">
            @foreach($currenciesWithRates as $index => $currency)
                @php
                    $tabId = 'deposit-tab-' . $currency->currency_code;
                    $paneId = 'deposit-pane-' . $currency->currency_code;
                    $symbol = $currencySymbols[$currency->currency_code] ?? $currency->currency_code;
                @endphp
                <button type="button"
                    class="deposit-rates-tabs__tab {{ $index === 0 ? 'is-active' : '' }}"
                    role="tab"
                    aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                    aria-controls="{{ $paneId }}"
                    id="{{ $tabId }}"
                    data-deposit-tab="{{ $currency->currency_code }}">{{ $currency->currency_code }} ({{ $symbol }})</button>
            @endforeach
        </div>
        <div class="deposit-rates-tabs__panes">
            @foreach($currenciesWithRates as $index => $currency)
                @php
                    $matrix = DepositRatesMatrix::forCurrency($currency);
                    $paneId = 'deposit-pane-' . $currency->currency_code;
                    $symbol = $currencySymbols[$currency->currency_code] ?? '';
                @endphp
                <div class="deposit-rates-tabs__pane {{ $index === 0 ? 'is-active' : '' }}"
                    id="{{ $paneId }}"
                    role="tabpanel"
                    aria-labelledby="deposit-tab-{{ $currency->currency_code }}"
                    data-deposit-pane="{{ $currency->currency_code }}"
                    {{ $index !== 0 ? 'hidden' : '' }}>
                    @if(!empty($matrix['columns']))
                        <div class="deposit-rates deposit-rates--desc deposit-rates--autoheight">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Сумма вклада</th>
                                        @foreach($matrix['columns'] as $termDays)
                                            <th>{{ DepositRatesMatrix::formatTermLabel($termDays) }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($matrix['rows'] as $rowIndex => $row)
                                        <tr>
                                            <td>{{ $row['amount_label'] }} {{ $symbol }}</td>
                                            @foreach($matrix['columns'] as $colIndex => $termDays)
                                                @php $rate = $matrix['grid'][$rowIndex][$colIndex] ?? null; @endphp
                                                <td>
                                                    @if($rate !== null)
                                                        <div>{{ number_format($rate, 1, '.', '') }} %</div>
                                                    @else
                                                        <div>-</div>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    </div>
    <div class="deposit-calculator-col">
        {{ $afterRates ?? '' }}
    </div>
</div>
<script>
(function() {
    var head = document.querySelector('.deposit-rates-tabs__head');
    var panes = document.querySelectorAll('.deposit-rates-tabs__pane');
    if (!head) return;
    head.addEventListener('click', function(e) {
        var tab = e.target.closest('[data-deposit-tab]');
        if (!tab) return;
        var code = tab.getAttribute('data-deposit-tab');
        head.querySelectorAll('.deposit-rates-tabs__tab').forEach(function(t) { t.classList.remove('is-active'); t.setAttribute('aria-selected', 'false'); });
        tab.classList.add('is-active'); tab.setAttribute('aria-selected', 'true');
        panes.forEach(function(p) {
            var show = p.getAttribute('data-deposit-pane') === code;
            p.classList.toggle('is-active', show);
            p.hidden = !show;
        });
    });
})();
</script>
@endif

<div class="credit-offer-card__section">
    <h3 class="credit-offer-card__section-title">Описание вклада</h3>
    <div class="credit-offer-card__description">
        @if(filled($deposit->description))
            {!! description_to_html($deposit->description) !!}
        @else
            <p>Описание вклада пока не добавлено.</p>
        @endif
    </div>
</div>
