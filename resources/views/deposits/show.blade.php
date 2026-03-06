@extends('layouts.app')

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

        <div class="main-content style-1 ">
    <div class="section-opportunities tf-spacing-27">
        <div class="tf-container">
            <div class="content">
                @if(isset($deposit) && filled($deposit->description))
                    {!! description_to_html($deposit->description) !!}
                @else
                    <p class="text-body-1 text_mono-gray-7">Описание вклада пока не добавлено.</p>
                @endif
            </div>
        </div>
    </div>
        </div>

@endsection
