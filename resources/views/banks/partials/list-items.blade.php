@foreach ($items as $bank)
    @php
        $variant = $variant ?? 'index';
        $logoPath = $bank->logo_square ?? $bank->logo;
        $logoUrl = $logoPath ? (str_starts_with($logoPath, 'http') ? $logoPath : asset('storage/' . $logoPath)) : null;
        $bankSlug = $bank->slug ? url('/banki/' . $bank->slug) : '#';
        $branchesCount = $bank->branches_count ?? null;
        $rating = $bank->rating !== null && $bank->rating !== '' ? (float) $bank->rating : null;
    @endphp

    @if($variant === 'category')
        <div class="karty-card">
            <div class="karty-card__col karty-card__name">
                <div class="karty-card__name-inner">
                    @if($logoUrl)
                        <img class="karty-card__image" src="{{ $logoUrl }}" alt="{{ $bank->name }}" width="101" height="66">
                    @else
                        <div class="karty-card__image karty-card__image-placeholder">—</div>
                    @endif
                    <div class="karty-card__name-block">
                        <div class="karty-card__name-text">{{ $bank->name }}</div>
                        <span class="karty-card__label">Банк</span>
                    </div>
                </div>
            </div>
            <div class="karty-card__col karty-card__action">
                @if($bank->website)
                    <a href="{{ $bank->website }}" class="tf-btn btn-primary2 btn-px-28 height-2 rounded-12" target="_blank" rel="noopener">
                        <span>Сайт</span>
                        <span class="bg-effect"></span>
                    </a>
                @else
                    <a href="{{ $bankSlug }}" class="tf-btn btn-primary2 btn-px-28 height-2 rounded-12">
                        <span>Подробнее</span>
                        <span class="bg-effect"></span>
                    </a>
                @endif
            </div>
        </div>
    @else
        <div class="credit-card credit-card--bank">
            <div class="credit-card__col credit-card__name">
                <div class="credit-card__name-inner">
                    @if($logoUrl)
                        <img class="credit-card__logo" src="{{ $logoUrl }}" alt="{{ $bank->name }}" width="64" height="64">
                    @else
                        <div class="credit-card__logo credit-card__logo-placeholder">—</div>
                    @endif
                    <div class="credit-card__name-block">
                        <div class="credit-card__name-text">{{ $bank->name }}</div>
                        @if($rating !== null)
                            <div class="credit-card__rating">
                                ⭐ {{ number_format($rating, 1, '.', '') }}@if(filled($bank->reviews_count ?? null) && (int) $bank->reviews_count > 0) ({{ number_format((int) $bank->reviews_count, 0, '', ' ') }} отзывов)@endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="credit-card__col">
                <span class="credit-card__label">Телефон</span>
                <span class="credit-card__value">{{ $bank->phone ?: '—' }}</span>
            </div>
            <div class="credit-card__col">
                <span class="credit-card__label">Отделения</span>
                <span class="credit-card__value">{{ $branchesCount ?? '—' }}</span>
            </div>
            <div class="credit-card__col">
                <span class="credit-card__label">Рег номер</span>
                <span class="credit-card__value">{{ $bank->license_number ?: '—' }}</span>
            </div>
            <div class="credit-card__col credit-card__action">
                <a href="{{ $bankSlug }}" class="tf-btn btn-primary2 btn-px-28 height-2 rounded-12">
                    <span>Подробнее</span>
                    <span class="bg-effect"></span>
                </a>
            </div>
        </div>
    @endif
@endforeach
