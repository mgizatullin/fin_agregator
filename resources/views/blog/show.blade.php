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
                                        {{ ($article->published_at ?? $article->created_at)->locale('ru')->translatedFormat('d F Y') }}
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

                                    @php
                                        $comments = $comments ?? [];
                                        $commentFormErrors = $errors->hasAny(['body', 'name']);
                                    @endphp
                                    <section id="comments" class="section-reviews tf-spacing-27 mb_102">
                                        <div class="section-heading">
                                            <h3 class="section-reviews__title">Комментарии</h3>
                                            <div class="section-heading__meta">
                                                <span class="section-heading__count">{{ count($comments) }} {{ \Illuminate\Support\Str::of('комментарий')->plural(count($comments)) }}</span>
                                            </div>
                                        </div>

                                        @if(session('status'))
                                            <p class="reviews-empty">{{ session('status') }}</p>
                                        @endif

                                        @if($comments !== [])
                                            <div class="reviews-list">
                                                @foreach($comments as $comment)
                                                    <article class="review-card">
                                                        <header class="review-card__header">
                                                            <h4 class="review-card__title">{{ $comment->name }}</h4>
                                                            <span class="review-card__header-meta">
                                                                <span>{{ ($comment->created_at)->locale('ru')->translatedFormat('d F Y, H:i') }}</span>
                                                            </span>
                                                        </header>
                                                        <div class="review-card__body">
                                                            <p style="white-space: pre-wrap;">{{ $comment->body }}</p>
                                                        </div>
                                                    </article>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="reviews-empty">Пока нет комментариев. Станьте первым, кто поделится мнением.</p>
                                        @endif

                                        <div class="review-form-wrapper">
                                            <h3 class="review-form__title">Оставить комментарий</h3>
                                            <form action="{{ route('blog.comments.store', $article) }}" method="post" class="review-form">
                                                @csrf
                                                <div class="review-form__grid">
                                                    <div class="review-form__field {{ $commentFormErrors ? 'review-form__field--error' : '' }}">
                                                        <label class="review-form__label" for="comment-name">Имя <span class="review-form__required">*</span></label>
                                                        <input id="comment-name" type="text" name="name" class="review-form__input" value="{{ old('name') }}" required maxlength="255">
                                                        @error('name')<span class="review-form__error-text">{{ $message }}</span>@enderror
                                                    </div>

                                                    <div class="review-form__field review-form__field--full {{ $commentFormErrors ? 'review-form__field--error' : '' }}">
                                                        <label class="review-form__label" for="comment-body">Комментарий <span class="review-form__required">*</span></label>
                                                        <textarea id="comment-body" name="body" rows="5" class="review-form__textarea" required maxlength="5000">{{ old('body') }}</textarea>
                                                        @error('body')<span class="review-form__error-text">{{ $message }}</span>@enderror
                                                    </div>
                                                </div>

                                                <button type="submit" class="tf-btn btn-primary2 btn-px-28 height-2 rounded-12 review-form__submit">
                                                    <span>Отправить комментарий</span>
                                                    <span class="bg-effect"></span>
                                                </button>
                                            </form>
                                            <p class="text-body-3 text_mono-gray-6 mt_12">Комментарий появится после модерации.</p>
                                        </div>
                                    </section>
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
