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
                    <x-credit-service-calculator />
                @elseif($service->type === \App\Models\Service::TYPE_DEPOSIT_CALCULATOR)
                    <x-deposit-service-calculator :key-rate="$depositKeyRate" />
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
