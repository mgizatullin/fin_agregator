@php
    $request = request();
    $creditsItems = isset($items) && method_exists($items, 'getCollection')
        ? $items->getCollection()
        : collect($items ?? []);
    $maxAmountFilter = (int) ceil((float) $creditsItems->max('max_amount'));
    $amountRangeStep = $maxAmountFilter > 0 ? max(1000, (int) ceil($maxAmountFilter / 100)) : 1000;
    $maxTermFilter = (int) ceil((float) $creditsItems->max('term_months'));
    $maxRateFilter = round((float) $creditsItems->max('rate'), 2);
    $maxPskFilter = round((float) $creditsItems->max('psk'), 2);
    $receiveMethodOptions = $creditsItems
        ->flatMap(fn ($credit) => $credit->relationLoaded('receiveMethods') ? $credit->receiveMethods : collect())
        ->unique('id')
        ->sortBy('name')
        ->values();
    $receiveMethodCounts = [];
    foreach ($receiveMethodOptions as $method) {
        $receiveMethodCounts[$method->id] = $creditsItems->filter(function ($credit) use ($method) {
            return $credit->relationLoaded('receiveMethods') && $credit->receiveMethods->contains('id', $method->id);
        })->count();
    }
    $selectedReceiveMethods = collect((array) $request->input('receive_methods', []))
        ->map(fn ($value) => (string) $value)
        ->filter()
        ->values()
        ->all();
    $amountValue = max(0, (int) $request->integer('amount', 0));
    $termValue = max(0, (int) $request->integer('term', 0));
    $rateValue = max(0, (float) $request->input('rate', 0));
    $pskValue = max(0, (float) $request->input('psk', 0));
@endphp

<div
    class="credit-filters mb_32"
    data-credit-filters
    data-summary-mode="{{ $summaryMode ?? 'index' }}"
    data-category-label="{{ $categoryLabel ?? '' }}"
