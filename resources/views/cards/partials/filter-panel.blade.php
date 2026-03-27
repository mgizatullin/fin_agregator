@php
    $request = request();
    $meta = $filterMeta ?? [];

    $maxGracePeriod = max(0, (int) ($meta['max_grace_period'] ?? 0));
    $maxCreditLimit = max(0, (int) ($meta['max_credit_limit'] ?? 0));
    $maxAnnualFee = max(0, (int) ($meta['max_annual_fee'] ?? 0));
    $maxPsk = max(0, (float) ($meta['max_psk'] ?? 0));
    $maxCashback = max(0, (float) ($meta['max_cashback'] ?? 0));

    $creditLimitStep = $maxCreditLimit > 0 ? max(1000, (int) ceil($maxCreditLimit / 100)) : 1000;
    $annualFeeStep = $maxAnnualFee > 0 ? max(100, (int) ceil($maxAnnualFee / 100)) : 100;

    $gracePeriodValue = max(0, (int) $request->integer('grace_period', 0));
    $creditLimitValue = max(0, (int) $request->integer('credit_limit', 0));
    $annualFeeValue = max(0, (int) $request->integer('annual_fee', 0));
    $pskValue = max(0, (float) $request->input('psk', 0));
    $cashbackValue = max(0, (float) $request->input('cashback', 0));
@endphp

<div class="credit-filters mb_32" data-card-filters>
    <div class="credit-filters__grid credit-filters__grid--cards">
        <div class="credit-filters__field">
            <label class="credit-filters__label" for="card-filter-grace-period">Льготный период от</label>
            <div class="credit-filters__pair">
                <input id="card-filter-grace-period" type="text" inputmode="numeric" data-filter-number="grace_period" data-min="0" data-max="{{ $maxGracePeriod }}" value="{{ $gracePeriodValue }}" class="credit-filters__input" placeholder="0">
                <span class="credit-filters__suffix">дн.</span>
            </div>
            <input type="range" min="0" max="{{ $maxGracePeriod }}" step="1" value="{{ $gracePeriodValue }}" data-filter-range="grace_period" class="credit-filters__range">
        </div>

        <div class="credit-filters__field">
            <label class="credit-filters__label" for="card-filter-credit-limit">Кредитный лимит от</label>
            <div class="credit-filters__pair">
                <input id="card-filter-credit-limit" type="text" inputmode="numeric" data-filter-number="credit_limit" data-min="0" data-max="{{ $maxCreditLimit }}" value="{{ $creditLimitValue }}" class="credit-filters__input" placeholder="0">
                <span class="credit-filters__suffix">₽</span>
            </div>
            <input type="range" min="0" max="{{ $maxCreditLimit }}" step="{{ $creditLimitStep }}" value="{{ $creditLimitValue }}" data-filter-range="credit_limit" class="credit-filters__range">
        </div>

        <div class="credit-filters__field">
            <label class="credit-filters__label" for="card-filter-annual-fee">Стоимость обслуживания до</label>
            <div class="credit-filters__pair">
                <input id="card-filter-annual-fee" type="text" inputmode="numeric" data-filter-number="annual_fee" data-min="0" data-max="{{ $maxAnnualFee }}" value="{{ $annualFeeValue }}" class="credit-filters__input" placeholder="0">
                <span class="credit-filters__suffix">₽</span>
            </div>
            <input type="range" min="0" max="{{ $maxAnnualFee }}" step="{{ $annualFeeStep }}" value="{{ $annualFeeValue }}" data-filter-range="annual_fee" class="credit-filters__range">
        </div>

        <div class="credit-filters__field">
            <label class="credit-filters__label" for="card-filter-psk">ПСК до</label>
            <div class="credit-filters__pair">
                <input id="card-filter-psk" type="number" min="0" max="{{ $maxPsk }}" step="0.01" value="{{ $pskValue }}" data-filter-number="psk" class="credit-filters__input credit-filters__input--no-spinner">
                <span class="credit-filters__suffix">%</span>
            </div>
            <input type="range" min="0" max="{{ $maxPsk }}" step="0.01" value="{{ $pskValue }}" data-filter-range="psk" class="credit-filters__range">
        </div>

        <div class="credit-filters__field">
            <label class="credit-filters__label" for="card-filter-cashback">Кэшбэк от</label>
            <div class="credit-filters__pair">
                <input id="card-filter-cashback" type="number" min="0" max="{{ $maxCashback }}" step="0.01" value="{{ $cashbackValue }}" data-filter-number="cashback" class="credit-filters__input credit-filters__input--no-spinner">
                <span class="credit-filters__suffix">%</span>
            </div>
            <input type="range" min="0" max="{{ $maxCashback }}" step="0.01" value="{{ $cashbackValue }}" data-filter-range="cashback" class="credit-filters__range">
        </div>
    </div>

    <div class="credit-filters__actions">
        <button type="button" class="tf-btn btn-primary2 btn-px-28 height-2 rounded-12" data-filter-apply>
            <span>Подобрать</span>
            <span class="bg-effect"></span>
        </button>
        <button type="button" class="tf-btn btn-border border-1 height-2" data-filter-reset>
            <span>Сбросить фильтр</span>
            <span class="bg-effect"></span>
        </button>
    </div>
