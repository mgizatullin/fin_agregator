@extends('layouts.app')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => 'Вклады ' . $bank->name,
    'breadcrumbs' => $breadcrumbs ?? [
        ['url' => url('/'), 'label' => 'Главная'],
        ['url' => url_section('banki'), 'label' => 'Банки'],
        ['url' => url_section('banki/' . $bank->slug), 'label' => $bank->name],
        ['label' => 'Вклады'],
    ],
])
@endsection

@section('content')
<div class="main-content style-1">
    <div class="section-deposits-wrapper tf-spacing-27">
        <div class="tf-container">
            <div class="content">
                @if($bank->deposits->isNotEmpty())
                    <p class="section-subtitle text-body-2 text_mono-gray-7 mb_29">
                        Доступные вклады в банке
                    </p>
                    
                    <div class="d-grid gap_10" id="deposits-list">
                        @foreach($bank->deposits as $deposit)
                            <x-deposit-card :item="$deposit" />
                        @endforeach
                    </div>
                @else
                    <p class="text-body-1 text_mono-gray-7">В данном банке нет доступных вкладов.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
