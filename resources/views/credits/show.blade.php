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

        <div class="main-content style-1 ">
    <div class="section-opportunities tf-spacing-27">
        <div class="tf-container">
            <div class="content">
                @if(filled($credit->description))
                    {!! description_to_html($credit->description) !!}
                @else
                    <p class="text-body-1 text_mono-gray-7">Описание кредита пока не добавлено.</p>
                @endif
            </div>
        </div>
    </div>
        </div>

@endsection
