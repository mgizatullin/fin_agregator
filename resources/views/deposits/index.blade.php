@extends('layouts.main')

@section('content')

            <!-- .page-title -->
            <div class="page-title style-default">
                <div class="tf-container">
                    <div class="row">
                        <div class="col-12">
                            <div class="heading mb_51">
                                <h1 class="text_black mb_18 letter-spacing-1 ">{{ $section->title ?? 'Вклады' }}</h1>
                                <p class="sub-heading text_mono-gray-7">{{ $section->subtitle ?? '' }}</p>
                            </div>
                            <ul class="breadcrumb">
                                <li><a href="{{ url('/') }}" class="link">Главная</a></li>
                                <li>{{ $section->title ?? 'Вклады' }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div><!-- /.page-title -->

        </div>

        <div class="main-content style-1 ">
    <div class="section-opportunities tf-spacing-27">
        <div class="tf-container">
            <div class="d-grid gap_10">
                @if(isset($items) && $items->isNotEmpty())
                    @foreach ($items as $item)
                        <x-deposit-card :item="$item" />
                    @endforeach
                @else
                    <p class="text-body-1 text_mono-gray-7">Нет вкладов.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="tf-container">
    <div class="content mb-0 text-body">
            {!! description_to_html($section->description ?? '') !!}
        </div>
    </div>
        </div>

@endsection
