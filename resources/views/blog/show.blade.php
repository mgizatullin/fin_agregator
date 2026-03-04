@extends('layouts.main')

@section('content')
            <!-- .page-title -->
            <div class="page-title style-default v2">
                <div class="tf-container">
                    <div class="row">
                        <div class="col-12">
                            <div class="heading mb_53">
                                <h1 class="text_black mb_25 letter-spacing-1">{{ $article->title }}</h1>
                                @if($article->excerpt)
                                <p class="sub-heading text_mono-gray-7">{{ $article->excerpt }}</p>
                                @endif
                            </div>
                            <ul class="breadcrumb">
                                <li><a href="{{ url('/') }}" class="link">Главная</a></li>
                                <li><a href="{{ url('/blog') }}" class="link">Блог</a></li>
                                <li>{{ $article->title }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div><!-- /.page-title -->

        </div>

        <div class="main-content style-1 ">
            <div class="section-sigle-post tf-spacing-3">
                <div class="tf-container">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="left">
                                <div class="heading-single-post mx-auto mb_40">
                                    <ul class="blog-article-meta d-flex align-items-center mb_32">
                                        @if($article->category)
                                        <li class="meta-item text-body-1 ">
                                            <a href="{{ url('/blog/category/' . $article->category->slug) }}" class="link-black">{{ $article->category->name }}</a>
                                        </li>
                                        @endif
                                        <li class="meta-item date text-body-1">
                                            {{ ($article->published_at ?? $article->created_at)->format('j F Y') }}
                                        </li>
                                    </ul>
                                </div>
                                <div class="content-post-main w-full mx-auto">
                                    @if($article->image)
                                    <div class="thumbs-post-single rounded-24 overflow-hidden mb_112">
                                        <img class="lazyload" data-src="{{ asset('storage/' . $article->image) }}" src="{{ asset('storage/' . $article->image) }}" alt="{{ $article->title }}">
                                    </div>
                                    @endif
                                    <div class="single-post-content mb_102">
                                        {!! $article->content !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="sidebar">
                                <div>
                                    <h6 class="sidebar-title mb_18 ">Категория</h6>
                                    <div class="sidebar-categories">
                                        @foreach(\App\Models\Category::where('is_active', true)->withCount('articles')->orderBy('name')->get() as $cat)
                                        <div class="item">
                                            <a href="{{ url('/blog/category/' . $cat->slug) }}" class="text-body-1 text_mono-gray-6">{{ $cat->name }}</a>
                                            <span class="text-body-3 text_mono-gray-6">{{ $cat->articles_count }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection
