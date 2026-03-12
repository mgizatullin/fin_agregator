@extends('layouts.app')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $bank->name ?? $section->title ?? 'Банк',
    'subtitle' => $section->subtitle ?? null,
    'breadcrumbs' => [
        ['url' => url('/'), 'label' => 'Главная'],
        ['url' => url('/banki'), 'label' => 'Банки'],
        ['label' => $bank->name ?? 'Банк'],
    ],
])
@endsection

@section('content')

        <div class="main-content style-1 ">
    <div class="section-opportunities tf-spacing-27">
        <div class="tf-container">
            <div class="content">
                @if(isset($bank) && filled($bank->description))
                    {!! description_to_html($bank->description) !!}
                @else
                    <p class="text-body-1 text_mono-gray-7">Описание банка пока не добавлено.</p>
                @endif
                @include('partials.reviews-section', [
                    'reviewable' => $bank,
                    'sectionTitle' => 'Отзывы о банке',
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
