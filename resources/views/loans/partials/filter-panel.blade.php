@php
    $request = request();
    $meta = $filterMeta ?? [];

    $maxAmount = max(0, (int) ($meta['max_amount'] ?? 0));
    $maxTerm = max(0, (int) ($meta['max_term'] ?? 0));
    $maxTermNoInterest = max(0, (int) ($meta['max_term_no_interest'] ?? 0));
    $maxRate = max(0, (float) ($meta['max_rate'] ?? 0));
    $maxPsk = max(0, (float) ($meta['max_psk'] ?? 0));

    $amountStep = $maxAmount > 0 ? max(1000, (int) ceil($maxAmount / 100)) : 1000;

    $amountValue = max(0, (int) $request->integer('amount', 0));
    $termValue = max(0, (int) $request->integer('term', 0));
    $termNoInterestValue = max(0, (int) $request->integer('term_no_interest', 0));
    $rateValue = max(0, (float) $request->input('rate', 0));
    $pskValue = max(0, (float) $request->input('psk', 0));
@endphp

<div class="credit-filters mb_32" data-loan-filters>
    <div class="credit-filters__grid credit-filters__grid--loans">
        <div class="credit-filters__field">
            <label class="credit-filters__label" for="loan-filter-amount">Сумма от</label>
            <div class="credit-filters__pair">
                <input id="loan-filter-amount" type="text" inputmode="numeric" data-filter-number="amount" data-min="0" data-max="{{ $maxAmount }}" value="{{ $amountValue }}" class="credit-filters__input" placeholder="0">
                <span class="credit-filters__suffix">₽</span>
            </div>
            <input type="range" min="0" max="{{ $maxAmount }}" step="{{ $amountStep }}" value="{{ $amountValue }}" data-filter-range="amount" class="credit-filters__range">
        </div>

        <div class="credit-filters__field">
            <label class="credit-filters__label" for="loan-filter-term">Срок от</label>
            <div class="credit-filters__pair">
                <input id="loan-filter-term" type="text" inputmode="numeric" data-filter-number="term" data-min="0" data-max="{{ $maxTerm }}" value="{{ $termValue }}" class="credit-filters__input" placeholder="0">
                <span class="credit-filters__suffix">дн.</span>
            </div>
            <input type="range" min="0" max="{{ $maxTerm }}" step="1" value="{{ $termValue }}" data-filter-range="term" class="credit-filters__range">
        </div>

        <div class="credit-filters__field">
            <label class="credit-filters__label" for="loan-filter-term-no-interest">Срок без процентов от</label>
            <div class="credit-filters__pair">
                <input id="loan-filter-term-no-interest" type="text" inputmode="numeric" data-filter-number="term_no_interest" data-min="0" data-max="{{ $maxTermNoInterest }}" value="{{ $termNoInterestValue }}" class="credit-filters__input" placeholder="0">
                <span class="credit-filters__suffix">дн.</span>
            </div>
            <input type="range" min="0" max="{{ $maxTermNoInterest }}" step="1" value="{{ $termNoInterestValue }}" data-filter-range="term_no_interest" class="credit-filters__range">
        </div>

        <div class="credit-filters__field">
            <label class="credit-filters__label" for="loan-filter-rate">Ставка до</label>
            <div class="credit-filters__pair">
                <input id="loan-filter-rate" type="number" min="0" max="{{ $maxRate }}" step="0.01" value="{{ $rateValue }}" data-filter-number="rate" class="credit-filters__input credit-filters__input--no-spinner">
                <span class="credit-filters__suffix">%</span>
            </div>
            <input type="range" min="0" max="{{ $maxRate }}" step="0.01" value="{{ $rateValue }}" data-filter-range="rate" class="credit-filters__range">
        </div>

        <div class="credit-filters__field">
            <label class="credit-filters__label" for="loan-filter-psk">ПСК до</label>
            <div class="credit-filters__pair">
                <input id="loan-filter-psk" type="number" min="0" max="{{ $maxPsk }}" step="0.01" value="{{ $pskValue }}" data-filter-number="psk" class="credit-filters__input credit-filters__input--no-spinner">
                <span class="credit-filters__suffix">%</span>
            </div>
            <input type="range" min="0" max="{{ $maxPsk }}" step="0.01" value="{{ $pskValue }}" data-filter-range="psk" class="credit-filters__range">
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
            .credit-filters__grid--loans {
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
                .credit-filters__grid--loans {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }
            }
            @media (max-width: 767px) {
                .credit-filters {
                    padding: 18px;
                }
                .credit-filters__grid--loans {
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
                document.querySelectorAll('[data-loan-filters]').forEach(function (filtersRoot) {
                    const controls = {
                        amountNumber: filtersRoot.querySelector('[data-filter-number="amount"]'),
                        amountRange: filtersRoot.querySelector('[data-filter-range="amount"]'),
                        termNumber: filtersRoot.querySelector('[data-filter-number="term"]'),
                        termRange: filtersRoot.querySelector('[data-filter-range="term"]'),
                        termNoInterestNumber: filtersRoot.querySelector('[data-filter-number="term_no_interest"]'),
                        termNoInterestRange: filtersRoot.querySelector('[data-filter-range="term_no_interest"]'),
                        rateNumber: filtersRoot.querySelector('[data-filter-number="rate"]'),
                        rateRange: filtersRoot.querySelector('[data-filter-range="rate"]'),
                        pskNumber: filtersRoot.querySelector('[data-filter-number="psk"]'),
                        pskRange: filtersRoot.querySelector('[data-filter-range="psk"]'),
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
                        [controls.amountNumber, controls.amountRange, true],
                        [controls.termNumber, controls.termRange, true],
                        [controls.termNoInterestNumber, controls.termNoInterestRange, true],
                        [controls.rateNumber, controls.rateRange, false],
                        [controls.pskNumber, controls.pskRange, false],
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
                            const amount = syncPair(controls.amountNumber, controls.amountRange, true);
                            const term = syncPair(controls.termNumber, controls.termRange, true);
                            const termNoInterest = syncPair(controls.termNoInterestNumber, controls.termNoInterestRange, true);
                            const rate = syncPair(controls.rateNumber, controls.rateRange, false);
                            const psk = syncPair(controls.pskNumber, controls.pskRange, false);
                            const url = new URL(window.location.href);

                            ['amount', 'term', 'term_no_interest', 'rate', 'psk', 'page'].forEach(function (key) {
                                url.searchParams.delete(key);
                            });

                            if (amount > 0) url.searchParams.set('amount', String(amount));
                            if (term > 0) url.searchParams.set('term', String(term));
                            if (termNoInterest > 0) url.searchParams.set('term_no_interest', String(termNoInterest));
                            if (rate > 0) url.searchParams.set('rate', String(rate));
                            if (psk > 0) url.searchParams.set('psk', String(psk));

                            window.location.href = url.toString();
                        });
                    }

                    if (controls.resetBtn) {
                        controls.resetBtn.addEventListener('click', function () {
                            const url = new URL(window.location.href);
                            ['amount', 'term', 'term_no_interest', 'rate', 'psk', 'page'].forEach(function (key) {
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
