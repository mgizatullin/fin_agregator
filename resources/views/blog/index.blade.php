@extends('layouts.main')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $section->title ?? 'Блог',
    'subtitle' => $section->subtitle ?? null
    ,'breadcrumbs' => array_filter([
        ['url' => '/', 'label' => 'Главная'],
        ['url' => url_section('blog'), 'label' => 'Журнал'],
        isset($category) && $category ? ['label' => $category->name] : null,
    ])
])
@endsection

@section('content')

        <div class="main-content style-1 ">
            <div class="section-blog-grid tf-spacing-2  ">
                <div class="tf-container">
                    <div class="row">
                        <div class="col-lg-8 left">
                            <div class="tf-grid-layout md-col-2">
                                @foreach($articles as $article)
                                <div class="blog-article-item  hover-image  ">
                                    <a href="{{ url_section('blog/' . $article->slug) }}" class="article-thumb mb_25 ">
                                        @if($article->image)
                                        <img class="lazyload " data-src="{{ asset('storage/' . $article->image) }}" src="{{ asset('storage/' . $article->image) }}" alt="{{ $article->title }}">
                                        @else
                                        <img class="lazyload " data-src="{{ asset('assets/images/blog/blog-1.jpg') }}" src="{{ asset('assets/images/blog/blog-1.jpg') }}" alt="{{ $article->title }}">
                                        @endif
                                    </a>
                                    <div class="article-content">
                                        <ul class="blog-article-meta mb_15 d-flex align-items-center">
                                            @if($article->category)
                                            <li class="meta-item text-body-1">
                                                <a href="{{ url_section('blog/category/' . $article->category->slug) }}" class="link-black">{{ $article->category->name }}</a>
                                            </li>
                                            @endif
                                            <li class="meta-item date text-body-1">
                                                {{ ($article->published_at ?? $article->created_at)->locale('ru')->translatedFormat('j F Y') }}
                                            </li>
                                        </ul>
                                        <h5 class="title letter-spacing-2"> <a href="{{ url_section('blog/' . $article->slug) }}" class="link ">{{ $article->title }}</a>
                                        </h5>
                                        @if($article->excerpt || $article->content || $article->content_html)
                                        <p class="text-body-2 text_mono-gray-6 mt_12">{{ $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->content_html ?: $article->content), 150) }}</p>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @if($articles->hasPages())
                            <ul class="wg-pagination d-flex justify-content-center gap_12 mt_85">
                                @if($articles->onFirstPage())
                                <li><span><i class="icon-angle-left-solid"></i></span></li>
                                @else
                                <li><a href="{{ $articles->previousPageUrl() }}"><i class="icon-angle-left-solid"></i></a></li>
                                @endif
                                @foreach($articles->getUrlRange(1, $articles->lastPage()) as $page => $url)
                                <li><a href="{{ $url }}" class="{{ $articles->currentPage() === $page ? 'active' : '' }}">{{ $page }}</a></li>
                                @endforeach
                                @if($articles->hasMorePages())
                                <li><a href="{{ $articles->nextPageUrl() }}"><i class="icon-angle-right-solid"></i></a></li>
                                @else
                                <li><span><i class="icon-angle-right-solid"></i></span></li>
                                @endif
                            </ul>
                            @endif
                        </div>
                        <div class="col-lg-4 right">
                            @include('layouts.partials.blog-sidebar')
                        </div>
                    </div>

                </div>
            </div>
        </div>
@endsection
