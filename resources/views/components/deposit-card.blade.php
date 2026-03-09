@php
    /** @var \App\Models\Deposit $item */
    $bankName = $item->bank ? ($item->bank->name ?: '-') : '-';
    $depositName = $item->name ?: '-';
    $rate = $item->rate !== null && $item->rate !== '' ? $item->rate . '%' : '-';
    $termMonths = $item->term_months !== null && $item->term_months !== '' ? $item->term_months . ' мес.' : '-';
    $minAmount = $item->min_amount !== null && $item->min_amount !== '' ? 'от ' . number_format((float) $item->min_amount, 0, '', ' ') . ' ₽' : '-';
    $replenishment = $item->replenishment ? 'Да' : 'Нет';
    $logoPath = $item->bank->logo_square ?? $item->bank->logo ?? null;
    $logo = $logoPath ? (str_starts_with($logoPath, 'http') ? $logoPath : asset('storage/' . $logoPath)) : null;
    $detailUrl = $item->slug ? url('/vklady/' . $item->slug) : '#';
@endphp

<div class="deposit-card">
    <div class="deposit-card__col deposit-card__name">
        <div class="deposit-card__name-inner">
            @if($logo)
                <img class="deposit-card__logo" src="{{ $logo }}" alt="{{ $bankName }}" width="64" height="64">
            @else
                <div class="deposit-card__logo deposit-card__logo-placeholder">—</div>
            @endif
            <div class="deposit-card__name-block">
                <div class="deposit-card__name-text">{{ $bankName }}</div>
                <span class="deposit-card__label">{{ $depositName }}</span>
            </div>
        </div>
    </div>
    <div class="deposit-card__col">
        <span class="deposit-card__label">Ставка</span>
        <span class="deposit-card__value">{{ $rate }}</span>
    </div>
    <div class="deposit-card__col">
        <span class="deposit-card__label">Срок</span>
        <span class="deposit-card__value">{{ $termMonths }}</span>
    </div>
    <div class="deposit-card__col">
        <span class="deposit-card__label">Мин. сумма</span>
        <span class="deposit-card__value">{{ $minAmount }}</span>
    </div>
    <div class="deposit-card__col">
        <span class="deposit-card__label">Пополнение</span>
        <span class="deposit-card__value">{{ $replenishment }}</span>
    </div>
    <div class="deposit-card__col deposit-card__action">
        <a href="{{ $detailUrl }}" class="tf-btn btn-primary2 btn-px-28 height-2 rounded-12">
            <span>Подробнее</span>
            <span class="bg-effect"></span>
        </a>
    </div>
</div>
