@extends('layouts.app')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $service->title,
    'subtitle' => $pageData['date_label'] ? 'Официальные курсы '.$pageData['date_label'] : null,
    'breadcrumbs' => [
        ['url' => url('/'), 'label' => 'Главная'],
        ['label' => $service->title],
    ],
])
@endsection

@section('content')
<div class="main-content style-1">
    <div class="section-opportunities tf-spacing-27">
        <div class="tf-container">
            <div class="cbr-page">
                <aside class="cbr-page__aside" aria-label="Быстрая навигация по валютам">
                    <div class="cbr-page__aside-inner">
                        <p class="cbr-page__aside-lead text_mono-gray-7">
                            Курсы подгружаются из нашей базы; актуализация — по расписанию с официального API курсов ЦБ РФ (ежедневно).
                        </p>
                        @if(!empty($pageData['rows']))
                            <nav class="cbr-page__nav">
                                @foreach($pageData['rows'] as $row)
                                    <a href="#cbr-{{ strtolower($row['code']) }}" class="cbr-page__nav-link">{{ $row['name'] }}</a>
                                @endforeach
                            </nav>
                        @endif
                    </div>
                </aside>
                <div class="cbr-page__main">
                    @forelse($pageData['rows'] as $row)
                        <article
                            id="cbr-{{ strtolower($row['code']) }}"
                            class="cbr-page__card"
                            data-currency-convert
                            data-rate="{{ $row['rate'] !== null ? e($row['rate']) : '' }}"
                        >
                            <div class="cbr-page__card-head">
                                <h2 class="cbr-page__card-title">{{ $row['name'] }} — рубль</h2>
                                @if($row['change'] !== null)
                                    <span class="cbr-page__change {{ $row['change_positive'] ? 'is-up' : 'is-down' }}">
                                        {{ $row['change_positive'] ? '+' : '' }}{{ number_format((float) $row['change'], 4, ',', ' ') }}
                                    </span>
                                @endif
                            </div>
                            @if($row['rate'] !== null)
                                <p class="cbr-page__rate-line text_mono-gray-7">
                                    Курс ЦБ: <strong class="text_black">{{ number_format((float) $row['rate'], 4, ',', ' ') }} ₽</strong> за 1 {{ $row['code'] }}
                                </p>
                                <div class="cbr-page__calc">
                                    <label class="cbr-page__calc-label" for="cbr-input-{{ strtolower($row['code']) }}">Сумма в {{ $row['code'] }}</label>
                                    <div class="cbr-page__calc-row">
                                        <input
                                            type="text"
                                            inputmode="decimal"
                                            class="cbr-page__calc-input"
                                            id="cbr-input-{{ strtolower($row['code']) }}"
                                            data-cbr-amount
                                            value="1"
                                            autocomplete="off"
                                            aria-describedby="cbr-out-{{ strtolower($row['code']) }}-desc"
                                        >
                                        <span class="cbr-page__calc-eq">=</span>
                                        <div class="cbr-page__calc-result-wrap">
                                            <span class="cbr-page__calc-result" data-rub-out>—</span>
                                            <span class="cbr-page__calc-rub">₽</span>
                                        </div>
                                    </div>
                                    <span class="visually-hidden" id="cbr-out-{{ strtolower($row['code']) }}-desc">Результат в рублях по курсу ЦБ</span>
                                </div>
                            @else
                                <p class="text_mono-gray-7">Курс недоступен в базе за выбранную дату. Выполните <code class="cbr-page__code">php artisan cbr:fetch-rates</code>.</p>
                            @endif
                        </article>
                    @empty
                        <p class="text_mono-gray-7">Нет данных о курсах. Запустите загрузку: <code class="cbr-page__code">php artisan cbr:fetch-rates</code>.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @if(collect($pageData['rows'])->contains(fn ($r) => $r['rate'] !== null))
        <script src="{{ asset('assets/js/currency-rates-page.js') }}" defer></script>
    @endif
@endpush
