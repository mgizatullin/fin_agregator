@php
    $data = $currencyRatesWithChange ?? ['date' => null, 'date_label' => '', 'rates' => []];
    $ratesList = $data['rates'] ?? [];
    $dateLabel = $data['date_label'] ?? '';
    $flagCodes = ['USD' => 'us', 'EUR' => 'eu', 'CNY' => 'cn', 'GBP' => 'gb', 'CHF' => 'ch', 'JPY' => 'jp'];
@endphp
@if(!empty($ratesList))
@push('styles')
<style>
.section-currency-rates .currency-rates-widget { width: 35%; min-width: 280px; }
.section-currency-rates .currency-calc-box { background: transparent; border-radius: 0; padding: 0; border: none; }
.section-currency-rates .cbr-rates-box { border: none; background: #f2f6ff; border-radius: 24px; padding: 32px; }
.section-currency-rates .cbr-rates-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem 7rem; }
.section-currency-rates .cbr-rate-row { border-bottom: none; padding: 0.35rem 0; }
.section-currency-rates .currency-change { font-size: 0.875rem; font-weight: 600; }
.section-currency-rates .currency-change.text-success { color: #198754; }
.section-currency-rates .currency-change.text-danger { color: #dc3545; }
.section-currency-rates .currency-calc-amount,
.section-currency-rates .currency-calc-result,
.section-currency-rates .cbr-rate-value { font-size: 1.75rem; font-weight: 600; }
.section-currency-rates .currency-calc-source { position: relative; cursor: pointer; user-select: none; }
.section-currency-rates .currency-calc-trigger { display: flex; align-items: center; gap: 0.5rem; font-size: 1.75rem; font-weight: 600; }
.section-currency-rates .currency-calc-trigger .currency-calc-arrow { transition: transform 0.2s; }
.section-currency-rates .currency-calc-source.is-open .currency-calc-arrow { transform: rotate(180deg); }
.section-currency-rates .currency-calc-dropdown { position: absolute; left: 0; right: 0; top: 100%; margin-top: 4px; background: #fff; border: 1px solid rgba(0,0,0,.1); border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,.15); z-index: 100; max-height: 220px; overflow-y: auto; display: none; }
.section-currency-rates .currency-calc-source.is-open .currency-calc-dropdown { display: block; }
.section-currency-rates .currency-calc-option { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; cursor: pointer; font-size: 1.1rem; }
.section-currency-rates .currency-calc-option:hover { background: #f0f0f0; }
.section-currency-rates input.currency-calc-amount::-webkit-outer-spin-button,
.section-currency-rates input.currency-calc-amount::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.section-currency-rates input.currency-calc-amount[type="number"] { -moz-appearance: textfield; }
@media (max-width: 991px) { .section-currency-rates .currency-rates-widget { width: 100%; } }
</style>
@endpush
<section class="section-currency-rates tf-spacing-20" id="currency-rates-widget" aria-label="Курсы валют ЦБ РФ">
    <div class="tf-container-2">
        <div class="currency-rates-widget">
            <div class="cbr-rates-box">
                <h6 class="text_mono-dark-9 fw-6 mb-1">Курсы валют к рублю</h6>
                @if($dateLabel)
                <p class="text-body-3 text_mono-gray-6 mb_16">{{ $dateLabel }}</p>
                @endif
                <div class="cbr-rates-table">
                    <div class="cbr-rates-grid">
                    @foreach($ratesList as $r)
                    <div class="d-flex align-items-center justify-content-between py-2 cbr-rate-row">
                        <div class="d-flex align-items-center gap-2">
                            <img src="https://flagcdn.com/w40/{{ $flagCodes[$r['code']] ?? 'us' }}.png" alt="" width="24" height="18" class="rounded" style="object-fit: cover;">
                            <span class="text-body-2 fw-6 text_mono-dark-9">{{ $r['code'] }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="cbr-rate-value text_mono-dark-9">{{ $r['rate'] !== null ? number_format((float)$r['rate'], 2, ',', ' ') : '—' }} ₽</span>
                            @if(isset($r['change']) && $r['change'] !== null)
                            <span class="currency-change {{ $r['change_positive'] ? 'text-success' : 'text-danger' }}" title="Изменение за 24 ч">
                                @if($r['change_positive'])<span aria-hidden="true">↗</span>@else<span aria-hidden="true">↘</span>@endif
                                {{ number_format((float)$r['change'], 2, ',', ' ') }}
                            </span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                    </div>
                </div>
                <a href="{{ url_canonical(route('currency.calculator')) }}" class="btn btn-link link-primary p-0 mt-2 text-body-3 d-block text-end">Все курсы валют</a>

                <div class="currency-calc-box mt-4 pt-4" style="border-top: 1px solid rgba(0,0,0,.08);">
                    <h6 class="text_mono-dark-9 fw-6 mb-1">Быстрый расчёт</h6>
                    <div class="d-flex flex-wrap align-items-center gap_12 mt-3">
                        <div class="currency-calc-source d-flex align-items-center border rounded-3 px-3 py-2 bg-white" id="currency-calc-source" role="combobox" aria-expanded="false" aria-haspopup="listbox" aria-label="Валюта">
                            <select id="currency-calc-from" class="d-none" aria-hidden="true" tabindex="-1" aria-label="Валюта">
                                @foreach($ratesList as $r)
                                    @if($r['rate'] !== null)
                                    <option value="{{ $r['code'] }}" data-rate="{{ $r['rate'] }}" {{ $loop->first ? 'selected' : '' }}>{{ $r['code'] }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <div class="currency-calc-trigger" id="currency-calc-trigger">
                                <span class="currency-calc-trigger-code" id="currency-calc-trigger-code">{{ $ratesList[0]['code'] ?? 'USD' }}</span>
                                <img id="currency-calc-flag" src="https://flagcdn.com/w40/{{ $flagCodes[$ratesList[0]['code'] ?? 'USD'] ?? 'us' }}.png" alt="" width="24" height="18" class="rounded" style="object-fit: cover;">
                                <svg class="currency-calc-arrow text_mono-gray-6" width="12" height="12" viewBox="0 0 12 12" fill="currentColor" aria-hidden="true"><path d="M6 8L2 4h8z"/></svg>
                            </div>
                            <div class="currency-calc-dropdown" id="currency-calc-dropdown" role="listbox">
                                @foreach($ratesList as $r)
                                    @if($r['rate'] !== null)
                                    <div class="currency-calc-option" role="option" tabindex="0" data-value="{{ $r['code'] }}" data-rate="{{ $r['rate'] }}" data-flag="{{ $flagCodes[$r['code']] ?? 'us' }}">
                                        <img src="https://flagcdn.com/w40/{{ $flagCodes[$r['code']] ?? 'us' }}.png" alt="" width="24" height="18" class="rounded" style="object-fit: cover;">
                                        <span>{{ $r['code'] }}</span>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        <input type="number" id="currency-calc-amount" class="form-control flex-grow-1 currency-calc-amount" style="max-width: 160px;" placeholder="Сумма" min="0" step="any" value="100" aria-label="Сумма">
                        <span class="text_mono-gray-6 align-self-center">→</span>
                        <div class="currency-calc-result text_mono-dark-9 fw-5 d-flex align-items-center">
                            <span id="currency-calc-result-value">0</span>
                            <span class="ms-1">₽</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@push('scripts')
<script>
(function() {
    var source = document.getElementById('currency-calc-source');
    var trigger = document.getElementById('currency-calc-trigger');
    var from = document.getElementById('currency-calc-from');
    var dropdown = document.getElementById('currency-calc-dropdown');
    var triggerCode = document.getElementById('currency-calc-trigger-code');
    var flagImg = document.getElementById('currency-calc-flag');
    var amount = document.getElementById('currency-calc-amount');
    var resultEl = document.getElementById('currency-calc-result-value');
    if (!from || !amount || !resultEl || !source || !dropdown) return;

    function updateResult() {
        var opt = from.options[from.selectedIndex];
        var rate = parseFloat(opt ? opt.getAttribute('data-rate') : 0) || 0;
        var val = parseFloat(amount.value) || 0;
        resultEl.textContent = (val * rate).toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }

    function setSelected(code, rate, flagCode) {
        from.value = code;
        if (triggerCode) triggerCode.textContent = code;
        if (flagImg && flagCode) flagImg.src = 'https://flagcdn.com/w40/' + flagCode + '.png';
        source.classList.remove('is-open');
        source.setAttribute('aria-expanded', 'false');
        updateResult();
    }

    trigger.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var isOpen = source.classList.toggle('is-open');
        source.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    dropdown.querySelectorAll('.currency-calc-option').forEach(function(opt) {
        opt.addEventListener('click', function() {
            setSelected(opt.getAttribute('data-value'), opt.getAttribute('data-rate'), opt.getAttribute('data-flag'));
        });
        opt.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                setSelected(opt.getAttribute('data-value'), opt.getAttribute('data-rate'), opt.getAttribute('data-flag'));
            }
        });
    });

    document.addEventListener('click', function(e) {
        if (source && !source.contains(e.target)) {
            source.classList.remove('is-open');
            source.setAttribute('aria-expanded', 'false');
        }
    });

    amount.addEventListener('input', updateResult);
    updateResult();
})();
</script>
@endpush
@endif
