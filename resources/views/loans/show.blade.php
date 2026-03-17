@extends('layouts.app')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $loan->name ?? $section->title ?? 'Займ',
    'subtitle' => $section->subtitle ?? null,
    'breadcrumbs' => [
        ['url' => url('/'), 'label' => 'Главная'],
        ['url' => url_section('zaimy'), 'label' => 'Займы'],
        ['label' => $loan->name ?? 'Займ'],
    ],
])
@endsection

@section('content')
    <div class="main-content style-1">
        <div class="section-opportunities tf-spacing-27">
            <div class="tf-container">
                <div class="content">
                    <x-loan-offer-card :loan="$loan">
                        <x-slot:sidebar>
                            <x-loan-calculator :loan="$loan" />
                        </x-slot:sidebar>
                    </x-loan-offer-card>
                </div>
            </div>
        </div>
    </div>
@endsection
