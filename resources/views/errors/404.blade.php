@extends('layouts.app')

@php
    $title = 'Страница не найдена';
    $seo_title = '404 — Страница не найдена';
    $seo_description = 'Запрошенная страница не найдена. Вернитесь на главную страницу сайта.';
@endphp

@push('styles')
    <style>
        .wrap-page-header {
            margin-bottom: 90px;
        }
    </style>
@endpush

@section('page-header')
    <div class="page-title style-default">
        <div class="error-404">
            <div class="contnet">
                <div class="img">
                    <img src="{{ asset('assets/images/item/404.png') }}" alt="404">
                </div>
                <p class="sub-heading text_mono-gray-7">
                    Мы не нашли страницу, которую вы запрашивали.
                </p>
                <a href="{{ url('/') }}" class="tf-btn mx-auto">
                    <span>Вернуться на главную</span>
                    <span class="bg-effect"></span>
                </a>
            </div>
            <div class="item position-absolute">
                <img src="{{ asset('assets/images/item/shape-5.png') }}" alt="shape">
            </div>
        </div>
    </div>
@endsection

@section('content')
@endsection
