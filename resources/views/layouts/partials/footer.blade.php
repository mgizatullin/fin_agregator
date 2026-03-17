        <!-- .footer -->
        <footer id="footer" class="footer style-default">
            <div class="footer-wrap">
                <div class="tf-container">
                    <div class="footer-body">
                        <div class="row">
                            <div class="col-lg-4 ">
                                <div class="footer-about">
                                    <a href="/" class="footer-logo ">
                                        @if(!empty($siteSettings?->logo))
                                            <img src="{{ str_starts_with($siteSettings->logo, 'http') ? $siteSettings->logo : asset('storage/' . $siteSettings->logo) }}" alt="{{ config('app.name') }}">
                                        @else
                                            <img src="{{ asset('assets/images/logo/favicon.svg') }}" alt="logo">
                                        @endif
                                    </a>
                                    <div class="footer-info mb_51">
                                        @if(filled($siteSettings?->footer_under_logo))
                                            {!! $siteSettings->footer_under_logo !!}
                                        @else
                                            <a href="mailto:themesflat@gmail.com" class="link text-body-2 text_black">themesflat@gmail.com</a>
                                            <div class="text-body-2">152 Thatcher Road St, Manhattan, NY 10463, <br>США</div>
                                            <div class="text-body-2">(+068) 568 9696</div>
                                        @endif
                                    </div>
                                    @if(filled($siteSettings?->social_twitter) || filled($siteSettings?->social_facebook) || filled($siteSettings?->social_github) || filled($siteSettings?->social_instagram) || filled($siteSettings?->social_youtube) || filled($siteSettings?->social_zen) || filled($siteSettings?->social_telegram))
                                    <div class="tf-social">
                                        @if(filled($siteSettings?->social_twitter))<a href="{{ $siteSettings->social_twitter }}" class="icon-twitter-x" target="_blank" rel="noopener noreferrer" aria-label="Twitter"></a>@endif
                                        @if(filled($siteSettings?->social_facebook))<a href="{{ $siteSettings->social_facebook }}" class="icon-facebook-f" target="_blank" rel="noopener noreferrer" aria-label="Facebook"></a>@endif
                                        @if(filled($siteSettings?->social_github))<a href="{{ $siteSettings->social_github }}" class="icon-github" target="_blank" rel="noopener noreferrer" aria-label="GitHub"></a>@endif
                                        @if(filled($siteSettings?->social_instagram))<a href="{{ $siteSettings->social_instagram }}" class="icon-instagram" target="_blank" rel="noopener noreferrer" aria-label="Instagram"></a>@endif
                                        @if(filled($siteSettings?->social_youtube))<a href="{{ $siteSettings->social_youtube }}" class="icon-youtube" target="_blank" rel="noopener noreferrer" aria-label="YouTube"></a>@endif
                                        @if(filled($siteSettings?->social_zen))<a href="{{ $siteSettings->social_zen }}" class="icon-link-simple" target="_blank" rel="noopener noreferrer" aria-label="Дзен"></a>@endif
                                        @if(filled($siteSettings?->social_telegram))<a href="{{ $siteSettings->social_telegram }}" class="icon-paper-plane" target="_blank" rel="noopener noreferrer" aria-label="Телеграм"></a>@endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <div class="footer-col-block">
                                    <h6 class="footer-heading  footer-heading-mobile">
                                        {{ $siteSettings?->footer_heading_1 ?? 'Компания' }}
                                    </h6>
                                    <div class="tf-collapse-content">
                                        <ul class="footer-menu-list">
                                            @foreach(($siteSettings?->footer_menu_1 ?? []) as $item)
                                                <li class="text-body-2 text_mono-gray-6">
                                                    <a href="{{ $item['url'] ?? '#' }}" class=" link footer-menu_item">
                                                        {{ $item['label'] ?? '' }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <div>
                                    <div class="footer-col-block">
                                        <h6 class="footer-heading  footer-heading-mobile">
                                            {{ $siteSettings?->footer_heading_2 ?? 'Ссылки' }}
                                        </h6>
                                        <div class="tf-collapse-content">
                                            <ul class="footer-menu-list">
                                                @foreach(($siteSettings?->footer_menu_2 ?? []) as $item)
                                                    <li class="text-body-2 text_mono-gray-6">
                                                        <a href="{{ $item['url'] ?? '#' }}" class=" link footer-menu_item">
                                                            {{ $item['label'] ?? '' }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class=" footer-newsletter">
                                    <h6 class="footer-heading   ">
                                        Подпишитесь на нашу рассылку
                                    </h6>
                                    <div class="tf-collapse-content">
                                        <div class="wrap-newsletter">
                                            <p class="text-body-2 text_mono-gray-6 mb_29">Подпишитесь, чтобы получать новости, акции и полезные материалы!
                                            <form id="subscribe-form" action="#" class="form-newsletter style-1 subscribe-form mb_10" method="post" accept-charset="utf-8" data-mailchimp="true">
                                                <div id="subscribe-content" class="subscribe-content">
                                                    <fieldset class="email">
                                                        <input id="subscribe-email" type="email" name="email-form" class="subscribe-email style-2" placeholder="Введите ваш email" tabindex="0" aria-required="true">
                                                    </fieldset>
                                                    <div class="button-submit">
                                                        <button id="subscribe-button" class="subscribe-button tf-btn rounded-12 btn-primary2 " type="button">
                                                            <span>Подписаться</span>
                                                            <span class="bg-effect"></span>
                                                        </button>
                                                    </div>
                                                    <div class="icon">
                                                        <i class="icon-envelope-solid"></i>
                                                    </div>
                                                </div>
                                                <div id="subscribe-msg" class="subscribe-msg"></div>
                                            </form>
                                            <p class="description text-body-2">Подписываясь, вы принимаете нашу
                                                <a href="#" class="link-black text_primary ">Политика конфиденциальности</a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="tf-container">
                    <div class="row">
                        <div class="col-12">
                            <div class="wrapper d-flex align-items-center flex-wrap gap_12 ">
                                <p class="text-body-2">{!! $siteSettings?->copyright ?? '© ' . date('Y') . '. Все права защищены.' !!}</p>
                                <ul class="right d-flex align-items-center">
                                    <li><a href="/" class="link text_mono-gray-5 text-body-1">Главная</a></li>
                                    <li><a href="about.html" class="link text_mono-gray-5 text-body-1">О компании</a>
                                    </li>
                                    <li><a href="services.html" class="link text_mono-gray-5 text-body-1">Услуги</a>
                                    </li>
                                    <li><a href="{{ url_section('blog') }}" class="link text_mono-gray-5 text-body-1">Блог</a></li>
                                    <li><a href="contact-us.html" class="link text_mono-gray-5 text-body-1">Контакты</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </footer><!-- /.footer -->
