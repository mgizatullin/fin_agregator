@php
    /** @var \App\Models\Deposit $item */
    $bankName = $item->bank ? ($item->bank->name ?: '-') : '-';
    $depositName = $item->name ?: '-';
    $rate = $item->rate !== null && $item->rate !== '' ? $item->rate . '%' : '-';
    $termMonths = $item->term_months !== null && $item->term_months !== '' ? $item->term_months . ' мес.' : '-';
    $minAmount = $item->min_amount !== null && $item->min_amount !== '' ? 'от ' . number_format((float) $item->min_amount, 0, '', ' ') . ' ₽' : '-';
    $replenishment = $item->replenishment ? 'Да' : 'Нет';
    $logo = ($item->bank && $item->bank->logo) ? asset('storage/' . $item->bank->logo) : null;
@endphp

<div class="karty-card">
    <div class="karty-card__col karty-card__name">
        <div class="karty-card__name-inner">
            @if($logo)
                <img class="karty-card__image" src="{{ $logo }}" alt="{{ $bankName }}" width="101" height="66">
            @else
                <div class="karty-card__image karty-card__image-placeholder">—</div>
            @endif
            <div class="karty-card__name-block">
                <div class="karty-card__name-text">{{ $bankName }}</div>
                <span class="karty-card__label">{{ $depositName }}</span>
            </div>
        </div>
    </div>
    <div class="karty-card__col">
        <span class="karty-card__label">Ставка</span>
        <span class="karty-card__value">{{ $rate }}</span>
    </div>
    <div class="karty-card__col">
        <span class="karty-card__label">Срок</span>
        <span class="karty-card__value">{{ $termMonths }}</span>
    </div>
    <div class="karty-card__col">
        <span class="karty-card__label">Мин. сумма</span>
        <span class="karty-card__value">{{ $minAmount }}</span>
    </div>
    <div class="karty-card__col">
        <span class="karty-card__label">Пополнение</span>
        <span class="karty-card__value">{{ $replenishment }}</span>
    </div>
    <div class="karty-card__col karty-card__action">
        <a href="#" class="tf-btn btn-primary2 btn-px-28 height-2 rounded-12">
            <span>Подробнее</span>
            <span class="bg-effect"></span>
        </a>
    </div>
</div>

