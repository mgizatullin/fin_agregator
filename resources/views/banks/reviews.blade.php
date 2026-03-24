@extends('layouts.app')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => 'Отзывы о ' . $bank->name,
    'breadcrumbs' => $breadcrumbs ?? [
        ['url' => url('/'), 'label' => 'Главная'],
        ['url' => url_section('banki'), 'label' => 'Банки'],
        ['url' => url_section('banki/' . $bank->slug), 'label' => $bank->name],
        ['label' => 'Отзывы'],
    ],
])
@endsection

@section('content')
<div class="main-content style-1">
    <div class="section-reviews-wrapper tf-spacing-27">
        <div class="tf-container">
            <div class="content">
                @include('partials.reviews-section', [
                    'reviewable' => $bank,
                    'sectionTitle' => 'Отзывы о ' . $bank->name,
                    'serviceLabel' => 'Банк',
                    'productName' => $bank->name,
                    'formAction' => route('banks.reviews.store', $bank),
                    'bankId' => $bank->id,
                    'bankName' => $bank->name,
                    'formId' => 'bank-' . $bank->id,
                ])
            </div>
        </div>
    </div>
</div>
@endsection
