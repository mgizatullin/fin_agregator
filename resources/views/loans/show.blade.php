@extends('layouts.app')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $loan->name ?? $loan->title ?? $section->title ?? 'Займ',
    'subtitle' => $section->subtitle ?? null,
    'breadcrumbs' => [
        ['url' => url('/'), 'label' => 'Главная'],
        ['url' => url('/zaimy'), 'label' => 'Займы'],
        ['label' => $loan->name ?? $loan->title ?? 'Займ'],
    ],
])
@endsection

@section('content')

        <div class="main-content style-1 ">
    <div class="section-opportunities tf-spacing-27">
        <div class="tf-container">
            <div class="content">
                @if(filled($loan->description))
                    {!! description_to_html($loan->description) !!}
                @else
                    <p class="text-body-1 text_mono-gray-7">Описание займа пока не добавлено.</p>
                @endif
                @include('partials.reviews-section', [
                    'reviewable' => $loan,
                    'sectionTitle' => 'Отзывы по займу',
                    'serviceLabel' => 'Займ',
                    'productName' => $loan->name,
                    'formAction' => route('loans.reviews.store', $loan),
                    'bankId' => null,
                    'bankName' => $loan->company_name ?? '—',
                    'formId' => 'loan-' . $loan->id,
                ])
            </div>
        </div>
    </div>
        </div>

@endsection
