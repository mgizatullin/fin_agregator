@extends('layouts.app')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $page->title,
    'subtitle' => null,
    'breadcrumbs' => [
        ['url' => url('/'), 'label' => 'Главная'],
        ['label' => $page->title],
    ],
])
@endsection

@section('content')
<div class="main-content style-1 ">
    <div class="section-opportunities tf-spacing-27">
        <div class="tf-container">
            <div class="content">
                {!! $page->content !!}
            </div>
        </div>
    </div>
</div>
@endsection

