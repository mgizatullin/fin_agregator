@props(['credit'])

@php
    /** @var \App\Models\Credit $credit */
    $defaultAmount = (float) ($credit->max_amount ?? $credit->min_amount ?? 500000);
    $defaultRate = (float) ($credit->rate_min ?? $credit->rate ?? 12);
    $defaultTerm = (int) ($credit->term_months ?? $credit->max_term_months ?? $credit->min_term_months ?? 12);
    $minAmount = (float) ($credit->min_amount ?? 0);
    $maxAmount = (float) ($credit->max_amount ?? 5000000);
    $maxTerm = (int) ($credit->max_term_months ?? 60);
    $minTerm = (int) ($credit->min_term_months ?? $credit->term_months ?? 1);
    $creditCalcData = [
        'defaultAmount' => $defaultAmount,
        'defaultRate' => $defaultRate,
        'defaultTerm' => $defaultTerm,
        'minAmount' => $minAmount,
        'maxAmount' => $maxAmount > 0 ? $maxAmount : 5000000,
        'minTerm' => max(1, $minTerm),
        'maxTerm' => max(12, $maxTerm),
    ];
@endphp

<div class="deposit-calculator credit-calculator" id="credit-calculator">
    <h3 class="deposit-calculator__title">Калькулятор кредита</h3>

    <div class="deposit-calculator__field">
        <label class="deposit-calculator__label" for="credit-calc-amount">Сумма кредита, ₽</label>
        <input type="text" id="credit-calc-amount" inputmode="numeric" class="deposit-calculator__input deposit-calculator__input--no-spinner" data-min="{{ (int) $creditCalcData['minAmount'] }}" data-max="{{ (int) $creditCalcData['maxAmount'] }}" value="{{ number_format((int) $defaultAmount, 0, '', ' ') }}" autocomplete="off">
    </div>

    <div class="deposit-calculator__field">
        <label class="deposit-calculator__label" for="credit-calc-rate">Ставка, % годовых</label>
        <input type="text" id="credit-calc-rate" inputmode="decimal" class="deposit-calculator__input deposit-calculator__input--no-spinner" value="{{ rtrim(rtrim(number_format($defaultRate, 2, '.', ''), '0'), '.') }}" autocomplete="off">
    </div>

    <div class="deposit-calculator__field">
        <label class="deposit-calculator__label" for="credit-calc-term">Срок, мес.</label>
        <input type="number" id="credit-calc-term" min="{{ $creditCalcData['minTerm'] }}" max="{{ $creditCalcData['maxTerm'] }}" class="deposit-calculator__input deposit-calculator__input--no-spinner" value="{{ $defaultTerm }}" autocomplete="off">
    </div>

    <div class="deposit-calculator__results">
        <div class="deposit-calculator__result-row">
            <span class="deposit-calculator__result-label">Ежемесячный платёж</span>
            <span class="deposit-calculator__result-value" id="credit-calc-monthly">—</span>
        </div>
        <div class="deposit-calculator__result-row">
            <span class="deposit-calculator__result-label">Сумма к возврату</span>
            <span class="deposit-calculator__result-value" id="credit-calc-total">—</span>
        </div>
        <div class="deposit-calculator__result-row">
            <span class="deposit-calculator__result-label">Переплата</span>
            <span class="deposit-calculator__result-value deposit-calculator__result-value--total" id="credit-calc-overpay">—</span>
        </div>
    </div>
</div>

<script>
(function() {
    var amountEl = document.getElementById('credit-calc-amount');
    var rateEl = document.getElementById('credit-calc-rate');
    var termEl = document.getElementById('credit-calc-term');
    var monthlyEl = document.getElementById('credit-calc-monthly');
    var totalEl = document.getElementById('credit-calc-total');
    var overpayEl = document.getElementById('credit-calc-overpay');
    if (!amountEl || !rateEl || !termEl || !monthlyEl || !totalEl || !overpayEl) return;

    function parseNum(val) {
        if (val === '' || val === null || val === undefined) return 0;
        return parseFloat(String(val).replace(/\s/g, '').replace(',', '.')) || 0;
    }

    function formatMoney(num) {
        return new Intl.NumberFormat('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Math.round(num)) + ' ₽';
    }

    function annuityPayment(P, annualRate, months) {
        if (months <= 0 || P <= 0) return 0;
        var r = annualRate / 100 / 12;
        if (r === 0) return P / months;
        var x = Math.pow(1 + r, months);
        return P * (r * x) / (x - 1);
    }

    function update() {
        var amount = parseNum(amountEl.value);
        var rate = parseNum(rateEl.value);
        var term = parseInt(termEl.value, 10) || 12;
        if (amount <= 0 || term <= 0) {
            monthlyEl.textContent = '—';
            totalEl.textContent = '—';
            overpayEl.textContent = '—';
            return;
        }
        var monthly = annuityPayment(amount, rate, term);
        var total = monthly * term;
        var overpay = total - amount;
        monthlyEl.textContent = formatMoney(monthly);
        totalEl.textContent = formatMoney(total);
        overpayEl.textContent = formatMoney(overpay);
    }

    function formatAmountInput() {
        var v = amountEl.value.replace(/\D/g, '');
        if (v === '') return;
        var n = parseInt(v, 10);
        if (!isNaN(n)) amountEl.value = new Intl.NumberFormat('ru-RU').format(n);
    }

    amountEl.addEventListener('input', function() { formatAmountInput(); update(); });
    amountEl.addEventListener('blur', formatAmountInput);
    rateEl.addEventListener('input', update);
    rateEl.addEventListener('change', update);
    termEl.addEventListener('input', update);
    termEl.addEventListener('change', update);
    update();
})();
</script>
