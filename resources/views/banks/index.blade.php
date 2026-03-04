@extends('layouts.main')

@section('content')

            <!-- .page-title -->
            <div class="page-title style-default">
                <div class="tf-container">
                    <div class="row">
                        <div class="col-12">
                            <div class="heading mb_51">
                                <h1 class="text_black mb_18 letter-spacing-1 ">{{ $section->title ?? 'Банки' }}</h1>
                                <p class="sub-heading text_mono-gray-7">{{ $section->subtitle ?? '' }}</p>
                            </div>
                            <ul class="breadcrumb">
                                <li><a href="{{ url('/') }}" class="link">Главная</a></li>
                                <li>{{ $section->title ?? 'Банки' }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div><!-- /.page-title -->

        </div>

        <div class="main-content style-1 ">
    <div class="section-opportunities tf-spacing-27">
        <div class="tf-container">
            <div class="d-grid gap_40">
                @if(isset($items) && $items->isNotEmpty())
                    @foreach ($items as $bank)
                        <div class="karty-card">
                            <div class="karty-card__col karty-card__name">
                                <div class="karty-card__name-inner">
                                    @if($bank->logo)
                                        <img class="karty-card__image" src="{{ asset('storage/' . $bank->logo) }}" alt="{{ $bank->name }}" width="101" height="66">
                                    @else
                                        <div class="karty-card__image karty-card__image-placeholder">—</div>
                                    @endif
                                    <div class="karty-card__name-block">
                                        <div class="karty-card__name-text">{{ $bank->name }}</div>
                                        <span class="karty-card__label">Банк</span>
                                    </div>
                                </div>
                            </div>
                            <div class="karty-card__col karty-card__action">
                                @if($bank->website)
                                    <a href="{{ $bank->website }}" class="tf-btn btn-primary2 btn-px-28 height-2 rounded-12" target="_blank" rel="noopener">
                                        <span>Сайт</span>
                                        <span class="bg-effect"></span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-body-1 text_mono-gray-7">Нет банков.</p>
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
