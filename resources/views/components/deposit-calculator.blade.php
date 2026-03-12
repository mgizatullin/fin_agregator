@props(['deposit'])

@php
    /** @var \App\Models\Deposit $deposit */
    $deposit->loadMissing(['currencies.conditions']);
    $currencies = $deposit->currencies->filter(fn ($c) => $c->conditions->where('is_active', true)->isNotEmpty())->values();
    $currencySymbols = ['RUB' => '₽', 'USD' => '$', 'EUR' => '€', 'CNY' => '¥'];

    $conditionsByCurrency = [];
    $limitsByCurrency = [];

    foreach ($currencies as $currency) {
        $code = $currency->currency_code;
        $active = $currency->conditions->where('is_active', true)->values();
        $list = [];
        $amountMins = [];
        $amountMaxs = [];
        $termMins = [];
        $termMaxs = [];
        foreach ($active as $c) {
            $list[] = [
                'amount_min' => $c->amount_min !== null ? (float) $c->amount_min : null,
                'amount_max' => $c->amount_max !== null ? (float) $c->amount_max : null,
                'term_days_min' => $c->term_days_min !== null ? (int) $c->term_days_min : null,
                'term_days_max' => $c->term_days_max !== null ? (int) $c->term_days_max : null,
                'rate' => $c->rate !== null ? (float) $c->rate : null,
            ];
            if ($c->amount_min !== null) {
                $amountMins[] = (float) $c->amount_min;
            }
            if ($c->amount_max !== null) {
                $amountMaxs[] = (float) $c->amount_max;
            }
            if ($c->term_days_min !== null) {
                $termMins[] = (int) $c->term_days_min;
            }
            if ($c->term_days_max !== null) {
                $termMaxs[] = (int) $c->term_days_max;
            }
        }
        $conditionsByCurrency[$code] = $list;
        $limitsByCurrency[$code] = [
            'min_amount' => count($amountMins) > 0 ? min($amountMins) : null,
            'max_amount' => count($amountMaxs) > 0 ? max($amountMaxs) : null,
            'min_term' => count($termMins) > 0 ? min($termMins) : null,
            'max_term' => count(array_merge($termMins, $termMaxs)) > 0 ? max(array_merge($termMins, $termMaxs)) : null,
        ];
    }

    $firstCode = $currencies->first()?->currency_code ?? 'RUB';
    $firstLimits = $limitsByCurrency[$firstCode] ?? ['min_amount' => 0, 'max_amount' => null, 'min_term' => 31, 'max_term' => 365];
    $defaultAmount = $firstLimits['min_amount'] ?? 100000;
    $defaultTerm = $firstLimits['min_term'] ?? 31;

    $depositConditionsJson = [
        'currencies' => $conditionsByCurrency,
        'limits' => $limitsByCurrency,
        'symbols' => $currencySymbols,
    ];
@endphp

@if($currencies->isNotEmpty())
<script id="deposit-conditions" type="application/json">{!! json_encode($depositConditionsJson) !!}</script>

<div class="deposit-calculator" id="deposit-calculator">
    <h3 class="deposit-calculator__title">Калькулятор доходности</h3>

    <div class="deposit-calculator__currency">
        <label class="deposit-calculator__label">Валюта</label>
        <div class="deposit-calculator__currency-btns" role="group">
            @foreach($currencies as $currency)
                <button type="button" class="deposit-calculator__currency-btn {{ $currency->currency_code === $firstCode ? 'is-active' : '' }}" data-currency="{{ $currency->currency_code }}">{{ $currencySymbols[$currency->currency_code] ?? $currency->currency_code }}</button>
            @endforeach
        </div>
    </div>

    <div class="deposit-calculator__field">
        <label class="deposit-calculator__label" for="deposit-calc-amount">Сумма вклада</label>
        <input type="number" id="deposit-calc-amount" class="deposit-calculator__input" min="{{ (int)($firstLimits['min_amount'] ?? 0) }}" @if(isset($firstLimits['max_amount']) && $firstLimits['max_amount'] !== null) max="{{ (int)$firstLimits['max_amount'] }}" @endif step="1000" value="{{ (int)$defaultAmount }}" autocomplete="off">
    </div>

    <div class="deposit-calculator__field">
        <label class="deposit-calculator__label" for="deposit-calc-term">Срок (дней)</label>
        <input type="number" id="deposit-calc-term" class="deposit-calculator__input" min="{{ (int)($firstLimits['min_term'] ?? 1) }}" max="{{ (int)($firstLimits['max_term'] ?? 3650) }}" step="1" value="{{ (int)$defaultTerm }}" autocomplete="off">
    </div>

    <div class="deposit-calculator__results">
        <div class="deposit-calculator__result-row">
            <span class="deposit-calculator__result-label">Доход</span>
            <span class="deposit-calculator__result-value" id="deposit-calc-income">—</span>
        </div>
        <div class="deposit-calculator__result-row">
            <span class="deposit-calculator__result-label">Ставка</span>
            <span class="deposit-calculator__result-value" id="deposit-calc-rate">—</span>
        </div>
        <div class="deposit-calculator__result-row">
            <span class="deposit-calculator__result-label">Сумма вклада</span>
            <span class="deposit-calculator__result-value" id="deposit-calc-amount-display">—</span>
        </div>
        <div class="deposit-calculator__result-row">
            <span class="deposit-calculator__result-label">Сумма в конце срока</span>
            <span class="deposit-calculator__result-value deposit-calculator__result-value--total" id="deposit-calc-total">—</span>
        </div>
    </div>