>
    <div class="credit-filters__grid">
        <div class="credit-filters__field">
            <label class="credit-filters__label" for="credit-filter-amount">Сумма кредита</label>
            <div class="credit-filters__pair">
                <input id="credit-filter-amount" type="text" inputmode="numeric" data-filter-number="amount" data-min="0" data-max="{{ max(0, $maxAmountFilter) }}" value="{{ $amountValue }}" class="credit-filters__input" placeholder="0">
                <span class="credit-filters__suffix">₽</span>
            </div>
            <input type="range" min="0" max="{{ max(0, $maxAmountFilter) }}" step="{{ $amountRangeStep }}" value="{{ $amountValue }}" data-filter-range="amount" class="credit-filters__range">
        </div>

        <div class="credit-filters__field">
            <label class="credit-filters__label" for="credit-filter-term">Срок кредита</label>
            <div class="credit-filters__pair">
                <input id="credit-filter-term" type="text" inputmode="numeric" data-filter-number="term" data-min="0" data-max="{{ max(0, $maxTermFilter) }}" value="{{ $termValue }}" class="credit-filters__input" placeholder="0">
                <span class="credit-filters__suffix">мес.</span>
            </div>
            <input type="range" min="0" max="{{ max(0, $maxTermFilter) }}" step="1" value="{{ $termValue }}" data-filter-range="term" class="credit-filters__range">
        </div>

        <div class="credit-filters__field">
            <label class="credit-filters__label" for="credit-filter-rate">Ставка до</label>
            <div class="credit-filters__pair">
                <input id="credit-filter-rate" type="number" min="0" max="{{ max(0, $maxRateFilter) }}" step="0.01" value="{{ $rateValue }}" data-filter-number="rate" class="credit-filters__input credit-filters__input--no-spinner">
                <span class="credit-filters__suffix">%</span>
            </div>
            <input type="range" min="0" max="{{ max(0, $maxRateFilter) }}" step="0.01" value="{{ $rateValue }}" data-filter-range="rate" class="credit-filters__range">
        </div>

        <div class="credit-filters__field">
            <label class="credit-filters__label" for="credit-filter-psk">ПСК до</label>
            <div class="credit-filters__pair">
                <input id="credit-filter-psk" type="number" min="0" max="{{ max(0, $maxPskFilter) }}" step="0.01" value="{{ $pskValue }}" data-filter-number="psk" class="credit-filters__input credit-filters__input--no-spinner">
                <span class="credit-filters__suffix">%</span>
            </div>
            <input type="range" min="0" max="{{ max(0, $maxPskFilter) }}" step="0.01" value="{{ $pskValue }}" data-filter-range="psk" class="credit-filters__range">
        </div>

        <div class="credit-filters__field credit-filters__field--dropdown">
            <label class="credit-filters__label">Способ получения</label>
            <select id="credit-filter-receive-methods" multiple data-filter-select="receive-methods" class="credit-filters__select-hidden" aria-hidden="true" tabindex="-1">
                @foreach($receiveMethodOptions as $method)
                    <option value="{{ $method->id }}" @selected(in_array((string) $method->id, $selectedReceiveMethods, true))>{{ $method->name }}</option>
                @endforeach
            </select>
            <div class="credit-filters__dropdown" data-receive-dropdown>
                <button type="button" class="credit-filters__dropdown-trigger" data-receive-dropdown-trigger aria-expanded="false" aria-haspopup="listbox">
                    <span class="credit-filters__dropdown-label">Выберите способы</span>
                    <span class="credit-filters__dropdown-arrow" aria-hidden="true"></span>
                </button>
                <div class="credit-filters__dropdown-panel" role="listbox" hidden>
                    @foreach($receiveMethodOptions as $method)
                        @php $count = $receiveMethodCounts[$method->id] ?? 0; @endphp
                        <div class="credit-filters__checkbox" data-value="{{ $method->id }}" role="option" tabindex="0">
                            <span class="credit-filters__checkbox-display"></span>
                            <span class="credit-filters__checkbox-label">{{ $method->name }}</span>
                            <span class="credit-filters__checkbox-counter">({{ $count }})</span>
                        </div>
                    @endforeach
                </div>
            </div>
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
                grid-template-columns: repeat(5, minmax(0, 1fr));
                gap: 18px;
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
            .credit-filters__input,
            .credit-filters__select-hidden {
                width: 100%;
                border: 1px solid rgba(25, 20, 0, 0.14);
                border-radius: 14px;
                background: #fff;
                color: #191400;
            }
            .credit-filters__input {
                height: 52px;
                padding: 0 44px 0 16px;
            }
            .credit-filters__input--no-spinner {
                -moz-appearance: textfield;
            }
            .credit-filters__input--no-spinner::-webkit-outer-spin-button,
            .credit-filters__input--no-spinner::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            .credit-filters__select-hidden {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                border: 0;
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
            .credit-filters__dropdown {
                position: relative;
            }
            .credit-filters__dropdown-trigger {
                width: 100%;
                height: 52px;
                padding: 0 40px 0 16px;
                border: 1px solid rgba(25, 20, 0, 0.14);
                border-radius: 14px;
                background: #fff;
                color: #191400;
                font-size: inherit;
                text-align: left;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: space-between;
                position: relative;
            }
            .credit-filters__dropdown-trigger:hover {
                border-color: rgba(25, 20, 0, 0.24);
            }
            .credit-filters__dropdown-arrow {
                position: absolute;
                right: 14px;
                top: 50%;
                transform: translateY(-50%);
                width: 0;
                height: 0;
                border-left: 5px solid transparent;
                border-right: 5px solid transparent;
                border-top: 6px solid #706f6c;
            }
            .credit-filters__dropdown-trigger[aria-expanded="true"] .credit-filters__dropdown-arrow {
                border-top: none;
                border-bottom: 6px solid #706f6c;
            }
            .credit-filters__dropdown-panel {
                position: absolute;
                left: 0;
                right: 0;
                top: 100%;
                margin-top: 4px;
                padding: 8px;
                background: #fff;
                border: 1px solid rgba(25, 20, 0, 0.14);
                border-radius: 14px;
                box-shadow: 0 10px 30px rgba(17, 24, 39, 0.12);
                z-index: 10;
                max-height: 240px;
                overflow-y: auto;
            }
            .credit-filters__checkbox {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 10px 12px;
                cursor: pointer;
                border-radius: 10px;
                position: relative;
                padding-left: 36px;
            }
            .credit-filters__checkbox:hover {
                background: rgba(25, 20, 0, 0.04);
            }
            .credit-filters__checkbox-display {
                position: absolute;
                left: 0;
                top: 50%;
                transform: translateY(-50%);
                width: 18px;
                height: 18px;
                border: 1px solid rgba(25, 20, 0, 0.24);
                border-radius: 4px;
                background: #fff;
            }
            .credit-filters__checkbox.checked .credit-filters__checkbox-display {
                background: #0075ff;
                border-color: #0075ff;
            }
            .credit-filters__checkbox.checked .credit-filters__checkbox-display::after {
                content: "";
                position: absolute;
                left: 5px;
                top: 2px;
                width: 4px;
                height: 8px;
                border: solid #fff;
                border-width: 0 2px 2px 0;
                transform: rotate(45deg);
            }
            .credit-filters__checkbox-label {
                flex: 1;
            }
            .credit-filters__checkbox-counter {
                font-size: 13px;
                color: #706f6c;
            }
            .credit-filters__actions {
                display: flex;
                justify-content: flex-end;
                gap: 12px;
                margin-top: 18px;
            }
            @media (max-width: 1199px) {
                .credit-filters__grid {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }
                .credit-filters__field--dropdown {
                    grid-column: span 3;
                }
            }
            @media (max-width: 767px) {
                .credit-filters {
                    padding: 18px;
                }
                .credit-filters__grid {
                    grid-template-columns: 1fr;
                }
                .credit-filters__field--dropdown {
                    grid-column: span 1;
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
                document.querySelectorAll('[data-credit-filters]').forEach(function (filtersRoot) {
                    const controls = {
                        amountNumber: filtersRoot.querySelector('[data-filter-number="amount"]'),
                        amountRange: filtersRoot.querySelector('[data-filter-range="amount"]'),
                        termNumber: filtersRoot.querySelector('[data-filter-number="term"]'),
                        termRange: filtersRoot.querySelector('[data-filter-range="term"]'),
                        rateNumber: filtersRoot.querySelector('[data-filter-number="rate"]'),
                        rateRange: filtersRoot.querySelector('[data-filter-range="rate"]'),
                        pskNumber: filtersRoot.querySelector('[data-filter-number="psk"]'),
                        pskRange: filtersRoot.querySelector('[data-filter-range="psk"]'),
                        receiveMethods: filtersRoot.querySelector('[data-filter-select="receive-methods"]'),
                        applyBtn: filtersRoot.querySelector('[data-filter-apply]'),
                        reset: filtersRoot.querySelector('[data-filter-reset]'),
                    };

                    const dropdown = filtersRoot.querySelector('[data-receive-dropdown]');
                    const dropdownTrigger = filtersRoot.querySelector('[data-receive-dropdown-trigger]');
                    const dropdownPanel = dropdown?.querySelector('.credit-filters__dropdown-panel');
                    const dropdownLabel = filtersRoot.querySelector('.credit-filters__dropdown-label');
                    const checkboxes = dropdown ? Array.from(dropdown.querySelectorAll('.credit-filters__checkbox')) : [];

                    const normalizeNumber = (value, fallback = 0) => {
                        if (value === '' || value === null || value === undefined) return fallback;
                        const str = String(value).replace(/\s/g, '');
                        const number = Number(str);
                        return Number.isFinite(number) ? number : fallback;
                    };

                    const formatWithSpaces = (num, isInteger) => {
                        if (!Number.isFinite(num)) return '0';
                        const n = isInteger ? Math.round(num) : num;
                        const parts = String(n).split('.');
                        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
                        return parts.join('.');
                    };

                    const getInputNumeric = (input, rangeInput, source = 'input') => {
                        const raw = source === 'range'
                            ? (rangeInput?.value ?? '')
                            : (input && input.type === 'text'
                                ? (input.value || '').replace(/[^\d]/g, '')
                                : (input?.value ?? ''));
                        const num = normalizeNumber(raw, 0);
                        const max = normalizeNumber(rangeInput?.max ?? input?.dataset?.max ?? 0);
                        const min = normalizeNumber(rangeInput?.min ?? input?.dataset?.min ?? 0);
                        const clamped = Math.min(Math.max(num, min), max);
                        return input && input.type === 'text'
                            ? Math.round(clamped)
                            : Math.round(clamped * 100) / 100;
                    };

                    const syncPair = (numberInput, rangeInput, isInteger, formatDisplay = true, source = 'input') => {
                        const max = normalizeNumber(rangeInput?.max ?? numberInput?.dataset?.max ?? 0);
                        const current = getInputNumeric(numberInput, rangeInput, source);
                        const clamped = Math.min(Math.max(current, 0), max);

                        if (rangeInput) {
                            rangeInput.value = clamped;
                        }

                        if (numberInput) {
                            if (numberInput.type === 'text') {
                                if (!formatDisplay) {
                                    numberInput.value = String(Math.round(clamped));
                                } else {
                                    numberInput.value = formatWithSpaces(clamped, isInteger);
                                }
                            } else {
                                numberInput.value = clamped;
                            }
                        }

                        return clamped;
                    };

                    const pairs = [
                        [controls.amountNumber, controls.amountRange, true],
                        [controls.termNumber, controls.termRange, true],
                        [controls.rateNumber, controls.rateRange, false],
                        [controls.pskNumber, controls.pskRange, false],
                    ];

                    pairs.forEach(([numEl, rangeEl, isInt]) => {
                        if (!numEl || !rangeEl) return;
                        numEl.addEventListener('input', function () {
                            syncPair(numEl, rangeEl, isInt, false, 'input');
                        });
                        numEl.addEventListener('change', function () {
                            syncPair(numEl, rangeEl, isInt, true, 'input');
                        });
                        numEl.addEventListener('blur', function () {
                            syncPair(numEl, rangeEl, isInt, true, 'input');
                        });
                        rangeEl.addEventListener('input', function () {
                            syncPair(numEl, rangeEl, isInt, true, 'range');
                        });
                        rangeEl.addEventListener('change', function () {
                            syncPair(numEl, rangeEl, isInt, true, 'range');
                        });
                    });

                    if (controls.applyBtn) {
                        controls.applyBtn.addEventListener('click', function () {
                            const amount = syncPair(controls.amountNumber, controls.amountRange, true);
                            const term = syncPair(controls.termNumber, controls.termRange, true);
                            const rate = syncPair(controls.rateNumber, controls.rateRange, false);
                            const psk = syncPair(controls.pskNumber, controls.pskRange, false);
                            const selectedReceiveMethods = controls.receiveMethods
                                ? Array.from(controls.receiveMethods.selectedOptions).map((opt) => opt.value).filter(Boolean)
                                : [];
                            const url = new URL(window.location.href);

                            ['amount', 'term', 'rate', 'psk'].forEach(function (key) {
                                url.searchParams.delete(key);
                            });
                            url.searchParams.delete('receive_methods');
                            url.searchParams.delete('receive_methods[]');
                            url.searchParams.delete('page');

                            if (amount > 0) {
                                url.searchParams.set('amount', String(amount));
                            }
                            if (term > 0) {
                                url.searchParams.set('term', String(term));
                            }
                            if (rate > 0) {
                                url.searchParams.set('rate', String(rate));
                            }
                            if (psk > 0) {
                                url.searchParams.set('psk', String(psk));
                            }

                            selectedReceiveMethods.forEach(function (value) {
                                url.searchParams.append('receive_methods[]', value);
                            });

                            window.location.href = url.toString();
                        });
                    }

                    if (controls.reset) {
                        controls.reset.addEventListener('click', function () {
                            const url = new URL(window.location.href);
                            ['amount', 'term', 'rate', 'psk', 'page'].forEach(function (key) {
                                url.searchParams.delete(key);
                            });
                            url.searchParams.delete('receive_methods');
                            url.searchParams.delete('receive_methods[]');
                            window.location.href = url.pathname + (url.searchParams.toString() ? '?' + url.searchParams.toString() : '');
                        });
                    }

                    if (dropdownTrigger && dropdownPanel) {
                        dropdownTrigger.addEventListener('click', function () {
                            const open = dropdownPanel.hidden;
                            dropdownPanel.hidden = !open;
                            dropdownTrigger.setAttribute('aria-expanded', open ? 'true' : 'false');
                        });

                        document.addEventListener('click', function (e) {
                            if (!dropdown.contains(e.target)) {
                                dropdownPanel.hidden = true;
                                dropdownTrigger.setAttribute('aria-expanded', 'false');
                            }
                        });
                    }

                    checkboxes.forEach((ch) => {
                        const value = ch.dataset.value;
                        const option = controls.receiveMethods?.querySelector('option[value="' + value + '"]');
                        if (option && option.selected) ch.classList.add('checked');

                        ch.addEventListener('click', function () {
                            if (!option) return;
                            option.selected = !option.selected;
                            ch.classList.toggle('checked', option.selected);
                            const selected = Array.from(controls.receiveMethods.selectedOptions).map((o) => o.textContent);
                            if (dropdownLabel) {
                                dropdownLabel.textContent = selected.length ? selected.join(', ') : 'Выберите способы';
                            }
                        });
                    });

                    if (controls.receiveMethods && dropdownLabel) {
                        const updateLabel = () => {
                            const selected = Array.from(controls.receiveMethods.selectedOptions).map((o) => o.textContent);
                            dropdownLabel.textContent = selected.length ? selected.join(', ') : 'Выберите способы';
                        };
                        updateLabel();
                    }
                });
            });
        </script>
    @endpush
@endonce
