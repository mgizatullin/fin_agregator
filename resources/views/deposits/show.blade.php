@extends('layouts.app')

@push('styles')
<style>
.deposit-rates-table { width: 100%; border-collapse: collapse; }
.deposit-rates-table th, .deposit-rates-table td { padding: 8px 12px; text-align: left; border: 1px solid rgba(25,20,0,0.12); }
.deposit-rates-table th { background: rgba(25,20,0,0.04); font-weight: 600; }
.deposit-rates-currency__title { font-size: 1.125rem; font-weight: 600; margin-bottom: 12px; }
.deposit-rates-range-heading { margin: 12px 0 8px 0; }

/* Матрица ставок и табы валют */
.deposit-rates-section { margin-top: 1.5rem; }
.deposit-rates-tabs__head { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem; border-bottom: 1px solid rgba(25,20,0,0.12); }
.deposit-rates-tabs__tab { padding: 0.5rem 1rem; background: transparent; border: none; border-bottom: 2px solid transparent; cursor: pointer; font-weight: 500; color: inherit; }
.deposit-rates-tabs__tab:hover { opacity: 0.85; }
.deposit-rates-tabs__tab.is-active { border-bottom-color: currentColor; }
.deposit-rates-tabs__pane { display: none; }
.deposit-rates-tabs__pane.is-active { display: block; }
.deposit-rates.deposit-rates--desc { width: 100%; overflow-x: auto; }
.deposit-rates table { width: 100%; border-collapse: collapse; }
.deposit-rates th, .deposit-rates td { padding: 8px 12px; text-align: left; border: 1px solid rgba(25,20,0,0.12); }
.deposit-rates th { background: rgba(25,20,0,0.04); font-weight: 600; }
.deposit-rates__sub-text { font-size: 0.875em; opacity: 0.8; }

.deposit-currency-tabs { display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap; }
.deposit-currency-tabs__btn { padding: 0.4rem 0.8rem; border: 1px solid rgba(25,20,0,0.2); background: transparent; cursor: pointer; font-size: 1.1rem; border-radius: 4px; }
.deposit-currency-tabs__btn:hover { background: rgba(25,20,0,0.05); }
.deposit-currency-tabs__btn.is-active { background: rgba(25,20,0,0.1); border-color: rgba(25,20,0,0.3); font-weight: 600; }

/* Ряд: таблица ставок | калькулятор */
.deposit-rates-calculator-row { display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem; align-items: start; margin-top: 1.5rem; }
@media (max-width: 900px) { .deposit-rates-calculator-row { grid-template-columns: 1fr; } }
.deposit-rates-col { min-width: 0; }
.deposit-calculator-col { min-width: 0; }

/* Калькулятор */
.deposit-calculator { background: rgba(25,20,0,0.03); border: 1px solid rgba(25,20,0,0.12); border-radius: 8px; padding: 1.25rem; }
.deposit-calculator__title { font-size: 1.125rem; font-weight: 600; margin: 0 0 1rem 0; }
.deposit-calculator__currency { margin-bottom: 1rem; }
.deposit-calculator__label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.35rem; color: rgba(25,20,0,0.7); }
.deposit-calculator__currency-btns { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.deposit-calculator__currency-btn { padding: 0.4rem 0.75rem; border: 1px solid rgba(25,20,0,0.25); background: #fff; cursor: pointer; font-size: 1rem; border-radius: 4px; }
.deposit-calculator__currency-btn:hover { background: rgba(25,20,0,0.04); }
.deposit-calculator__currency-btn.is-active { background: rgba(25,20,0,0.1); border-color: rgba(25,20,0,0.35); font-weight: 600; }
.deposit-calculator__field { margin-bottom: 1rem; }
.deposit-calculator__input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid rgba(25,20,0,0.2); border-radius: 4px; font-size: 1rem; box-sizing: border-box; }
.deposit-calculator__results { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(25,20,0,0.1); }
.deposit-calculator__result-row { display: flex; justify-content: space-between; align-items: baseline; gap: 0.5rem; margin-bottom: 0.5rem; }
.deposit-calculator__result-row:last-child { margin-bottom: 0; }
.deposit-calculator__result-label { font-size: 0.875rem; color: rgba(25,20,0,0.7); }
.deposit-calculator__result-value { font-weight: 600; }
.deposit-calculator__result-value--total { font-size: 1.1rem; }
</style>
@endpush

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $deposit->name ?? $deposit->title ?? $section->title ?? 'Вклад',
    'subtitle' => $section->subtitle ?? null,
    'breadcrumbs' => [
        ['url' => url('/'), 'label' => 'Главная'],
        ['url' => url('/vklady'), 'label' => 'Вклады'],
        ['label' => $deposit->name ?? $deposit->title ?? 'Вклад'],
    ],
])
@endsection

@section('content')
    <div class="main-content style-1">
        <div class="section-opportunities tf-spacing-27">
            <div class="tf-container">
                <div class="content">
                    <x-deposit-offer-card :deposit="$deposit">
                        <x-slot:afterRates>
                            <x-deposit-calculator :deposit="$deposit" />
                        </x-slot:afterRates>
                    </x-deposit-offer-card>
                </div>
            </div>
        </div>
    </div>
@endsection
