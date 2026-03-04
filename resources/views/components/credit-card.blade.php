@php
    /** @var \App\Models\Credit $item */
    $bank = $item->bank;
    $bankName = $bank ? ($bank->name ?: '') : '';
    $creditName = $item->name ?: '—';
    $logoPath = $bank && filled($bank->logo_square ?? null) ? asset('storage/' . $bank->logo_square) : ($bank && filled($bank->logo ?? null) ? asset('storage/' . $bank->logo) : null);

    $rate = $item->rate !== null && $item->rate !== '' ? $item->rate . '%' : '—';
    $psk = $item->psk !== null && $item->psk !== '' ? $item->psk . '%' : '—';
    $maxAmount = $item->max_amount !== null && $item->max_amount !== '' ? number_format((float) $item->max_amount, 0, '', ' ') . ' ₽' : '—';
    $term = $item->term_months !== null && $item->term_months !== '' ? $item->term_months . ' мес.' : '—';

    $detailUrl = route('credits.category.show', $item->slug);
@endphp

<div class="credit-card">
    <div class="credit-card__col credit-card__name">
        <div class="credit-card__name-inner">
            @if($logoPath)
                <img class="credit-card__logo" src="{{ $logoPath }}" alt="{{ $bankName }}" width="64" height="64">
            @else
                <div class="credit-card__logo credit-card__logo-placeholder" aria-hidden="true">—</div>
            @endif
            <div class="credit-card__name-block">
                <div class="credit-card__name-text">{{ $bankName ?: '—' }}</div>
                <span class="credit-card__label">{{ $creditName }}</span>
            </div>
        </div>
    </div>

    <div class="credit-card__col credit-card__psc">
        <span class="credit-card__label">ПСК</span>
        <span class="credit-card__value">{{ $psk }}</span>
    </div>

    <div class="credit-card__col credit-card__rate">
        <span class="credit-card__label">Ставка</span>
        <span class="credit-card__value">{{ $rate }}</span>
    </div>

    <div class="credit-card__col credit-card__amount">
        <span class="credit-card__label">Сумма</span>
        <span class="credit-card__value">{{ $maxAmount }}</span>
    </div>

    <div class="credit-card__col credit-card__term">
        <span class="credit-card__label">Срок</span>
        <span class="credit-card__value">{{ $term }}</span>
    </div>

    <div class="credit-card__col credit-card__action">
        <a href="{{ $detailUrl }}" class="tf-btn btn-primary2 btn-px-28 height-2 rounded-12">
            <span>Подробнее</span>
            <span class="bg-effect"></span>
        </a>
    </div>
</div>
