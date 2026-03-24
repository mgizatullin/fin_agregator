@extends('layouts.app')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => 'Отделения ' . $bank->name,
    'breadcrumbs' => $breadcrumbs ?? [
        ['url' => url('/'), 'label' => 'Главная'],
        ['url' => url_section('banki'), 'label' => 'Банки'],
        ['url' => url_section('banki/' . $bank->slug), 'label' => $bank->name],
        ['label' => 'Отделения'],
    ],
])
@endsection

@section('content')
<div class="main-content style-1">
    <div class="section-branches-wrapper tf-spacing-27">
        <div class="tf-container">
            <div class="content">
                @if($bank->branches->isNotEmpty())
                    <p class="section-subtitle text-body-2 text_mono-gray-7 mb_29">
                        Всего отделений: <strong>{{ $bank->branches->count() }}</strong>
                    </p>
                    
                    <div class="branches-list grid-layout gap_16">
                        @foreach($bank->branches as $branch)
                        <div class="branch-card">
                            <h3 class="branch-name text-body-1">{{ $branch->name ?? 'Отделение' }}</h3>
                            @if($branch->address)
                            <p class="branch-address text-body-2 text_mono-gray-7">
                                <i class="icon-map-marker"></i> {{ $branch->address }}
                            </p>
                            @endif
                            @if($branch->phone)
                            <a href="tel:{{ preg_replace('/[^\d+]/', '', $branch->phone) }}" class="branch-phone text-body-2 link">
                                {{ $branch->phone }}
                            </a>
                            @endif
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-body-1 text_mono-gray-7">Информация об отделениях пока не доступна.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
