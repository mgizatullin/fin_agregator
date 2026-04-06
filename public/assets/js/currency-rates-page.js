(function () {
    'use strict';

    function parseNum(val) {
        if (val === '' || val === null || val === undefined) return 0;
        var s = String(val).replace(/\s/g, '').replace(',', '.');
        var n = parseFloat(s);
        return isNaN(n) ? 0 : n;
    }

    function formatRub(n) {
        return new Intl.NumberFormat('ru-RU', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(n);
    }

    document.querySelectorAll('[data-currency-convert]').forEach(function (card) {
        var rateStr = card.getAttribute('data-rate');
        if (!rateStr) return;
        var rate = parseFloat(rateStr);
        if (!isFinite(rate) || rate <= 0) return;

        var input = card.querySelector('[data-cbr-amount]');
        var out = card.querySelector('[data-rub-out]');
        if (!input || !out) return;

        function update() {
            var amount = parseNum(input.value);
            out.textContent = formatRub(amount * rate);
        }

        input.addEventListener('input', update);
        input.addEventListener('change', update);
        update();
    });
})();
