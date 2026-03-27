@extends('layouts.app')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $service->title,
    'subtitle' => null,
    'breadcrumbs' => [
        ['url' => url('/'), 'label' => 'Главная'],
        ['label' => $service->title],
    ],
])
@endsection

@section('content')
<div class="main-content style-1 ">
    <div class="section-opportunities tf-spacing-27">
        <div class="tf-container">
            <div class="content">
                @if($service->type === \App\Models\Service::TYPE_CREDIT_CALCULATOR)
                    <x-credit-calculator :credit="$credit" />
                @elseif($service->type === \App\Models\Service::TYPE_DEPOSIT_CALCULATOR)
                    @if($deposit)
                        <x-deposit-calculator :deposit="$deposit" />
                    @else
                        <p class="text_mono-gray-7">Калькулятор будет доступен после появления активных вкладов с условиями в каталоге.</p>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