</div>

<script>
(function() {
    var script = document.getElementById('deposit-conditions');
    if (!script) return;
    var data = JSON.parse(script.textContent);
    var currencies = data.currencies || {};
    var limits = data.limits || {};
    var symbols = data.symbols || {};

    var currencyBtns = document.querySelectorAll('#deposit-calculator .deposit-calculator__currency-btn');
    var amountInput = document.getElementById('deposit-calc-amount');
    var termInput = document.getElementById('deposit-calc-term');
    var incomeEl = document.getElementById('deposit-calc-income');
    var rateEl = document.getElementById('deposit-calc-rate');
    var amountDisplayEl = document.getElementById('deposit-calc-amount-display');
    var totalEl = document.getElementById('deposit-calc-total');

    var currentCurrency = currencyBtns.length ? currencyBtns[0].getAttribute('data-currency') : null;

    function findRate(currencyCode, amount, termDays) {
        var list = currencies[currencyCode];
        if (!list || !list.length) return null;
        amount = Number(amount);
        termDays = parseInt(termDays, 10) || 0;
        var amountMatched = list.filter(function(c) {
            return (c.amount_min == null || c.amount_min <= amount) && (c.amount_max == null || c.amount_max >= amount);
        });
        if (!amountMatched.length) return null;
        amountMatched = amountMatched.filter(function(c) {
            return c.term_days_min != null && c.term_days_min <= termDays && (c.term_days_max == null || c.term_days_max >= termDays);
        });
        if (amountMatched.length) {
            amountMatched.sort(function(a, b) { return (b.term_days_min || 0) - (a.term_days_min || 0); });
            return amountMatched[0].rate;
        }
        amountMatched = list.filter(function(c) {
            return (c.amount_min == null || c.amount_min <= amount) && (c.amount_max == null || c.amount_max >= amount);
        });
        var termMatched = amountMatched.filter(function(c) { return c.term_days_min != null && c.term_days_min <= termDays; });
        if (!termMatched.length) return null;
        termMatched.sort(function(a, b) { return (b.term_days_min || 0) - (a.term_days_min || 0); });
        return termMatched[0].rate;
    }

    function formatNum(n) {
        return Number(n).toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function formatRate(r) {
        return Number(r).toFixed(2).replace(/\.?0+$/, '') + '%';
    }

    function updateLimits() {
        var lim = limits[currentCurrency] || {};
        var minA = lim.min_amount != null ? lim.min_amount : 0;
        var maxA = lim.max_amount;
        if (amountInput) {
            amountInput.min = minA;
            amountInput.max = maxA != null ? maxA : '';
            if (Number(amountInput.value) < minA) amountInput.value = minA;
            if (maxA != null && Number(amountInput.value) > maxA) amountInput.value = maxA;
        }
        var minT = lim.min_term != null ? lim.min_term : 1;
        var maxT = lim.max_term != null ? lim.max_term : 3650;
        if (termInput) {
            termInput.min = minT;
            termInput.max = maxT;
            if (Number(termInput.value) < minT) termInput.value = minT;
            if (Number(termInput.value) > maxT) termInput.value = maxT;
        }
    }

    function recalc() {
        var amount = Number(amountInput && amountInput.value ? amountInput.value : 0);
        var term = parseInt(termInput && termInput.value ? termInput.value : 0, 10) || 0;
        var sym = symbols[currentCurrency] || '';

        if (!amount || !term) {
            if (incomeEl) incomeEl.textContent = '—';
            if (rateEl) rateEl.textContent = '—';
            if (amountDisplayEl) amountDisplayEl.textContent = '—';
            if (totalEl) totalEl.textContent = '—';
            return;
        }

        var rate = findRate(currentCurrency, amount, term);
        if (rate == null) {
            if (incomeEl) incomeEl.textContent = '—';
            if (rateEl) rateEl.textContent = '—';
            if (amountDisplayEl) amountDisplayEl.textContent = formatNum(amount) + ' ' + sym;
            if (totalEl) totalEl.textContent = '—';
            return;
        }

        var income = amount * rate / 100 * (term / 365);
        var total = amount + income;

        if (incomeEl) incomeEl.textContent = formatNum(income) + ' ' + sym;
        if (rateEl) rateEl.textContent = formatRate(rate);
        if (amountDisplayEl) amountDisplayEl.textContent = formatNum(amount) + ' ' + sym;
        if (totalEl) totalEl.textContent = formatNum(total) + ' ' + sym;
    }

    var debounceMs = 200;
    var debounceTimer = null;
    function scheduleRecalc() {
        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            debounceTimer = null;
            recalc();
        }, debounceMs);
    }

    if (currencyBtns.length) {
        currencyBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                currentCurrency = this.getAttribute('data-currency');
                currencyBtns.forEach(function(b) { b.classList.remove('is-active'); });
                this.classList.add('is-active');
                updateLimits();
                scheduleRecalc();
            });
        });
    }

    if (amountInput) {
        amountInput.addEventListener('input', scheduleRecalc);
        amountInput.addEventListener('change', scheduleRecalc);
    }
    if (termInput) {
        termInput.addEventListener('input', scheduleRecalc);
        termInput.addEventListener('change', scheduleRecalc);
    }

    updateLimits();
    recalc();
})();
</script>
@endif
