@extends('layouts.main')

@section('content')

            <!-- .page-title -->
            <div class="page-title style-default">
                <div class="tf-container">
                    <div class="row">
                        <div class="col-12">
                            <div class="heading mb_51">
                                <h1 class="text_black mb_18 letter-spacing-1 ">{{ $section->title ?? 'Займы' }}</h1>
                                <p class="sub-heading text_mono-gray-7">{{ $section->subtitle ?? '' }}</p>
                            </div>
                            <ul class="breadcrumb">
                                <li><a href="{{ url('/') }}" class="link">Главная</a></li>
                                @if(isset($sectionIndexUrl) && isset($sectionIndexTitle))
                                    <li><a href="{{ $sectionIndexUrl }}" class="link">{{ $sectionIndexTitle }}</a></li>
                                @endif
                                <li>{{ $section->title ?? '' }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div><!-- /.page-title -->

        </div>

        <div class="main-content style-1 ">
    <div class="section-opportunities tf-spacing-27">
        <div class="tf-container">
            <div class="loan-cards-grid">
                @if(isset($items) && $items->isNotEmpty())
                    @foreach ($items as $item)
                        <x-loan-card :item="$item" />
                    @endforeach
                @else
                    <p class="text-body-1 text_mono-gray-7">В этой категории пока нет займов.</p>
                @endif
            </div>
        </div>
    </div>

    @if(!empty($section->description))
    <div class="tf-container">
        <div class="content mb-0">
            {!! $section->description !!}
        </div>
    </div>
    @endif
        </div>

@endsection
