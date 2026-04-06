<div x-data="{ open: $wire.entangle('showModal').live }" class="cbr-rates-box home-quick-pick-card">

    {{-- ══════════════════════════════════════════════════════════════
         КАРТОЧКА ПОДБОРА КРЕДИТА
    ══════════════════════════════════════════════════════════════ --}}

    @if($submitted)
        {{-- Успешная отправка --}}
        <div class="d-flex flex-column align-items-center justify-content-center py-4 gap_16">
            <div class="credit-lead-success-icon" aria-hidden="true">
                <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                    <circle cx="24" cy="24" r="24" fill="#e8f5e9"/>
                    <path d="M14 24l8 8 12-14" stroke="#2e7d32" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <p class="text-body-1 fw-6 text_mono-dark-9 text-center mb-0">Спасибо! Ваша заявка отправлена.</p>
            <p class="text-body-3 text_mono-gray-6 text-center mb-0">Мы передали данные в банк. Ожидайте звонка.</p>
        </div>
    @else
        <h6 class="text_mono-dark-9 fw-6 mb_16">Подбор кредита</h6>

        <div class="home-quick-pick-field mb_16">
            <label class="text-body-3 text_mono-gray-7 d-block mb_8" for="clm-purpose">Цель кредита</label>
            <select id="clm-purpose" wire:model="purpose" class="form-select home-quick-pick-select">
                <option value="">На любые цели</option>
                @if($refinanceSlug !== '')
                    <option value="{{ $refinanceSlug }}">Рефинансирование</option>
                @endif
                @if($businessSlug !== '')
                    <option value="{{ $businessSlug }}">Для бизнеса</option>
                @endif
            </select>
        </div>

        <div class="home-quick-pick-field mb_16">
            <label class="text-body-3 text_mono-gray-7 d-block mb_8" for="clm-amount">Сумма</label>
            <div class="home-quick-pick-input-wrap">
                <input
                    type="number"
                    id="clm-amount"
                    wire:model="amount"
                    class="form-control home-quick-pick-input @error('amount') is-invalid @enderror"
                    min="1"
                    max="100000000"
                    step="1"
                    inputmode="numeric"
                >
                <span class="home-quick-pick-suffix">₽</span>
            </div>
            @error('amount')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="home-quick-pick-field mb_24">
            <label class="text-body-3 text_mono-gray-7 d-block mb_8" for="clm-term">Срок</label>
            <div class="home-quick-pick-input-wrap">
                <input
                    type="number"
                    id="clm-term"
                    wire:model="termYears"
                    class="form-control home-quick-pick-input @error('termYears') is-invalid @enderror"
                    min="1"
                    max="30"
                    step="1"
                    inputmode="numeric"
                >
                <span class="home-quick-pick-suffix">лет</span>
            </div>
            @error('termYears')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <button
            type="button"
            wire:click="openModal"
            wire:loading.attr="disabled"
            class="tf-btn btn-primary2 btn-px-28 height-2 rounded-12 w-100"
        >
            <span wire:loading.remove wire:target="openModal">Подобрать</span>
            <span wire:loading wire:target="openModal">Проверка…</span>
            <span class="bg-effect"></span>
        </button>
    @endif

    {{-- ══════════════════════════════════════════════════════════════
         МОДАЛЬНОЕ ОКНО (Alpine x-show, телепортируется в <body>)
    ══════════════════════════════════════════════════════════════ --}}
    <template x-teleport="body">
        <div x-show="open" x-cloak style="display:none;">

            {{-- Backdrop --}}
            <div
                class="position-fixed top-0 start-0 w-100 h-100"
                style="background:rgba(0,0,0,.55);z-index:1055;"
                x-transition:enter="transition-opacity duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="$wire.closeModal()"
                aria-hidden="true"
            ></div>

            {{-- Dialog --}}
            <div
                class="position-fixed top-50 start-50 translate-middle w-100"
                style="z-index:1056;max-width:460px;padding:0 1rem;"
                x-transition:enter="transition duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                role="dialog"
                aria-modal="true"
                aria-labelledby="clm-modal-title"
            >
                <div class="card rounded-4 shadow-lg p-4 p-md-5 border-0">

                    {{-- Заголовок --}}
                    <div class="d-flex align-items-center justify-content-between mb_24">
                        <h5 id="clm-modal-title" class="text_mono-dark-9 fw-7 mb-0">Введите ваши данные</h5>
                        <button
                            type="button"
                            class="btn-close"
                            aria-label="Закрыть"
                            @click="$wire.closeModal()"
                        ></button>
                    </div>

                    <p class="text-body-3 text_mono-gray-6 mb_24">
                        Банк свяжется с вами для подтверждения заявки.
                    </p>

                    {{-- Форма --}}
                    <form wire:submit="submit" novalidate>

                        {{-- Имя --}}
                        <div class="mb_16">
                            <label class="text-body-3 text_mono-gray-7 d-block mb_8" for="clm-firstName">
                                Имя <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                id="clm-firstName"
                                wire:model="firstName"
                                class="form-control @error('firstName') is-invalid @enderror"
                                placeholder="Иван"
                                autocomplete="given-name"
                            >
                            @error('firstName')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Телефон --}}
                        <div class="mb_24">
                            <label class="text-body-3 text_mono-gray-7 d-block mb_8" for="clm-phone">
                                Телефон <span class="text-danger">*</span>
                            </label>
                            <input
                                type="tel"
                                id="clm-phone"
                                wire:model="phone"
                                class="form-control @error('phone') is-invalid @enderror"
                                placeholder="+7 (___) ___-__-__"
                                autocomplete="tel"
                                x-on:input="
                                    let raw = $event.target.value.replace(/\D/g, '');
                                    if (raw.startsWith('7') || raw.startsWith('8')) raw = raw.slice(1);
                                    raw = raw.slice(0, 10);
                                    let fmt = '';
                                    if (raw.length > 0) fmt = '+7 (' + raw.slice(0,3);
                                    if (raw.length >= 4) fmt += ') ' + raw.slice(3,6);
                                    if (raw.length >= 7) fmt += '-' + raw.slice(6,8);
                                    if (raw.length >= 9) fmt += '-' + raw.slice(8,10);
                                    $event.target.value = fmt;
                                    $wire.phone = fmt;
                                "
                            >
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Ошибка API --}}
                        @if($errorMessage)
                            <div class="alert alert-danger py-2 text-body-3 mb_16" role="alert">
                                {{ $errorMessage }}
                            </div>
                        @endif

                        {{-- Кнопка --}}
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="tf-btn btn-primary2 btn-px-28 height-2 rounded-12 w-100"
                        >
                            <span wire:loading.remove wire:target="submit">Отправить заявку</span>
                            <span wire:loading wire:target="submit">Отправка…</span>
                            <span class="bg-effect"></span>
                        </button>

                        <p class="text-body-4 text_mono-gray-5 text-center mt_16 mb-0">
                            Нажимая «Отправить», вы даёте согласие на обработку персональных данных.
                        </p>

                    </form>
                </div>
            </div>

        </div>
    </template>

</div>
