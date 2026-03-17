@props(['loan'])

@php
    /** @var \App\Models\Loan $loan */
    $defaultAmount = (float) ($loan->max_amount ?? 30000);
    $defaultRate = (float) ($loan->rate ?? 0);
    $defaultTermDays = (int) ($loan->term_days ?? 30);
    $defaultTermNoInterest = (int) ($loan->term_no_interest ?? 0);
    $maxAmount = (float) ($loan->max_amount ?? 100000);
    $loanCalcData = [
        'defaultAmount' => $defaultAmount,
        'defaultRate' => $defaultRate,
        'defaultTermDays' => max(1, $defaultTermDays),
        'defaultTermNoInterest' => max(0, min($defaultTermNoInterest, $defaultTermDays)),
        'maxAmount' => $maxAmount > 0 ? $maxAmount : 100000,
        'psk' => filled($loan->psk) ? (float) $loan->psk : null,
    ];
@endphp

<div class="deposit-calculator loan-calculator" id="loan-calculator">
    <h3 class="deposit-calculator__title">Калькулятор займа</h3>

    <div class="deposit-calculator__field">
        <label class="deposit-calculator__label" for="loan-calc-amount">Сумма займа, ₽</label>
        <input type="text" id="loan-calc-amount" inputmode="numeric" class="deposit-calculator__input deposit-calculator__input--no-spinner" data-max="{{ (int) $loanCalcData['maxAmount'] }}" value="{{ number_format((int) $loanCalcData['defaultAmount'], 0, '', ' ') }}" autocomplete="off">
    </div>

    <div class="deposit-calculator__field">
        <label class="deposit-calculator__label" for="loan-calc-term">Срок, дней</label>
        <input type="number" id="loan-calc-term" min="1" max="365" class="deposit-calculator__input deposit-calculator__input--no-spinner" value="{{ $loanCalcData['defaultTermDays'] }}" autocomplete="off">
    </div>

    <div class="deposit-calculator__field">
        <label class="deposit-calculator__label" for="loan-calc-term-free">Срок без процентов, дней</label>
        <input type="number" id="loan-calc-term-free" min="0" max="365" class="deposit-calculator__input deposit-calculator__input--no-spinner" value="{{ $loanCalcData['defaultTermNoInterest'] }}" autocomplete="off">
    </div>

    <div class="deposit-calculator__field">
        <label class="deposit-calculator__label" for="loan-calc-rate">Ставка, % годовых</label>
        <input type="text" id="loan-calc-rate" inputmode="decimal" class="deposit-calculator__input deposit-calculator__input--no-spinner" value="{{ rtrim(rtrim(number_format($loanCalcData['defaultRate'], 2, '.', ''), '0'), '.') }}" autocomplete="off">
    </div>

    @if($loanCalcData['psk'] !== null)
    <p class="deposit-calculator__hint text-body-3 text_mono-gray-6 mb-2">ПСК: {{ number_format($loanCalcData['psk'], 2, ',', ' ') }}%</p>
    @endif

    <div class="deposit-calculator__results">
        <div class="deposit-calculator__result-row">
            <span class="deposit-calculator__result-label">Переплата</span>
            <span class="deposit-calculator__result-value deposit-calculator__result-value--total" id="loan-calc-overpay">—</span>
        </div>
        <div class="deposit-calculator__result-row">
            <span class="deposit-calculator__result-label">Итого к возврату</span>
            <span class="deposit-calculator__result-value" id="loan-calc-total">—</span>
        </div>
    </div>
</div>

<script>
(function() {
    var amountEl = document.getElementById('loan-calc-amount');
    var termEl = document.getElementById('loan-calc-term');
    var termFreeEl = document.getElementById('loan-calc-term-free');
    var rateEl = document.getElementById('loan-calc-rate');
    var overpayEl = document.getElementById('loan-calc-overpay');
    var totalEl = document.getElementById('loan-calc-total');
    if (!amountEl || !termEl || !termFreeEl || !rateEl || !overpayEl || !totalEl) return;

    var maxAmount = parseInt(amountEl.getAttribute('data-max') || '100000', 10) || 100000;

    function parseNum(val) {
        if (val === '' || val === null || val === undefined) return 0;
        return parseFloat(String(val).replace(/\s/g, '').replace(',', '.')) || 0;
    }

    function formatMoney(num) {
        return new Intl.NumberFormat('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Math.round(num)) + ' ₽';
    }

    function update() {
        var amount = parseNum(amountEl.value);
        var termDays = parseInt(termEl.value, 10) || 0;
        var termFree = parseInt(termFreeEl.value, 10) || 0;
        var rate = parseNum(rateEl.value);
        if (amount <= 0 || termDays <= 0) {
            overpayEl.textContent = '—';
            totalEl.textContent = '—';
            return;
        }
        amount = Math.min(amount, maxAmount);
        termFree = Math.min(Math.max(0, termFree), termDays);
        var daysWithInterest = Math.max(0, termDays - termFree);
        var overpay = 0;
        if (daysWithInterest > 0 && rate > 0) {
            overpay = amount * (rate / 100) * (daysWithInterest / 365);
        }
        var total = amount + overpay;
        overpayEl.textContent = formatMoney(overpay);
        totalEl.textContent = formatMoney(total);
    }

    function formatAmountInput() {
        var v = amountEl.value.replace(/\D/g, '');
        if (v === '') return;
        var n = parseInt(v, 10);
        if (!isNaN(n)) amountEl.value = new Intl.NumberFormat('ru-RU').format(Math.min(n, maxAmount));
    }

    amountEl.addEventListener('input', function() { formatAmountInput(); update(); });
    amountEl.addEventListener('blur', formatAmountInput);
    termEl.addEventListener('input', update);
    termEl.addEventListener('change', update);
    termFreeEl.addEventListener('input', update);
    termFreeEl.addEventListener('change', update);
    rateEl.addEventListener('input', update);
    rateEl.addEventListener('change', update);
    update();
})();
</script>
