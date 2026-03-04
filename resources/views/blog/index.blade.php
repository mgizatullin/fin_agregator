@extends('layouts.main')

@section('content')
            <!-- .page-title -->
            <div class="page-title style-default v6">
                <div class="tf-container">
                    <div class="row">
                        <div class="col-12">
                            <div class="heading mb_51">
                                <h1 class="text_black mb_25 letter-spacing-1 ">{{ $section->title ?? 'Блог' }}</h1>
                                @if(!empty($section->subtitle))
                                <p class="sub-heading text_mono-gray-7">{{ $section->subtitle }}</p>
                                @endif
                                @if(!empty($section->description))
                                <div class="sub-heading text_mono-gray-7">{!! $section->description !!}</div>
                                @endif
                            </div>
                            <ul class="breadcrumb">
                                <li><a href="{{ url('/') }}" class="link">Главная</a></li>
                                <li>Блог</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div><!-- /.page-title -->

        </div>

        <div class="main-content style-1 ">
            <div class="section-blog-grid tf-spacing-2  ">
                <div class="tf-container">
                    <div class="row">
                        <div class="col-lg-8 left">
                            <div class="tf-grid-layout md-col-2">
                                @foreach($articles as $article)
                                <div class="blog-article-item  hover-image  ">
                                    <a href="{{ url('/blog/' . $article->slug) }}" class="article-thumb mb_25 ">
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
                                                <a href="{{ url('/blog/category/' . $article->category->slug) }}" class="link-black">{{ $article->category->name }}</a>
                                            </li>
                                            @endif
                                            <li class="meta-item date text-body-1">
                                                {{ ($article->published_at ?? $article->created_at)->format('j F Y') }}
                                            </li>
                                        </ul>
                                        <h5 class="title letter-spacing-2"> <a href="{{ url('/blog/' . $article->slug) }}" class="link ">{{ $article->title }}</a>
                                        </h5>
                                        @if($article->excerpt || $article->content)
                                        <p class="text-body-2 text_mono-gray-6 mt_12">{{ $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->content), 150) }}</p>
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
                            <div class="sidebar">
                                <div>
                                    <h6 class="sidebar-title mb_21 ">
                                        Поиск
                                    </h6>
                                    <form class="form-search style-2" action="{{ url('/blog') }}" method="get">
                                        <fieldset class="text">
                                            <input type="text" placeholder="Поиск" class="style-2" name="search" tabindex="0" value="{{ request('search') }}" aria-required="true">
                                        </fieldset>
                                        <button class="" type="submit">
                                            <i class="icon-search-solid"></i>
                                        </button>
                                    </form>
                                </div>
                                <div>
                                    <h6 class="sidebar-title  mb_13 ">
                                        Последние публикации
                                    </h6>
                                    @foreach($latestArticles as $latest)
                                    <div class="relatest-post-item style-default hover-image-2">
                                        <a href="{{ url('/blog/' . $latest->slug) }}" class="image-rotate image ">
                                            @if($latest->image)
                                            <img src="{{ asset('storage/' . $latest->image) }}" alt="{{ $latest->title }}">
                                            @else
                                            <img src="{{ asset('assets/images/blog/blog-1.jpg') }}" alt="{{ $latest->title }}">
                                            @endif
                                        </a>
                                        <div class="content">
                                            <div class="text-body-1 mb_12">
                                                <a href="{{ url('/blog/' . $latest->slug) }}" class="link">
                                                    {{ $latest->title }}
                                                </a>
                                            </div>
                                            <ul class="blog-article-meta  d-flex align-items-center">
                                                @if($latest->category)
                                                <li class="meta-item text-body-3">
                                                    <a href="{{ url('/blog/category/' . $latest->category->slug) }}" class="link-black">{{ $latest->category->name }}</a>
                                                </li>
                                                @endif
                                                <li class="meta-item date text-body-3">
                                                    {{ ($latest->published_at ?? $latest->created_at)->format('j F Y') }}
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                <div>
                                    <h6 class="sidebar-title  mb_18 ">
                                        Категория
                                    </h6>
                                    <div class="sidebar-categories">
                                        @foreach($categories as $category)
                                        <div class="item"><a href="{{ url('/blog/category/' . $category->slug) }}" class="text-body-1  text_mono-gray-6">{{ $category->name }}</a><span class="text-body-3 text_mono-gray-6">{{ $category->articles_count }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div>
                                    <h6 class="sidebar-title  mb_18 -mt_7">
                                        Популярные теги
                                    </h6>
                                    <div class="wrap-popular-tag">
                                        <a href="{{ url('/blog') }}" class="popular-tag-item link">
                                            Аналитика
                                        </a>
                                        <a href="{{ url('/blog') }}" class="popular-tag-item link">
                                            Консалтинг
                                        </a>
                                        <a href="{{ url('/blog') }}" class="popular-tag-item link">
                                            Бизнес
                                        </a>
                                        <a href="{{ url('/blog') }}" class="popular-tag-item link">
                                            Данные
                                        </a>
                                        <a href="{{ url('/blog') }}" class="popular-tag-item link">
                                            Бизнес-консалтинг
                                        </a>
                                        <a href="{{ url('/blog') }}" class="popular-tag-item link">
                                            Маркетинг
                                        </a>
                                        <a href="{{ url('/blog') }}" class="popular-tag-item link">
                                            Решения
                                        </a>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="sidebar-title  mb_16">
                                        Подписаться на рассылку
                                    </h6>
                                    <form method="post" class="form-newsletter " action="#" accept-charset="utf-8">
                                        <p class="text-body-2 mb_14">Подпишитесь, чтобы получать новости и обновления на почту!</p>
                                        <div>
                                            <fieldset class="mb_14">
                                                <input type="email" class="tb-my-input style-2" name="email" placeholder="Введите email" required="">
                                            </fieldset>
                                        </div>
                                        <button name="submit" type="submit" class="tf-btn w-full btn-submit-comment btn-primary2">
                                            <span>Подписаться</span>
                                            <span class="bg-effect"></span>
                                        </button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
@endsection
