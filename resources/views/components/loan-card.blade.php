@php
    /** @var \App\Models\Loan $item */
    $companyName = $item->company_name ?: '—';
    $loanName = $item->name ?: '—';
    $rate = $item->rate !== null && $item->rate !== '' ? $item->rate . '%' : '—';
    $psk = $item->psk !== null && $item->psk !== '' ? $item->psk . '%' : '—';
    $maxAmount = $item->max_amount !== null && $item->max_amount !== '' ? number_format((float) $item->max_amount, 0, '', ' ') . ' ₽' : '—';
    $termDays = $item->term_days !== null && $item->term_days !== '' ? $item->term_days . ' дн.' : '—';
    $logo = $item->logo ? asset('storage/' . $item->logo) : null;
    $url = $item->slug ? route('loans.category.show', $item->slug) : (filled($item->website) ? $item->website : '#');
@endphp

<div class="tf-box-icon style-7 v2 effect-icon loan-card">
    <div class="loan-card__header d-flex align-items-center gap_12 mb_24">
        @if($logo)
            <img class="loan-card__logo" src="{{ $logo }}" alt="{{ $companyName }}" width="64" height="64" style="width: 64px; height: 64px; object-fit: contain; flex-shrink: 0;">
        @else
            <div class="loan-card__logo loan-card__logo-placeholder d-flex align-items-center justify-content-center rounded-12 text_mono-gray-5" style="width: 64px; height: 64px; flex-shrink: 0; background: var(--Mono-gray-2); font-size: 1.25rem;">—</div>
        @endif
        <div>
            <h5 class="title fw-5 text_mono-dark-9 mb-0">{{ $companyName }}</h5>
            <p class="text-body-2 text_mono-gray-7 mb-0">{{ $loanName }}</p>
        </div>
    </div>
    <div class="loan-card__specs d-grid gap_16 mb_24" style="grid-template-columns: repeat(2, 1fr);">
        <div class="loan-card__spec">
            <span class="d-block text-body-2 text_mono-gray-7 mb_8">Сумма до</span>
            <span class="text-body-1 fw-5 text_mono-dark-9">{{ $maxAmount }}</span>
        </div>
        <div class="loan-card__spec">
            <span class="d-block text-body-2 text_mono-gray-7 mb_8">Срок (дни)</span>
            <span class="text-body-1 fw-5 text_mono-dark-9">{{ $termDays }}</span>
        </div>
        <div class="loan-card__spec">
            <span class="d-block text-body-2 text_mono-gray-7 mb_8">Ставка</span>
            <span class="text-body-1 fw-5 text_mono-dark-9">{{ $rate }}</span>
        </div>
        <div class="loan-card__spec">
            <span class="d-block text-body-2 text_mono-gray-7 mb_8">ПСК</span>
            <span class="text-body-1 fw-5 text_mono-dark-9">{{ $psk }}</span>
        </div>
    </div>
    <a href="{{ $url }}" class="tf-btn btn-primary2 btn-px-28 height-2 rounded-12" @if($url !== '#' && !$item->slug) target="_blank" rel="noopener noreferrer" @endif>
        <span>Получить деньги</span>
        <span class="bg-effect"></span>
    </a>
</div>
