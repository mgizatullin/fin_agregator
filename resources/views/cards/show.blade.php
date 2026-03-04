@extends('layouts.main')

@section('content')

            <!-- .page-title -->
            <div class="page-title style-default">
                <div class="tf-container">
                    <div class="row">
                        <div class="col-12">
                            <div class="heading mb_43">
                                <h1 class="text_black letter-spacing-1 ">{{ $card->name }}</h1>
                            </div>
                            <ul class="breadcrumb">
                                <li><a href="{{ url('/') }}" class="link">Главная</a></li>
                                <li><a href="{{ route('cards.index') }}" class="link">Кредитные карты</a></li>
                                <li>{{ $card->name }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div><!-- /.page-title -->

        </div>

        <div class="main-content style-1 ">
            <div class="tf-container">
                <div class="row">
                    <div class="col-lg-8 col-xl-9">
                        @include('cards.partials.card-product-content', ['card' => $card])
                    </div>
                    <div class="col-lg-4 col-xl-3">
                        @include('cards.partials.card-sidebar', ['card' => $card])
                    </div>
                </div>
            </div>
        </div>

@endsection
