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
        @foreach($latestArticles ?? [] as $latest)
        <div class="relatest-post-item style-default hover-image-2">
            <a href="{{ url('/blog/' . $latest->slug) }}" class="image-rotate image ">
                @if($latest->image)
                @php
                    $thumbPath = 'blog/thumbs/' . basename($latest->image);
                    $thumbExists = str_starts_with($latest->image, 'blog/') && \Illuminate\Support\Facades\Storage::disk('public')->exists($thumbPath);
                @endphp
                <img src="{{ asset($thumbExists ? 'storage/' . $thumbPath : 'storage/' . $latest->image) }}" alt="{{ $latest->title }}">
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
            @foreach($categories ?? [] as $category)
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
