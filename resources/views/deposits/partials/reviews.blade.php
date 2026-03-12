@php
    /** @var \App\Models\Deposit $deposit */
    /** @var \Illuminate\Support\Collection $banks */
    $reviews = $deposit->reviews->where('is_published', true)->values();
    $avgRating = $reviews->count() ? round($reviews->avg('rating'), 1) : null;
    $reviewFormErrors = $errors->hasAny(['body', 'name', 'rating', 'email', 'personal_data_consent']);
    $serviceOptions = ['Вклад', 'Кредит', 'Дебетовая карта', 'Кредитная карта'];
@endphp

<section id="deposit-reviews" class="section-reviews tf-spacing-27">
    <div class="section-heading">
        <h3 class="section-reviews__title">Отзывы по вкладу</h3>
        @if($avgRating)
            <div class="section-heading__meta">
                <div class="review-stars" aria-label="Средний рейтинг">
                    @for($i = 1; $i <= 5; $i++)
                        <span class="review-stars__star {{ $i <= round($avgRating) ? 'is-filled' : '' }}">★</span>
                    @endfor
                </div>
                <span class="section-heading__rating-text">{{ number_format($avgRating, 1, '.', ' ') }} из 5</span>
                <span class="section-heading__count">{{ $reviews->count() }} {{ \Illuminate\Support\Str::of('отзыв')->plural($reviews->count()) }}</span>
            </div>
        @endif
    </div>

    @if($reviews->isNotEmpty())
        <div class="reviews-list">
            @foreach($reviews as $review)
                @php
                    $serviceLabel = ($review->service ?? '') === 'deposit' || ($review->service ?? '') === 'Вклад' || empty($review->service) ? 'Вклад' : $review->service;
                @endphp
                <article class="review-card">
                    <header class="review-card__header">
                        <h4 class="review-card__title">{{ $review->title ?: 'Без заголовка' }}</h4>
                        <span class="review-card__header-meta">
                            <span>{{ $review->bank?->name ?? $deposit->bank?->name }}</span>
                            <span>·</span>
                            <span>{{ $serviceLabel }}@if($serviceLabel === 'Вклад'): {{ $deposit->name }}@endif</span>
                        </span>
                        <div class="review-stars review-stars--readonly" aria-label="Оценка: {{ $review->rating }} из 5">
                            @for($i = 1; $i <= 5; $i++)
                                <span class="review-stars__star {{ $i <= $review->rating ? 'is-filled' : '' }}">★</span>
                            @endfor
                        </div>
                    </header>
                    <div class="review-card__body">
                        <p>{{ $review->body }}</p>
                    </div>
                    <footer class="review-card__footer">
                        <span class="review-card__author">{{ $review->name }}</span>
                    </footer>
                </article>
            @endforeach
        </div>
    @else
        <p class="reviews-empty">Пока нет отзывов об этом вкладе. Станьте первым, кто поделится мнением.</p>
    @endif

    <div class="review-form-wrapper">
        <h3 class="review-form__title">Оставить отзыв</h3>
        <form action="{{ route('deposits.reviews.store', $deposit) }}" method="post" class="review-form" id="deposit-review-form">
            @csrf
            <div class="review-form__grid">
                <div class="review-form__field">
                    <label class="review-form__label" for="review-title">Заголовок</label>
                    <input id="review-title" type="text" name="title" class="review-form__input" value="{{ old('title') }}">
                </div>
                <div class="review-form__field">
                    <label class="review-form__label">Банк</label>
                    <input type="hidden" name="bank_id" value="{{ $deposit->bank_id ?? '' }}">
                    <span class="review-form__input review-form__input--static">{{ $deposit->bank?->name ?? '—' }}</span>
                    @error('bank_id')<span class="review-form__error-text">{{ $message }}</span>@enderror
                </div>
                <div class="review-form__field">
                    <label class="review-form__label">Услуга</label>
                    <input type="hidden" name="service" value="Вклад">
                    <span class="review-form__input review-form__input--static">Вклад</span>
                    @error('service')<span class="review-form__error-text">{{ $message }}</span>@enderror
                </div>
                <div class="review-form__field review-form__field--full {{ $reviewFormErrors ? 'review-form__field--error' : '' }}">
                    <label class="review-form__label" for="review-body">Отзыв <span class="review-form__required">*</span></label>
                    <textarea id="review-body" name="body" rows="4" class="review-form__textarea" required>{{ old('body') }}</textarea>
                    @error('body')<span class="review-form__error-text">{{ $message }}</span>@enderror
                </div>
                <div class="review-form__field review-form__field--full {{ $reviewFormErrors ? 'review-form__field--error' : '' }}">
                    <span class="review-form__label">Оценка <span class="review-form__required">*</span></span>
                    <div class="review-form-stars" role="group" aria-label="Оценка от 1 до 5">
                        <input type="hidden" name="rating" id="review-rating-value" value="{{ old('rating', '') }}">
                        @for($i = 1; $i <= 5; $i++)
                            <button type="button" class="review-form-stars__btn" data-value="{{ $i }}" aria-label="{{ $i }} из 5">★</button>
                        @endfor
                    </div>
                    @error('rating')<span class="review-form__error-text">{{ $message }}</span>@enderror
                </div>
                <div class="review-form__field {{ $reviewFormErrors ? 'review-form__field--error' : '' }}">
                    <label class="review-form__label" for="review-name">Имя <span class="review-form__required">*</span></label>
                    <input id="review-name" type="text" name="name" class="review-form__input" value="{{ old('name') }}" required>
                    @error('name')<span class="review-form__error-text">{{ $message }}</span>@enderror
                </div>
                <div class="review-form__field {{ $reviewFormErrors ? 'review-form__field--error' : '' }}">
                    <label class="review-form__label" for="review-email">E-mail <span class="review-form__required">*</span></label>
                    <input id="review-email" type="email" name="email" class="review-form__input" value="{{ old('email') }}" required>
                    @error('email')<span class="review-form__error-text">{{ $message }}</span>@enderror
                </div>
                <div class="review-form__field">
                    <label class="review-form__label" for="review-phone">Телефон</label>
                    <input id="review-phone" type="text" name="phone" class="review-form__input" value="{{ old('phone') }}">
                    @error('phone')<span class="review-form__error-text">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="review-form__consent {{ $reviewFormErrors && $errors->has('personal_data_consent') ? 'review-form__field--error' : '' }}">
                <label class="review-form__consent-label">
                    <input type="checkbox" name="personal_data_consent" value="1" class="review-form__consent-checkbox" {{ old('personal_data_consent') ? 'checked' : '' }} required>
                    <span>Я согласен на <a href="#" class="review-form__consent-link">обработку персональных данных</a></span>
                </label>
                @error('personal_data_consent')<span class="review-form__error-text">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="tf-btn btn-primary2 btn-px-28 height-2 rounded-12 review-form__submit">
                <span>Отправить отзыв</span>
                <span class="bg-effect"></span>
            </button>
        </form>
    </div>
