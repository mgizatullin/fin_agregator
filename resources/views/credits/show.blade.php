@extends('layouts.app')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $credit->name ?? $credit->title ?? $section->title ?? 'Кредит',
    'subtitle' => $section->subtitle ?? null,
    'breadcrumbs' => [
        ['url' => url('/'), 'label' => 'Главная'],
        ['url' => url('/kredity'), 'label' => 'Кредиты'],
        ['label' => $credit->name ?? $credit->title ?? 'Кредит'],
    ],
])
@endsection

@section('content')
    <div class="main-content style-1">
        <div class="section-opportunities tf-spacing-27">
            <div class="tf-container">
                <div class="content">
                    <x-credit-offer-card :credit="$credit" />
                </div>
            </div>
        </div>
    </div>
@endsection
