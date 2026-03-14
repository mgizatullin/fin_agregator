@php
    $base = $sectionBase ?? '';
    $basePath = $base ? '/' . $base : '';
@endphp
<div id="city-modal-root" class="city-modal" aria-hidden="true" role="dialog" aria-labelledby="city-modal-title" data-section-base="{{ $base }}">
    <div class="city-modal__overlay" data-city-modal-close></div>
    <div class="city-modal__box">
        <div class="city-modal__header">
            <h2 id="city-modal-title" class="city-modal__title">Выберите город</h2>
            <button type="button" class="city-modal__close" data-city-modal-close aria-label="Закрыть">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="city-modal__body">
            <div class="city-modal__search-wrap">
                <input type="text"
                       class="city-modal__search form-control"
                       placeholder="Введите название города"
                       autocomplete="off"
                       data-city-search
                       id="city-modal-search">
                <div class="city-modal__search-dropdown" id="city-modal-search-results" aria-live="polite" hidden></div>
            </div>
            <div class="city-modal__quick" id="city-modal-quick">
                <ul class="city-modal__quick-list">
                    <li><a href="{{ $basePath }}" class="city-modal__item city-modal__item--quick" data-city-slug="" data-city-name="Вся Россия">Вся Россия</a></li>
                    @if(!empty($moscowSlug))
                        <li><a href="{{ $basePath }}/{{ $moscowSlug }}" class="city-modal__item city-modal__item--quick" data-city-slug="{{ $moscowSlug }}" data-city-name="Москва">Москва</a></li>
                    @endif
                    @if(!empty($spbSlug))
                        <li><a href="{{ $basePath }}/{{ $spbSlug }}" class="city-modal__item city-modal__item--quick" data-city-slug="{{ $spbSlug }}" data-city-name="Санкт-Петербург">Санкт-Петербург</a></li>
                    @endif
                </ul>
            </div>
            <div class="city-modal__list-wrap" id="city-modal-list">
                <div class="city-modal__list">
                    @foreach($groupedCities as $letter => $cities)
                        <div class="city-modal__group" data-city-group="{{ $letter }}">
                            <div class="city-modal__letter">{{ $letter }}</div>
                            <ul class="city-modal__group-list">
                                @foreach($cities as $city)
                                    <li><a href="{{ $basePath }}/{{ $city->slug }}" class="city-modal__item" data-city-slug="{{ $city->slug }}" data-city-name="{{ $city->name }}">{{ $city->name }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
