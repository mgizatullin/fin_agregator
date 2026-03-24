@extends('layouts.app')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => 'Кредиты ' . $bank->name,
    'breadcrumbs' => $breadcrumbs ?? [
        ['url' => url('/'), 'label' => 'Главная'],
        ['url' => url_section('banki'), 'label' => 'Банки'],
        ['url' => url_section('banki/' . $bank->slug), 'label' => $bank->name],
        ['label' => 'Кредиты'],
    ],
])
@endsection

@section('content')
<div class="main-content style-1">
    <div class="section-credits-wrapper tf-spacing-27">
        <div class="tf-container">
            <div class="content">
                @if($bank->credits->isNotEmpty())
                    <p class="section-subtitle text-body-2 text_mono-gray-7 mb_29">
                        Доступные кредитные продукты
                    </p>
                    
                    <div class="d-grid gap_10" id="credits-list">
                        @include('credits.partials.list-items', ['items' => $bank->credits])
                    </div>
                @else
                    <p class="text-body-1 text_mono-gray-7">В данном банке нет доступных кредитов.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
