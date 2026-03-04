<aside class="article-sidebar fixed-sidebar tf-spacing-11">
    <div class="inner-wrapper-sticky">
        <div class="card-sidebar-offer sidebar__offer">
            <div class="card-sidebar-offer__flex">
                @if($card->bank && $card->bank->logo)
                    <div class="card-sidebar-offer__bank-logo">
                        <img src="{{ asset('storage/' . $card->bank->logo) }}" alt="{{ $card->bank->name }}" width="120" height="120">
                    </div>
                @endif
                <div class="card-sidebar-offer__items">
                    @if($card->bank)
                        <div class="card-sidebar-offer__item">
                            <span class="card-sidebar-offer__label">Организация</span>
                            <strong>{{ $card->bank->name }}</strong>
                        </div>
                    @endif
                    @if($card->bank && $card->bank->website)
                        <div class="card-sidebar-offer__item">
                            <span class="card-sidebar-offer__label">Официальный сайт</span>
                            <a href="{{ $card->bank->website }}" class="link" target="_blank" rel="noopener">{{ $card->bank->website }}</a>
                        </div>
                    @endif
                    @if($card->bank && $card->bank->phone)
                        <div class="card-sidebar-offer__item">
                            <span class="card-sidebar-offer__label">Телефон</span>
                            <a href="tel:{{ preg_replace('/\s+/', '', $card->bank->phone) }}" class="link">{{ $card->bank->phone }}</a>
                        </div>
                    @endif
                </div>
            </div>
            <div class="card-sidebar-offer__btn-wrap">
                <a href="#" class="tf-btn btn-primary2 w-full">
                    <span>Оформить карту онлайн</span>
                    <span class="bg-effect"></span>
                </a>
            </div>
        </div>
    </div>
</aside>
