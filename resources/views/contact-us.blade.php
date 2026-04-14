@extends('layouts.app')

@section('page-header')
    <div class="page-title style-default -mb_11">
        <div class="section-contact style-default position-relative py-0">
            <div class="tf-container">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="left">
                            <div class="heading">
                                <h1 class="mb_21">{{ $contactSettings->title ?: 'Контакты' }}</h1>
                                <ul class="breadcrumb">
                                    <li><a href="{{ url('/') }}" class="link">Главная</a></li>
                                    <li>Контакты</li>
                                </ul>
                            </div>
                            <div class="bot">
                                <div class="content mb-0">
                                    @php($contactEmail = $siteSettings?->email ?: 'themesflat@gmail.com')
                                    <h6>{{ $contactEmail }}</h6>
                                    <p class="text-body-2 text_mono-gray-6">Срочные вопросы по почте</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <form class="form-contact" method="post" action="{{ route('contact.store') }}">
                            @csrf

                            @if($errors->has('form'))
                                <div class="alert alert-warning mb_16">
                                    {{ $errors->first('form') }}
                                </div>
                            @endif
                            @if($errors->any())
                                <div class="alert alert-danger mb_16">
                                    <ul class="mb-0 ps-3">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if(session('contact_form_success'))
                                <div class="alert alert-success mb_16">
                                    {{ session('contact_form_success') }}
                                </div>
                            @endif

                            <fieldset>
                                <label class="mb_15" for="contact-full-name">Полное имя*</label>
                                <input type="text" name="full_name" id="contact-full-name" value="{{ old('full_name') }}" required>
                                @error('full_name')<div class="text-danger mt_6">{{ $message }}</div>@enderror
                            </fieldset>

                            <div class="grid-2 gap_24 ">
                                <fieldset>
                                    <label class="mb_15" for="contact-email">Эл. почта*</label>
                                    <input type="email" name="email" id="contact-email" value="{{ old('email') }}" required>
                                    @error('email')<div class="text-danger mt_6">{{ $message }}</div>@enderror
                                </fieldset>
                                <fieldset>
                                    <label class="mb_15" for="contact-phone">Номер телефона <span class="text_mono-gray-5">(Необязательно)</span></label>
                                    <input
                                        type="tel"
                                        name="phone"
                                        id="contact-phone"
                                        value="{{ old('phone') }}"
                                        placeholder="+7 (___) ___-__-__"
                                        inputmode="tel"
                                    >
                                    @error('phone')<div class="text-danger mt_6">{{ $message }}</div>@enderror
                                </fieldset>
                            </div>

                            <fieldset>
                                <label class="mb_15" for="contact-message">Сообщение</label>
                                <textarea class="message" name="message" id="contact-message">{{ old('message') }}</textarea>
                                @error('message')<div class="text-danger mt_6">{{ $message }}</div>@enderror
                            </fieldset>

                            <fieldset class="mb_16">
                                <label class="d-flex align-items-start gap_8" for="contact-consent">
                                    <input
                                        type="checkbox"
                                        name="personal_data_consent"
                                        id="contact-consent"
                                        value="1"
                                        {{ old('personal_data_consent') ? 'checked' : '' }}
                                        required
                                        style="margin-top: 4px;"
                                    >
                                    <span class="text-body-3 text_mono-gray-7">
                                        Я даю согласие на обработку персональных данных
                                    </span>
                                </label>
                                @error('personal_data_consent')<div class="text-danger mt_6">{{ $message }}</div>@enderror
                            </fieldset>

                            <button type="submit" class="tf-btn btn-primary2 mt_22">
                                <span>Отправить сообщение</span>
                                <span class="bg-effect"></span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="shape position-absolute">
                <img src="{{ asset('template/images/item/shape-5.png') }}" alt="item">
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const input = document.getElementById('contact-phone');
            if (!input) return;

            const applyMask = (value) => {
                let digits = (value || '').replace(/\D/g, '');
                if (digits.startsWith('7') || digits.startsWith('8')) {
                    digits = digits.slice(1);
                }
                digits = digits.slice(0, 10);

                let result = '+7';
                if (digits.length > 0) result += ' (' + digits.slice(0, 3);
                if (digits.length >= 4) result += ') ' + digits.slice(3, 6);
                if (digits.length >= 7) result += '-' + digits.slice(6, 8);
                if (digits.length >= 9) result += '-' + digits.slice(8, 10);

                return result;
            };

            input.addEventListener('input', function (e) {
                e.target.value = applyMask(e.target.value);
            });

            input.addEventListener('focus', function (e) {
                if (!e.target.value) {
                    e.target.value = '+7';
                }
            });

            input.addEventListener('blur', function (e) {
                if (e.target.value === '+7') {
                    e.target.value = '';
                }
            });

            if (input.value) {
                input.value = applyMask(input.value);
            }
        })();
    </script>
@endpush

@section('content')
    <div class="main-content style-1 ">
    </div>
@endsection
