/**
 * Калькулятор кредитов (страница сервиса): аннуитет, результаты при загрузке и по кнопке.
 */
(function () {
    'use strict';

    var root = document.getElementById('credit-service-calculator');
    if (!root) return;

    function parseAmount(str) {
        if (str === undefined || str === null) return NaN;
        return parseFloat(String(str).replace(/\s/g, '').replace(',', '.')) || 0;
    }

    function formatMoney(num) {
        return (
            new Intl.NumberFormat('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(
                Math.round(num)
            ) + ' ₽'
        );
    }

    function annuityPayment(P, annualRatePercent, months) {
        if (months <= 0 || P <= 0) return 0;
        var r = annualRatePercent / 100 / 12;
        if (r === 0) return P / months;
        var x = Math.pow(1 + r, months);
        return (P * (r * x)) / (x - 1);
    }

    function termToMonths(value, unit) {
        var n = parseInt(value, 10) || 0;
        if (n < 1) return 0;
        return unit === 'years' ? n * 12 : n;
    }

    function calculate() {
        var amount = parseAmount(elAmount.value);
        var rate = parseAmount(elRate.value);
        var months = termToMonths(elTerm.value, elTermUnit.value);

        if (amount <= 0 || months <= 0 || rate < 0) {
            elMonthly.textContent = '—';
            elTotal.textContent = '—';
            elOverpay.textContent = '—';
            if (elErr) {
                elErr.style.display = amount <= 0 || months <= 0 ? 'block' : 'none';
                elErr.textContent = 'Укажите сумму и срок.';
            }
            return;
        }
        if (elErr) elErr.style.display = 'none';

        var monthly = annuityPayment(amount, rate, months);
        var total = monthly * months;
        var overpay = total - amount;

        elMonthly.textContent = formatMoney(monthly);
        elTotal.textContent = formatMoney(total);
        elOverpay.textContent = formatMoney(overpay);
        if (elResult) elResult.hidden = false;
    }

    var elAmount = root.querySelector('[data-csc-amount]');
    var elTerm = root.querySelector('[data-csc-term]');
    var elTermUnit = root.querySelector('[data-csc-term-unit]');
    var elRate = root.querySelector('[data-csc-rate]');
    var elBtn = root.querySelector('[data-csc-calc]');
    var elMonthly = root.querySelector('[data-csc-out-monthly]');
    var elTotal = root.querySelector('[data-csc-out-total]');
    var elOverpay = root.querySelector('[data-csc-out-overpay]');
    var elResult = root.querySelector('[data-csc-result]');
    var elErr = root.querySelector('[data-csc-error]');

    if (!elAmount || !elTerm || !elRate || !elMonthly) return;

    function formatAmountInput() {
        var v = elAmount.value.replace(/\D/g, '');
        if (v === '') return;
        var n = parseInt(v, 10);
        if (!isNaN(n)) elAmount.value = new Intl.NumberFormat('ru-RU').format(n);
    }

    elAmount.addEventListener('input', function () {
        formatAmountInput();
    });
    elAmount.addEventListener('blur', formatAmountInput);

    if (elBtn) elBtn.addEventListener('click', calculate);
    elTerm.addEventListener('input', calculate);
    elTerm.addEventListener('change', calculate);
    elTermUnit.addEventListener('change', calculate);
    elRate.addEventListener('input', calculate);
    elRate.addEventListener('change', calculate);

    calculate();
})();
