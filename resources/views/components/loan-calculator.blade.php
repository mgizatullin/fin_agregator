@props(['loan'])

@php
    /** @var \App\Models\Loan $loan */
    $defaultAmount = (float) ($loan->max_amount ?? 30000);
    $defaultRate = (float) ($loan->rate ?? 0);
    $defaultTermDays = (int) ($loan->term_days ?? 30);
    $defaultTermNoInterest = (int) ($loan->term_no_interest ?? 0);
    $minAmount = (float) ($loan->min_amount ?? 0);
    $maxAmount = (float) ($loan->max_amount ?? 100000);
    $minTermDays = (int) ($loan->term_days_min ?? $loan->term_days ?? 1);
    $maxTermDays = (int) ($loan->term_days ?? $loan->term_days_min ?? 365);

    if ($minAmount > $maxAmount) {
        $tmp = $minAmount;
        $minAmount = $maxAmount;
        $maxAmount = $tmp;
    }
    if ($minTermDays > $maxTermDays) {
        $tmp = $minTermDays;
        $minTermDays = $maxTermDays;
        $maxTermDays = $tmp;
    }

    $amountStep = $maxAmount > $minAmount ? max(1, (int) (($maxAmount - $minAmount) / 500)) : 1;
    $termStep = 1;
    $loanCalcData = [
        'defaultAmount' => $defaultAmount,
        'defaultRate' => $defaultRate,
        'defaultTermDays' => max(1, $defaultTermDays),
        'defaultTermNoInterest' => max(0, min($defaultTermNoInterest, $defaultTermDays)),
        'minAmount' => max(0, $minAmount),
        'maxAmount' => $maxAmount > 0 ? $maxAmount : 100000,
        'minTermDays' => max(1, $minTermDays),
        'maxTermDays' => max(1, $maxTermDays),
        'psk' => filled($loan->psk) ? (float) $loan->psk : null,
    ];
@endphp

<div class="deposit-calculator loan-calculator" id="loan-calculator">
    <h3 class="deposit-calculator__title">Калькулятор займа</h3>

    <div class="deposit-calculator__field">
        <label class="deposit-calculator__label" for="loan-calc-amount">Сумма займа, ₽</label>
        <input type="text" id="loan-calc-amount" inputmode="numeric" class="deposit-calculator__input deposit-calculator__input--no-spinner"
               data-min="{{ (int) $loanCalcData['minAmount'] }}" data-max="{{ (int) $loanCalcData['maxAmount'] }}"
               value="{{ number_format((int) $loanCalcData['defaultAmount'], 0, '', ' ') }}" autocomplete="off">
        <input type="range" class="deposit-calculator__range" id="loan-calc-amount-range"
               min="{{ (int) $loanCalcData['minAmount'] }}" max="{{ (int) $loanCalcData['maxAmount'] }}"
               step="{{ $amountStep }}" value="{{ (int) $loanCalcData['defaultAmount'] }}">
    </div>

    <div class="deposit-calculator__field">
        <label class="deposit-calculator__label" for="loan-calc-term">Срок, дней</label>
        <input type="number" id="loan-calc-term"
               min="{{ (int) $loanCalcData['minTermDays'] }}" max="{{ (int) $loanCalcData['maxTermDays'] }}"
               class="deposit-calculator__input deposit-calculator__input--no-spinner"
               value="{{ $loanCalcData['defaultTermDays'] }}" autocomplete="off">
        <input type="range" class="deposit-calculator__range" id="loan-calc-term-range"
               min="{{ (int) $loanCalcData['minTermDays'] }}" max="{{ (int) $loanCalcData['maxTermDays'] }}"
               step="{{ $termStep }}" value="{{ (int) $loanCalcData['defaultTermDays'] }}">
    </div>

    <div class="deposit-calculator__results">
        <div class="deposit-calculator__result-row">
            <span class="deposit-calculator__result-label">Срок без процентов</span>
            <span class="deposit-calculator__result-value" id="loan-calc-term-free-display">{{ (int) $loanCalcData['defaultTermNoInterest'] }} дн.</span>
        </div>
        <div class="deposit-calculator__result-row">
            <span class="deposit-calculator__result-label">Ставка, % в день</span>
            <span class="deposit-calculator__result-value" id="loan-calc-rate-display">{{ rtrim(rtrim(number_format($loanCalcData['defaultRate'], 2, '.', ''), '0'), '.') }}%</span>
        </div>
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
    var amountRange = document.getElementById('loan-calc-amount-range');
    var termEl = document.getElementById('loan-calc-term');
    var termRange = document.getElementById('loan-calc-term-range');
    var termFreeDisplayEl = document.getElementById('loan-calc-term-free-display');
    var overpayEl = document.getElementById('loan-calc-overpay');
    var totalEl = document.getElementById('loan-calc-total');
    if (!amountEl || !termEl || !overpayEl || !totalEl) return;

    var minAmount = parseInt(amountEl.getAttribute('data-min') || '0', 10) || 0;
    var maxAmount = parseInt(amountEl.getAttribute('data-max') || '100000', 10) || 100000;
    var termMin = parseInt(termEl.getAttribute('min') || '1', 10) || 1;
    var termMax = parseInt(termEl.getAttribute('max') || '365', 10) || 365;
    var fixedRate = parseNum({{ json_encode((float) $loanCalcData['defaultRate']) }});
    var fixedTermFree = parseInt({{ json_encode((int) $loanCalcData['defaultTermNoInterest']) }}, 10) || 0;

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

        if (amount < minAmount) amount = minAmount;
        if (amount > maxAmount) amount = maxAmount;
        if (termDays < termMin) termDays = termMin;
        if (termDays > termMax) termDays = termMax;

        if (amount <= 0 || termDays <= 0) {
            overpayEl.textContent = '—';
            totalEl.textContent = '—';
            return;
        }

        if (amountRange) amountRange.value = String(Math.round(amount));
        if (termRange) termRange.value = String(termDays);

        var termFree = Math.min(Math.max(0, fixedTermFree), termDays);
        if (termFreeDisplayEl) termFreeDisplayEl.textContent = String(termFree) + ' дн.';
        var daysWithInterest = Math.max(0, termDays - termFree);
        var overpay = 0;
        if (daysWithInterest > 0 && fixedRate > 0) {
            // $fixedRate хранится как "% в день".
            overpay = amount * (fixedRate / 100) * daysWithInterest;
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

    if (amountRange) {
        amountRange.addEventListener('input', function () {
            var v = parseInt(amountRange.value, 10) || 0;
            amountEl.value = new Intl.NumberFormat('ru-RU').format(Math.round(v));
            update();
        });
        amountRange.addEventListener('change', function () {
            var v = parseInt(amountRange.value, 10) || 0;
            amountEl.value = new Intl.NumberFormat('ru-RU').format(Math.round(v));
            update();
        });
    }

    if (termRange) {
        termRange.addEventListener('input', function () {
            var v = parseInt(termRange.value, 10) || termMin;
            termEl.value = String(Math.round(v));
            update();
        });
        termRange.addEventListener('change', function () {
            var v = parseInt(termRange.value, 10) || termMin;
            termEl.value = String(Math.round(v));
            update();
        });
    }
    update();
})();
</script>
