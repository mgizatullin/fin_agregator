@extends('layouts.app')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $card->name ?? 'Кредитная карта',
    'subtitle' => null,
    'breadcrumbs' => [
        ['url' => url('/'), 'label' => 'Главная'],
        ['url' => url_section('karty'), 'label' => 'Кредитные карты'],
        ['label' => $card->name ?? 'Кредитная карта'],
    ],
])
@endsection

@section('content')
        <div class="main-content style-1">
            <div class="section-opportunities tf-spacing-27">
                <div class="tf-container">
                    <div class="content">
                        <x-card-offer-card :card="$card" />
                    </div>
                </div>
            </div>
        </div>
@endsection
