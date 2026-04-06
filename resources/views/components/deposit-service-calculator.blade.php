@props([
    'keyRate' => null,
])
@php
    $cbRate = $keyRate ?? config('services.deposit_calculator.cb_key_rate_percent', 18);
@endphp

<div
    id="deposit-service-calculator"
    class="deposit-service-calc"
    data-key-rate="{{ (float) $cbRate }}"
>
    <div class="deposit-service-calc__grid">
        <div class="deposit-service-calc__field">
            <label class="deposit-calculator__label" for="dsc-amount">Сумма, ₽</label>
            <input type="text" id="dsc-amount" class="deposit-calculator__input deposit-calculator__input--no-spinner" data-dsc-amount inputmode="decimal" autocomplete="off" placeholder="0">
        </div>

        <div class="deposit-service-calc__field deposit-service-calc__field--term">
            <span class="deposit-calculator__label">Срок</span>
            <div class="deposit-service-calc__term-row">
                <input type="text" class="deposit-calculator__input deposit-calculator__input--no-spinner" data-dsc-term inputmode="numeric" value="12" autocomplete="off">
                <select class="deposit-service-calc__select" data-dsc-term-unit>
                    <option value="months" selected>Месяцев</option>
                    <option value="years">Лет</option>
                </select>
            </div>
        </div>

        <div class="deposit-service-calc__field">
            <span class="deposit-calculator__label">Дата открытия вклада</span>
            <div class="deposit-service-calc__date-wrap">
                <span class="deposit-service-calc__date-display" data-dsc-open-display>—</span>
                <input type="date" class="deposit-service-calc__date-input" data-dsc-open-input aria-label="Дата открытия">
            </div>
        </div>

        <div class="deposit-service-calc__field">
            <label class="deposit-calculator__label" for="dsc-rate">Ставка, % годовых</label>
            <input type="text" id="dsc-rate" class="deposit-calculator__input deposit-calculator__input--no-spinner" data-dsc-rate inputmode="decimal" autocomplete="off" placeholder="16">
        </div>

        <div class="deposit-service-calc__field">
            <label class="deposit-calculator__label" for="dsc-freq">Периодичность выплат</label>
            <select id="dsc-freq" class="deposit-service-calc__select deposit-service-calc__select--wide" data-dsc-freq>
                <option value="daily">Ежедневно</option>
                <option value="monthly" selected>Ежемесячно</option>
                <option value="quarterly">Ежеквартально</option>
                <option value="end">В конце срока</option>
            </select>
        </div>

        <div class="deposit-service-calc__field">
            <label class="deposit-calculator__label" for="dsc-reinvest">Начисленный процент</label>
            <select id="dsc-reinvest" class="deposit-service-calc__select deposit-service-calc__select--wide" data-dsc-reinvest>
                <option value="payout">Выплачивать</option>
                <option value="reinvest" selected>Реинвестировать</option>
            </select>
        </div>
    </div>

    <div class="deposit-service-calc__flows">
        <div class="deposit-service-calc__flow-btns">
            <button type="button" class="deposit-service-calc__btn-secondary" data-dsc-add-replenish>Пополнение +</button>
            <button type="button" class="deposit-service-calc__btn-secondary" data-dsc-add-withdraw>Снятие +</button>
        </div>
        <div class="deposit-service-calc__flow-panel" data-dsc-flow-panel hidden>
            <div class="deposit-service-calc__flow-panel-title" data-dsc-flow-title>Пополнение</div>
            <div class="deposit-service-calc__flow-panel-grid">
                <input type="date" class="deposit-service-calc__input" data-dsc-flow-date>
                <input type="text" class="deposit-service-calc__input" data-dsc-flow-amount inputmode="decimal" placeholder="Сумма, ₽">
                <button type="button" class="deposit-service-calc__btn-primary" data-dsc-flow-save>Добавить</button>
                <button type="button" class="deposit-service-calc__btn-link" data-dsc-flow-cancel>Отмена</button>
            </div>
        </div>
        <div class="deposit-service-calc__flows-list" data-dsc-flows-list></div>
    </div>

    <div class="deposit-service-calc__actions">
        <button type="button" class="deposit-service-calc__btn-calc" data-dsc-calc>Рассчитать</button>
    </div>

    <div class="deposit-service-calc__error text_mono-gray-7" data-dsc-error style="display:none;margin-top:12px;"></div>

    <div class="deposit-service-calc__result" data-dsc-result hidden>
        <h3 class="deposit-service-calc__result-title">Итог</h3>
        <div class="deposit-calculator__results">
            <div class="deposit-calculator__result-row">
                <span class="deposit-calculator__result-label">Сумма вклада (на дату окончания)</span>
                <span class="deposit-calculator__result-value" data-dsc-out-balance>—</span>
            </div>
            <div class="deposit-calculator__result-row">
                <span class="deposit-calculator__result-label">Начисленные проценты</span>
                <span class="deposit-calculator__result-value" data-dsc-out-interest>—</span>
            </div>
            <div class="deposit-calculator__result-row">
                <span class="deposit-calculator__result-label">Эффективная ставка</span>
                <span class="deposit-calculator__result-value" data-dsc-out-effective>—</span>
            </div>
            <div class="deposit-calculator__result-row">
                <span class="deposit-calculator__result-label">Налоги по вкладу (ориентировочно)</span>
                <span class="deposit-calculator__result-value" data-dsc-out-tax>—</span>
            </div>
            <div class="deposit-calculator__result-row">
                <span class="deposit-calculator__result-label">Дата окончания</span>
                <span class="deposit-calculator__result-value" data-dsc-out-end>—</span>
            </div>
        </div>
        <p class="deposit-service-calc__tax-note">
            Налог: не облагаемый минимум 1&nbsp;000&nbsp;000 ₽ × ключевая ставка ЦБ ({{ $cbRate }}% годовых) пропорционально сроку; 13% с суммы превышения. Не является налоговой консультацией.
        </p>

        <h3 class="deposit-service-calc__table-title">График</h3>
        <div class="deposit-service-calc__table-wrap">
            <table class="deposit-service-calc__table">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Дата</th>
                        <th>Начисленные %</th>
                        <th>Пополнение/снятие</th>
                        <th>Сумма вклада</th>
                    </tr>
                </thead>
                <tbody data-dsc-table-body></tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('assets/js/deposit-service-calculator.js') }}"></script>
@endpush
