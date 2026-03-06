@extends('layouts.app')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $card->name ?? 'Кредитная карта',
    'subtitle' => null,
    'breadcrumbs' => [
        ['url' => url('/'), 'label' => 'Главная'],
        ['url' => url('/karty'), 'label' => 'Кредитные карты'],
        ['label' => $card->name ?? 'Кредитная карта'],
    ],
])
@endsection

@section('content')

        <div class="main-content style-1 ">
            <div class="tf-container">
                <div class="row">
                    <div class="col-lg-8 col-xl-9">
                        @include('cards.partials.card-product-content', ['card' => $card])
                    </div>
                    <div class="col-lg-4 col-xl-3">
                        @include('cards.partials.card-sidebar', ['card' => $card])
                    </div>
                </div>
            </div>
        </div>

@endsection
