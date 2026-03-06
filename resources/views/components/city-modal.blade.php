<div id="city-modal-root" class="city-modal" aria-hidden="true" role="dialog" aria-labelledby="city-modal-title">
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
            </div>
            <div class="city-modal__list-wrap" id="city-modal-list">
                <div class="city-modal__list">
                    @foreach($groupedCities as $letter => $cities)
                        <div class="city-modal__group" data-city-group="{{ $letter }}">
                            <div class="city-modal__letter">{{ $letter }}</div>
                            @foreach($cities as $city)
                                <a href="#" class="city-modal__item city-modal-item" data-city-slug="{{ $city->slug }}" data-city-name="{{ $city->name }}">{{ $city->name }}</a>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