</section>

<script>
(function() {
    var form = document.getElementById('deposit-review-form');
    if (!form) return;
    var starsWrap = form.querySelector('.review-form-stars');
    var hiddenInput = form.querySelector('#review-rating-value');
    var buttons = form.querySelectorAll('.review-form-stars__btn');
    var currentValue = hiddenInput ? parseInt(hiddenInput.value, 10) || 0 : 0;

    function updateStars(value) {
        currentValue = value;
        if (hiddenInput) hiddenInput.value = value || '';
        buttons.forEach(function(btn, i) {
            btn.classList.toggle('is-filled', (i + 1) <= value);
        });
    }

    if (starsWrap && buttons.length) {
        updateStars(currentValue);
        starsWrap.addEventListener('click', function(e) {
            var btn = e.target.closest('.review-form-stars__btn');
            if (btn) updateStars(parseInt(btn.getAttribute('data-value'), 10));
        });
        starsWrap.addEventListener('mouseleave', function() {
            updateStars(hiddenInput ? parseInt(hiddenInput.value, 10) || 0 : 0);
        });
        starsWrap.addEventListener('mouseenter', function(e) {
            var btn = e.target.closest('.review-form-stars__btn');
            if (btn) {
                var v = parseInt(btn.getAttribute('data-value'), 10);
                buttons.forEach(function(b, i) {
                    b.classList.toggle('is-filled', (i + 1) <= v);
                });
            }
        });
    }
})();
</script>