</div>

@once
    @push('styles')
        <style>
            .credit-filters {
                padding: 24px;
                border: 1px solid rgba(25, 20, 0, 0.08);
                border-radius: 20px;
                background: #fff;
                box-shadow: 0 10px 30px rgba(17, 24, 39, 0.04);
            }
            .credit-filters__grid {
                display: grid;
                gap: 18px;
            }
            .credit-filters__grid--cards {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
            .credit-filters__field {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .credit-filters__label {
                font-size: 14px;
                font-weight: 600;
                color: #191400;
            }
            .credit-filters__pair {
                position: relative;
            }
            .credit-filters__input {
                width: 100%;
                height: 52px;
                padding: 0 44px 0 16px;
                border: 1px solid rgba(25, 20, 0, 0.14);
                border-radius: 14px;
                background: #fff;
                color: #191400;
            }
            .credit-filters__input--no-spinner {
                -moz-appearance: textfield;
            }
            .credit-filters__input--no-spinner::-webkit-outer-spin-button,
            .credit-filters__input--no-spinner::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            .credit-filters__suffix {
                position: absolute;
                top: 50%;
                right: 14px;
                transform: translateY(-50%);
                font-size: 13px;
                color: #706f6c;
                pointer-events: none;
            }
            .credit-filters__range {
                width: 95%;
                margin: -14px 0 0 7px;
                z-index: 2;
                height: 4px;
                accent-color: #f97316;
            }
            .credit-filters__actions {
                display: flex;
                justify-content: flex-end;
                gap: 12px;
                margin-top: 18px;
            }
            @media (max-width: 1199px) {
                .credit-filters__grid--cards {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }
            }
            @media (max-width: 767px) {
                .credit-filters {
                    padding: 18px;
                }
                .credit-filters__grid--cards {
                    grid-template-columns: 1fr;
                }
                .credit-filters__actions {
                    justify-content: stretch;
                    flex-wrap: wrap;
                }
                .credit-filters__actions .tf-btn {
                    flex: 1;
                    min-width: 0;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-card-filters]').forEach(function (filtersRoot) {
                    const controls = {
                        gracePeriodNumber: filtersRoot.querySelector('[data-filter-number="grace_period"]'),
                        gracePeriodRange: filtersRoot.querySelector('[data-filter-range="grace_period"]'),
                        creditLimitNumber: filtersRoot.querySelector('[data-filter-number="credit_limit"]'),
                        creditLimitRange: filtersRoot.querySelector('[data-filter-range="credit_limit"]'),
                        annualFeeNumber: filtersRoot.querySelector('[data-filter-number="annual_fee"]'),
                        annualFeeRange: filtersRoot.querySelector('[data-filter-range="annual_fee"]'),
                        pskNumber: filtersRoot.querySelector('[data-filter-number="psk"]'),
                        pskRange: filtersRoot.querySelector('[data-filter-range="psk"]'),
                        cashbackNumber: filtersRoot.querySelector('[data-filter-number="cashback"]'),
                        cashbackRange: filtersRoot.querySelector('[data-filter-range="cashback"]'),
                        applyBtn: filtersRoot.querySelector('[data-filter-apply]'),
                        resetBtn: filtersRoot.querySelector('[data-filter-reset]'),
                    };

                    const normalizeNumber = (value, fallback = 0) => {
                        if (value === '' || value === null || value === undefined) return fallback;
                        const str = String(value).replace(/\s/g, '');
                        const number = Number(str);
                        return Number.isFinite(number) ? number : fallback;
                    };

                    const formatWithSpaces = (num) => {
                        if (!Number.isFinite(num)) return '0';
                        return String(Math.round(num)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
                    };

                    const syncPair = (numberInput, rangeInput, isInteger, source = 'input', formatDisplay = true) => {
                        if (!numberInput || !rangeInput) return 0;
                        const raw = source === 'range'
                            ? (rangeInput.value ?? '')
                            : (numberInput.type === 'text' ? numberInput.value.replace(/[^\d]/g, '') : numberInput.value);
                        const max = normalizeNumber(rangeInput.max, 0);
                        const min = normalizeNumber(rangeInput.min, 0);
                        const parsed = normalizeNumber(raw, 0);
                        const clamped = Math.min(Math.max(parsed, min), max);
                        const value = isInteger ? Math.round(clamped) : Math.round(clamped * 100) / 100;

                        rangeInput.value = String(value);
                        if (numberInput.type === 'text') {
                            numberInput.value = formatDisplay ? formatWithSpaces(value) : String(value);
                        } else {
                            numberInput.value = String(value);
                        }

                        return value;
                    };

                    [
                        [controls.gracePeriodNumber, controls.gracePeriodRange, true],
                        [controls.creditLimitNumber, controls.creditLimitRange, true],
                        [controls.annualFeeNumber, controls.annualFeeRange, true],
                        [controls.pskNumber, controls.pskRange, false],
                        [controls.cashbackNumber, controls.cashbackRange, false],
                    ].forEach(function ([numberInput, rangeInput, isInteger]) {
                        if (!numberInput || !rangeInput) return;
                        numberInput.addEventListener('input', function () {
                            syncPair(numberInput, rangeInput, isInteger, 'input', false);
                        });
                        numberInput.addEventListener('change', function () {
                            syncPair(numberInput, rangeInput, isInteger, 'input', true);
                        });
                        numberInput.addEventListener('blur', function () {
                            syncPair(numberInput, rangeInput, isInteger, 'input', true);
                        });
                        rangeInput.addEventListener('input', function () {
                            syncPair(numberInput, rangeInput, isInteger, 'range', true);
                        });
                    });

                    if (controls.applyBtn) {
                        controls.applyBtn.addEventListener('click', function () {
                            const gracePeriod = syncPair(controls.gracePeriodNumber, controls.gracePeriodRange, true);
                            const creditLimit = syncPair(controls.creditLimitNumber, controls.creditLimitRange, true);
                            const annualFee = syncPair(controls.annualFeeNumber, controls.annualFeeRange, true);
                            const psk = syncPair(controls.pskNumber, controls.pskRange, false);
                            const cashback = syncPair(controls.cashbackNumber, controls.cashbackRange, false);
                            const url = new URL(window.location.href);

                            ['grace_period', 'credit_limit', 'annual_fee', 'psk', 'cashback', 'page'].forEach(function (key) {
                                url.searchParams.delete(key);
                            });

                            if (gracePeriod > 0) url.searchParams.set('grace_period', String(gracePeriod));
                            if (creditLimit > 0) url.searchParams.set('credit_limit', String(creditLimit));
                            if (annualFee > 0) url.searchParams.set('annual_fee', String(annualFee));
                            if (psk > 0) url.searchParams.set('psk', String(psk));
                            if (cashback > 0) url.searchParams.set('cashback', String(cashback));

                            window.location.href = url.toString();
                        });
                    }

                    if (controls.resetBtn) {
                        controls.resetBtn.addEventListener('click', function () {
                            const url = new URL(window.location.href);
                            ['grace_period', 'credit_limit', 'annual_fee', 'psk', 'cashback', 'page'].forEach(function (key) {
                                url.searchParams.delete(key);
                            });
                            window.location.href = url.pathname + (url.searchParams.toString() ? '?' + url.searchParams.toString() : '');
                        });
                    }
                });
            });
        </script>
    @endpush
@endonce
