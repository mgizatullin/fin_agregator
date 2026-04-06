<div id="credit-service-calculator" class="credit-service-calc">
    <div class="credit-service-calc__grid">
        <div class="deposit-service-calc__field">
            <label class="deposit-calculator__label" for="csc-amount">Сумма кредита, ₽</label>
            <input type="text" id="csc-amount" class="deposit-calculator__input deposit-calculator__input--no-spinner" data-csc-amount inputmode="numeric" autocomplete="off" value="500 000">
        </div>

        <div class="deposit-service-calc__field deposit-service-calc__field--term">
            <span class="deposit-calculator__label">Срок</span>
            <div class="deposit-service-calc__term-row">
                <input type="text" class="deposit-calculator__input deposit-calculator__input--no-spinner" data-csc-term inputmode="numeric" value="12" autocomplete="off">
                <select class="deposit-service-calc__select" data-csc-term-unit>
                    <option value="months" selected>Месяцев</option>
                    <option value="years">Лет</option>
                </select>
            </div>
        </div>

        <div class="deposit-service-calc__field">
            <label class="deposit-calculator__label" for="csc-rate">Ставка, % годовых</label>
            <input type="text" id="csc-rate" class="deposit-calculator__input deposit-calculator__input--no-spinner" data-csc-rate inputmode="decimal" autocomplete="off" value="12">
        </div>
    </div>

    <div class="deposit-service-calc__actions">
        <button type="button" class="deposit-service-calc__btn-calc" data-csc-calc>Рассчитать</button>
    </div>

    <div class="deposit-service-calc__error text_mono-gray-7" data-csc-error style="display:none;margin-top:12px;"></div>

    <div class="deposit-service-calc__result" data-csc-result>
        <h3 class="deposit-service-calc__result-title">Результат</h3>
        <div class="deposit-calculator__results">
            <div class="deposit-calculator__result-row">
                <span class="deposit-calculator__result-label">Ежемесячный платёж</span>
                <span class="deposit-calculator__result-value" data-csc-out-monthly>—</span>
            </div>
            <div class="deposit-calculator__result-row">
                <span class="deposit-calculator__result-label">Сумма к возврату</span>
                <span class="deposit-calculator__result-value" data-csc-out-total>—</span>
            </div>
            <div class="deposit-calculator__result-row">
                <span class="deposit-calculator__result-label">Переплата</span>
                <span class="deposit-calculator__result-value deposit-calculator__result-value--total" data-csc-out-overpay>—</span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('assets/js/credit-service-calculator.js') }}" defer></script>
@endpush
