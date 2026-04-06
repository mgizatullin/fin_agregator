@extends('layouts.app')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $article->title ?? 'Статья',
    'subtitle' => $article->excerpt ?? null,
    'breadcrumbs' => array_filter([
        ['url' => '/', 'label' => 'Главная'],
        ['url' => url_section('blog'), 'label' => 'Журнал'],
        $article->category ? ['url' => url_section('blog/category/' . $article->category->slug), 'label' => $article->category->name] : null,
        ['label' => $article->title],
    ]),
])
@endsection

@section('content')

        <div class="main-content style-1 ">
            <div class="section-sigle-post tf-spacing-3">
                <div class="tf-container">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="left">
                                <div class="heading-single-post mx-auto mb_40">
                                <div class="box-infor mx-auto mb_40">
                                    <div class="box-user d-flex gap_16 align-items-center">
                                        @php
                                            $specialist = $article->specialist ?? null;
                                            $authorName = $specialist?->name ?: ($article->author ?? 'Редактор');
                                            $authorPosition = $specialist?->position ?: null;
                                            $photo = $specialist?->photo ? (string) $specialist->photo : '';
                                            $photoUrl = $photo !== ''
                                                ? (str_starts_with($photo, 'http') ? $photo : asset('storage/'.$photo))
                                                : null;
                                        @endphp
                                        <div class="avatar">
                                            @if($photoUrl)
                                                <img src="{{ $photoUrl }}" alt="avatar">
                                            @else
                                                <img src="{{ asset('assets/images/avatar/avatar-1.jpg') }}" alt="avatar">
                                            @endif
                                        </div>
                                        <div class="content">
                                            <div class="sub-heading text_mono-dark-9">{{ $authorName }}</div>
                                            @if(filled($authorPosition))
                                                <span class="text-body-3 text_mono-gray-5">{{ $authorPosition }}</span>
                                            @else
                                                <span class="text-body-3 text_mono-gray-5">Автор статьи</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="right">
                                        {{ ($article->published_at ?? $article->created_at)->translatedFormat('d F Y') }}
                                    </div>
                                </div>
                                </div>
                                <div class="content-post-main w-full mx-auto">
                                    @if($article->image)
                                    <div class="thumbs-post-single rounded-24 overflow-hidden mb_112">
                                        <img class="lazyload" data-src="{{ asset('storage/' . $article->image) }}" src="{{ asset('storage/' . $article->image) }}" alt="{{ $article->title }}">
                                    </div>
                                    @endif
                                    <div class="single-post-content mb_102 article-body">
                                        @if(filled($article->content_html))
                                            {!! $article->content_html !!}
                                        @else
                                            {!! $article->content !!}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            @include('layouts.partials.blog-sidebar')
                        </div>
                    </div>
                </div>
            </div>
        </div>

@endsection
