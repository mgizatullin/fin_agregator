/**
 * Калькулятор вкладов (сервис): расчёт по дням, пополнения/снятия, налог (упрощённо).
 */
(function () {
    'use strict';

    var root = document.getElementById('deposit-service-calculator');
    if (!root) return;

    var KEY_RATE = parseFloat(String(root.getAttribute('data-key-rate') || '18'));
    var TAX_RATE = 0.13;

    function pad(n) {
        return n < 10 ? '0' + n : String(n);
    }

    function toYMD(d) {
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }

    function parseYMD(s) {
        if (!s || typeof s !== 'string') return null;
        var p = s.split('-');
        if (p.length !== 3) return null;
        var y = parseInt(p[0], 10);
        var m = parseInt(p[1], 10) - 1;
        var day = parseInt(p[2], 10);
        if (isNaN(y) || isNaN(m) || isNaN(day)) return null;
        return new Date(y, m, day);
    }

    function addDays(d, n) {
        var x = new Date(d.getFullYear(), d.getMonth(), d.getDate());
        x.setDate(x.getDate() + n);
        return x;
    }

    function addMonths(d, n) {
        var x = new Date(d.getFullYear(), d.getMonth(), d.getDate());
        x.setMonth(x.getMonth() + n);
        return x;
    }

    function sameDate(a, b) {
        return (
            a.getFullYear() === b.getFullYear() &&
            a.getMonth() === b.getMonth() &&
            a.getDate() === b.getDate()
        );
    }

    function compareDate(a, b) {
        var ta = Date.UTC(a.getFullYear(), a.getMonth(), a.getDate());
        var tb = Date.UTC(b.getFullYear(), b.getMonth(), b.getDate());
        return ta - tb;
    }

    function daysBetween(a, b) {
        var ta = Date.UTC(a.getFullYear(), a.getMonth(), a.getDate());
        var tb = Date.UTC(b.getFullYear(), b.getMonth(), b.getDate());
        return Math.round((tb - ta) / 86400000);
    }

    function isMonthiversary(open, d) {
        return isStepAnniversary(open, d, 1);
    }

    function isQuarterAnniversary(open, d) {
        return isStepAnniversary(open, d, 3);
    }

    function isStepAnniversary(open, d, stepMonths) {
        if (compareDate(d, open) <= 0) return false;
        var k = stepMonths;
        while (k <= 1200) {
            var t = addMonths(open, k);
            if (sameDate(t, d)) return true;
            if (compareDate(t, d) > 0) return false;
            k += stepMonths;
        }
        return false;
    }

    function formatMoney(n) {
        if (n === null || n === undefined || isNaN(n)) return '—';
        var v = Math.round(n * 100) / 100;
        return (
            new Intl.NumberFormat('ru-RU', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
            }).format(v) + ' ₽'
        );
    }

    function formatMoneyPlain(n) {
        return new Intl.NumberFormat('ru-RU', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        }).format(Math.round(n * 100) / 100);
    }

    function formatDateRu(d) {
        return pad(d.getDate()) + '.' + pad(d.getMonth() + 1) + '.' + d.getFullYear();
    }

    function parseAmount(str) {
        if (str === undefined || str === null) return NaN;
        var s = String(str).replace(/\s/g, '').replace(',', '.');
        return parseFloat(s);
    }

    function computeEndDate(open, termValue, termUnit) {
        var n = parseInt(termValue, 10);
        if (!n || n < 1) return null;
        if (termUnit === 'years') return addMonths(open, n * 12);
        return addMonths(open, n);
    }

    function shouldFlushBucket(d, freq, open, end) {
        if (compareDate(d, open) <= 0) return false;
        if (freq === 'end') return sameDate(d, end);
        if (sameDate(d, end)) return true;
        if (freq === 'monthly') return isMonthiversary(open, d);
        if (freq === 'quarterly') return isQuarterAnniversary(open, d);
        return false;
    }

    function runSimulation(params) {
        var open = params.open;
        var end = params.end;
        var annualRate = params.annualRate;
        var freq = params.freq;
        var reinvest = params.reinvest;
        var initialAmount = params.initialAmount;
        var operations = params.operations || [];

        var r = annualRate / 100;
        var balance = initialAmount;
        var totalInterest = 0;
        var sumBalDays = 0;
        var rows = [];
        var seq = 0;

        var opsByDay = {};
        for (var i = 0; i < operations.length; i++) {
            var op = operations[i];
            var key = toYMD(op.date);
            if (!opsByDay[key]) opsByDay[key] = [];
            opsByDay[key].push(op);
        }

        function applyOps(d) {
            var key = toYMD(d);
            var list = opsByDay[key];
            if (!list || !list.length) return;
            var parts = [];
            for (var j = 0; j < list.length; j++) {
                var o = list[j];
                if (o.type === 'in') {
                    balance += o.amount;
                    parts.push('+' + formatMoneyPlain(o.amount) + ' ₽');
                } else {
                    balance -= o.amount;
                    parts.push('−' + formatMoneyPlain(o.amount) + ' ₽');
                }
            }
            seq++;
            rows.push({
                seq: seq,
                date: new Date(d.getFullYear(), d.getMonth(), d.getDate()),
                interest: null,
                flow: parts.join('; '),
                balance: balance,
            });
        }

        seq++;
        rows.push({
            seq: seq,
            date: new Date(open.getFullYear(), open.getMonth(), open.getDate()),
            interest: null,
            flow: null,
            balance: initialAmount,
        });

        applyOps(open);

        if (balance < 0) {
            return { error: 'Баланс не может быть отрицательным (проверьте суммы снятий).' };
        }

        var termDays = daysBetween(open, end) + 1;
        if (termDays < 1) {
            return { error: 'Некорректные даты.' };
        }

        if (freq === 'daily') {
            for (var d = open; compareDate(d, end) <= 0; d = addDays(d, 1)) {
                if (!sameDate(d, open)) {
                    applyOps(d);
                    if (balance < 0) {
                        return { error: 'Баланс не может быть отрицательным.' };
                    }
                }

                var dailyInt = balance * r / 365;
                totalInterest += dailyInt;
                sumBalDays += balance;

                if (reinvest) {
                    balance += dailyInt;
                }

                seq++;
                rows.push({
                    seq: seq,
                    date: new Date(d.getFullYear(), d.getMonth(), d.getDate()),
                    interest: dailyInt,
                    flow: null,
                    balance: balance,
                });
            }
        } else {
            var bucket = 0;
            for (var d2 = open; compareDate(d2, end) <= 0; d2 = addDays(d2, 1)) {
                if (!sameDate(d2, open)) {
                    applyOps(d2);
                    if (balance < 0) {
                        return { error: 'Баланс не может быть отрицательным.' };
                    }
                }

                var dailyInt2 = balance * r / 365;
                bucket += dailyInt2;
                sumBalDays += balance;

                if (shouldFlushBucket(d2, freq, open, end)) {
                    totalInterest += bucket;
                    if (reinvest) {
                        balance += bucket;
                    }
                    seq++;
                    rows.push({
                        seq: seq,
                        date: new Date(d2.getFullYear(), d2.getMonth(), d2.getDate()),
                        interest: bucket,
                        flow: null,
                        balance: balance,
                    });
                    bucket = 0;
                }
            }
        }

        var avgBal = termDays > 0 ? sumBalDays / termDays : 0;
        var effectiveRate =
            avgBal > 0 && termDays > 0
                ? (totalInterest / avgBal) * (365 / termDays) * 100
                : 0;

        var exemption = 1000000 * (KEY_RATE / 100) * (termDays / 365);
        var taxable = Math.max(0, totalInterest - exemption);
        var tax = taxable * TAX_RATE;

        rows.sort(function (a, b) {
            var c = compareDate(a.date, b.date);
            if (c !== 0) return c;
            return a.seq - b.seq;
        });
        for (var ri = 0; ri < rows.length; ri++) {
            rows[ri].no = ri + 1;
        }

        return {
            error: null,
            finalBalance: balance,
            totalInterest: totalInterest,
            effectiveRate: effectiveRate,
            tax: tax,
            exemption: exemption,
            endDate: end,
            termDays: termDays,
            rows: rows,
        };
    }

    var elAmount = root.querySelector('[data-dsc-amount]');
    var elTerm = root.querySelector('[data-dsc-term]');
    var elTermUnit = root.querySelector('[data-dsc-term-unit]');
    var elOpenDisplay = root.querySelector('[data-dsc-open-display]');
    var elOpenInput = root.querySelector('[data-dsc-open-input]');
    var elRate = root.querySelector('[data-dsc-rate]');
    var elFreq = root.querySelector('[data-dsc-freq]');
    var elReinvest = root.querySelector('[data-dsc-reinvest]');
    var elBtnReplenish = root.querySelector('[data-dsc-add-replenish]');
    var elBtnWithdraw = root.querySelector('[data-dsc-add-withdraw]');
    var elFlowsList = root.querySelector('[data-dsc-flows-list]');
    var elBtnCalc = root.querySelector('[data-dsc-calc]');
    var elResult = root.querySelector('[data-dsc-result]');
    var elTableBody = root.querySelector('[data-dsc-table-body]');
    var elErr = root.querySelector('[data-dsc-error]');
    var elFlowPanel = root.querySelector('[data-dsc-flow-panel]');
    var elFlowDate = root.querySelector('[data-dsc-flow-date]');
    var elFlowAmount = root.querySelector('[data-dsc-flow-amount]');
    var elFlowSave = root.querySelector('[data-dsc-flow-save]');
    var elFlowCancel = root.querySelector('[data-dsc-flow-cancel]');
    var elFlowTitle = root.querySelector('[data-dsc-flow-title]');

    var flowId = 0;
    var flows = [];
    var flowMode = 'in';

    function defaultOpenDate() {
        var t = new Date();
        return new Date(t.getFullYear(), t.getMonth(), t.getDate());
    }

    function syncOpenDisplay() {
        var d = parseYMD(elOpenInput.value);
        if (d) elOpenDisplay.textContent = formatDateRu(d);
        else elOpenDisplay.textContent = '—';
    }

    if (!elOpenInput || !elOpenDisplay) {
        return;
    }

    elOpenInput.addEventListener('change', syncOpenDisplay);
    elOpenInput.addEventListener('input', syncOpenDisplay);

    if (!elOpenInput.value) {
        elOpenInput.value = toYMD(defaultOpenDate());
    }
    syncOpenDisplay();

    function renderFlows() {
        if (!elFlowsList) return;
        elFlowsList.innerHTML = '';
        flows.forEach(function (f) {
            var row = document.createElement('div');
            row.className = 'deposit-service-calc__flow-row';
            row.innerHTML =
                '<span class="deposit-service-calc__flow-meta">' +
                formatDateRu(f.date) +
                ' · ' +
                (f.type === 'in' ? 'Пополнение' : 'Снятие') +
                ' · ' +
                formatMoney(f.amount) +
                '</span>' +
                '<button type="button" class="deposit-service-calc__flow-remove" data-remove-id="' +
                f.id +
                '">×</button>';
            elFlowsList.appendChild(row);
        });
        elFlowsList.querySelectorAll('[data-remove-id]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(btn.getAttribute('data-remove-id'), 10);
                flows = flows.filter(function (x) {
                    return x.id !== id;
                });
                renderFlows();
            });
        });
    }

    function openFlowPanel(mode) {
        if (!elFlowPanel || !elFlowAmount || !elFlowDate) return;
        flowMode = mode;
        if (elFlowTitle) {
            elFlowTitle.textContent = mode === 'in' ? 'Пополнение' : 'Снятие';
        }
        elFlowAmount.value = '';
        elFlowDate.value = elOpenInput.value;
        elFlowPanel.hidden = false;
        elFlowAmount.focus();
    }

    function closeFlowPanel() {
        if (elFlowPanel) elFlowPanel.hidden = true;
    }

    if (elBtnReplenish) {
        elBtnReplenish.addEventListener('click', function () {
            openFlowPanel('in');
        });
    }
    if (elBtnWithdraw) {
        elBtnWithdraw.addEventListener('click', function () {
            openFlowPanel('out');
        });
    }
    if (elFlowCancel) {
        elFlowCancel.addEventListener('click', closeFlowPanel);
    }
    if (elFlowSave) {
        elFlowSave.addEventListener('click', function () {
            var amt = parseAmount(elFlowAmount.value);
            if (isNaN(amt) || amt <= 0) {
                alert('Введите положительную сумму.');
                return;
            }
            var d = parseYMD(elFlowDate.value);
            if (!d) {
                alert('Укажите дату.');
                return;
            }
            var openD = parseYMD(elOpenInput.value);
            var endD = computeEndDate(openD, elTerm.value, elTermUnit.value);
            if (!endD) {
                alert('Укажите корректный срок.');
                return;
            }
            if (compareDate(d, openD) < 0 || compareDate(d, endD) > 0) {
                alert('Дата должна быть в пределах срока вклада.');
                return;
            }
            flowId++;
            flows.push({ id: flowId, type: flowMode, date: d, amount: amt });
            renderFlows();
            closeFlowPanel();
        });
    }

    function showErr(msg) {
        if (elErr) {
            elErr.textContent = msg;
            elErr.style.display = 'block';
        } else {
            alert(msg);
        }
    }

    function clearErr() {
        if (elErr) {
            elErr.textContent = '';
            elErr.style.display = 'none';
        }
    }

    if (elBtnCalc) {
        elBtnCalc.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            clearErr();

            if (!elAmount || !elTerm || !elTermUnit || !elRate || !elFreq || !elReinvest || !elOpenInput) {
                showErr('Не удалось инициализировать форму. Обновите страницу.');
                return;
            }

            var amount = parseAmount(elAmount.value);
            if (isNaN(amount) || amount <= 0) {
                showErr('Укажите сумму вклада.');
                return;
            }

            var open = parseYMD(elOpenInput.value);
            if (!open) {
                showErr('Укажите дату открытия.');
                return;
            }

            var end = computeEndDate(open, elTerm.value, elTermUnit.value);
            if (!end || compareDate(end, open) <= 0) {
                showErr('Укажите срок (от 1 месяца или 1 года).');
                return;
            }

            var rate = parseAmount(elRate.value);
            if (isNaN(rate) || rate < 0) {
                showErr('Укажите ставку % годовых.');
                return;
            }

            var freq = elFreq.value;
            var reinvest = elReinvest.value === 'reinvest';

            var ops = flows.map(function (f) {
                return { type: f.type, date: f.date, amount: f.amount };
            });

            var res;
            try {
                res = runSimulation({
                    open: open,
                    end: end,
                    annualRate: rate,
                    freq: freq,
                    reinvest: reinvest,
                    initialAmount: amount,
                    operations: ops,
                });
            } catch (err) {
                console.error(err);
                showErr('Ошибка расчёта. Проверьте введённые данные.');
                return;
            }

            if (res.error) {
                showErr(res.error);
                return;
            }

            var outBal = root.querySelector('[data-dsc-out-balance]');
            var outInt = root.querySelector('[data-dsc-out-interest]');
            var outEff = root.querySelector('[data-dsc-out-effective]');
            var outTax = root.querySelector('[data-dsc-out-tax]');
            var outEnd = root.querySelector('[data-dsc-out-end]');
            if (outBal) outBal.textContent = formatMoney(res.finalBalance);
            if (outInt) outInt.textContent = formatMoney(res.totalInterest);
            if (outEff) {
                outEff.textContent =
                    (isFinite(res.effectiveRate) ? res.effectiveRate : 0).toFixed(2).replace('.', ',') + '%';
            }
            if (outTax) outTax.textContent = formatMoney(res.tax);
            if (outEnd) outEnd.textContent = formatDateRu(res.endDate);

            if (elTableBody) {
                elTableBody.innerHTML = '';
                res.rows.forEach(function (row) {
                    var tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' +
                        row.no +
                        '</td>' +
                        '<td>' +
                        formatDateRu(row.date) +
                        '</td>' +
                        '<td>' +
                        (row.interest !== null ? formatMoneyPlain(row.interest) + ' ₽' : '—') +
                        '</td>' +
                        '<td>' +
                        (row.flow ? row.flow : '—') +
                        '</td>' +
                        '<td>' +
                        formatMoneyPlain(row.balance) +
                        ' ₽</td>';
                    elTableBody.appendChild(tr);
                });
            }

            if (elResult) {
                elResult.hidden = false;
                elResult.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    }
})();
