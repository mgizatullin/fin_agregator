<div class="page-title style-default">
    <div class="tf-container">
        <div class="row">
            <div class="col-12">

                <div class="heading mb_51">
                    <h1 class="text_black letter-spacing-1">
                        {{ $title ?? '' }}
                    </h1>

                    @if(!empty($showCitySelect) && !empty($citySelectBase))
                        <button type="button" class="city-select-btn" data-section-base="{{ $citySelectBase }}">
                            Выбрать город
                        </button>
                    @endif

                    @if(!empty($subtitle))
                        <p class="sub-heading text_mono-gray-7">
                            {{ $subtitle }}
                        </p>
                    @endif
                </div>

                <ul class="breadcrumb">
                    @if(!empty($breadcrumbs))
                        @foreach($breadcrumbs as $item)
                        <li>
                            @if(!empty($item['url']))
                            <a href="{{ $item['url'] }}" class="link">{{ $item['label'] }}</a>
                            @else
                            {{ $item['label'] }}
                            @endif
                        </li>
                        @endforeach
                    @else
                        <li>
                            <a href="/" class="link">Главная</a>
                        </li>
                        <li>
                            {{ $title ?? '' }}
                        </li>
                    @endif
                </ul>

            </div>
        </div>
    </div>
</div>
