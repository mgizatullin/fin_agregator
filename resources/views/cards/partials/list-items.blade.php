@foreach ($items as $card)
    @php
        $bankName = $card->bank ? ($card->bank->name ?: '-') : '-';
        $cardName = $card->name ?: '-';
        $gracePeriod = $card->grace_period !== null && $card->grace_period !== '' ? $card->grace_period . ' дн.' : '-';
        $creditLimit = $card->credit_limit !== null && $card->credit_limit !== '' ? 'до ' . number_format((float) $card->credit_limit, 0, '', ' ') . ' ₽' : '-';
        $annualFee = $card->annual_fee !== null && $card->annual_fee !== '' ? $card->annual_fee . ' ₽' : '-';
        $rate = $card->rate !== null && $card->rate !== '' ? $card->rate . '%' : '-';
        $cardUrl = $card->slug ? url_canonical(route('cards.show', $card->slug)) : '#';
        $cardImage = $card->image ? asset('storage/' . $card->image) : null;
    @endphp
    <div class="karty-card">
        <div class="karty-card__col karty-card__name">
            <div class="karty-card__name-inner">
                @if($cardImage)
                    <img class="karty-card__image" src="{{ $cardImage }}" alt="{{ $cardName }}" width="101" height="66">
                @else
                    <div class="karty-card__image karty-card__image-placeholder">—</div>
                @endif
                <div class="karty-card__name-block">
                    <div class="karty-card__name-text">{{ $bankName }}</div>
                    <span class="karty-card__label">{{ $cardName }}</span>
                </div>
            </div>
        </div>
        <div class="karty-card__col">
            <span class="karty-card__label">Льготный период</span>
            <span class="karty-card__value">{{ $gracePeriod }}</span>
        </div>
        <div class="karty-card__col">
            <span class="karty-card__label">Кредитный лимит</span>
            <span class="karty-card__value">{{ $creditLimit }}</span>
        </div>
        <div class="karty-card__col">
            <span class="karty-card__label">Годовое обслуживание</span>
            <span class="karty-card__value">{{ $annualFee }}</span>
        </div>
        <div class="karty-card__col">
            <span class="karty-card__label">Ставка</span>
            <span class="karty-card__value">{{ $rate }}</span>
        </div>
        <div class="karty-card__col karty-card__action">
            <div class="d-flex align-items-center gap_12 flex-wrap">
                <a href="{{ $cardUrl }}" class="card-action__info-btn" title="Подробнее" aria-label="Подробнее">i</a>
                <a href="{{ $cardUrl }}" class="tf-btn btn-primary2 btn-px-28 height-2 rounded-12">
                    <span>Оформить карту</span>
                    <span class="bg-effect"></span>
                </a>
            </div>
        </div>
    </div>
@endforeach
