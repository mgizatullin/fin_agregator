@extends('layouts.app')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $deposit->name ?? $deposit->title ?? $section->title ?? 'Вклад',
    'subtitle' => $section->subtitle ?? null,
    'breadcrumbs' => [
        ['url' => url('/'), 'label' => 'Главная'],
        ['url' => url_section('vklady'), 'label' => 'Вклады'],
        ['label' => $deposit->name ?? $deposit->title ?? 'Вклад'],
    ],
])
@endsection

@section('content')
    <div class="main-content style-1">
        <div class="section-opportunities tf-spacing-27">
            <div class="tf-container">
                <div class="content">
                    <x-deposit-offer-card :deposit="$deposit" :banks="$banks ?? collect()">
                        <x-slot:afterRates>
                            <x-deposit-calculator :deposit="$deposit" />
                        </x-slot:afterRates>
                    </x-deposit-offer-card>
                </div>
            </div>
        </div>
    </div>
@endsection
