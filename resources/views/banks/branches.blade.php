@extends('layouts.app')

@section('page-header')
@include('layouts.partials.page-header', [
    'title' => $title ?? ('Отделения ' . $bank->name),
    'showCitySelect' => true,
    'citySelectBase' => 'banki/' . $bank->slug . '/otdeleniya',
    'allowedCitySlugs' => isset($availableCities) ? $availableCities->pluck('slug')->values()->all() : [],
    'city' => $currentCity ?? null,
    'cityName' => 'Вся Россия',
    'breadcrumbs' => $breadcrumbs ?? [
        ['url' => url('/'), 'label' => 'Главная'],
        ['url' => url_section('banki'), 'label' => 'Банки'],
        ['url' => url_section('banki/' . $bank->slug), 'label' => $bank->name],
        ['label' => 'Отделения'],
    ],
])
@endsection

@section('content')
<div class="main-content style-1">
    <div class="section-branches-wrapper tf-spacing-27">
        <div class="tf-container">
            <div class="content">
                @if($bank->branches->isNotEmpty())
                    <p class="section-subtitle text-body-2 text_mono-gray-7 mb_29">
                        Всего отделений: <strong>{{ $bank->branches->count() }}</strong>
                    </p>

                    @php
                        $mapPoints = $bank->branches
                            ->filter(fn ($b) => filled($b->latitude) && filled($b->longitude))
                            ->map(fn ($b) => [
                                'lat' => (float) $b->latitude,
                                'lng' => (float) $b->longitude,
                                'address' => (string) ($b->address ?? ''),
                                'phone' => (string) ($b->phone ?? ''),
                                'working_hours' => (string) ($b->working_hours ?? ''),
                            ])
                            ->values();
                    @endphp

                    @if(true)
                        <div class="branches-map mb_29">
                            <div id="branches-yandex-map" style="width: 100%; height: 420px; border-radius: 16px; overflow: hidden;"></div>
                        </div>
                    @endif
                    
                    <div class="branches-list grid-layout gap_16">
                        @foreach($bank->branches as $branch)
                        <div class="branch-card">
                            <h3 class="branch-name text-body-1">{{ $branch->name ?? 'Отделение' }}</h3>
                            @if($branch->address)
                            <p class="branch-address text-body-2 text_mono-gray-7">
                                <i class="icon-map-marker"></i> {{ $branch->address }}
                            </p>
                            @endif
                            @if($branch->phone)
                            <a href="tel:{{ preg_replace('/[^\d+]/', '', $branch->phone) }}" class="branch-phone text-body-2 link">
                                {{ $branch->phone }}
                            </a>
                            @endif
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-body-1 text_mono-gray-7">Информация об отделениях пока не доступна.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @php
        $yandexMapsKey = config('services.yandex_maps.key');
    @endphp

    @if(!empty($yandexMapsKey))
        <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey={{ $yandexMapsKey }}" defer></script>
    @else
        <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" defer></script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const el = document.getElementById('branches-yandex-map');
            if (!el) return;

            const points = @json($mapPoints ?? []);
            const hasPoints = Array.isArray(points) && points.length > 0;
            const moscowCenter = [55.755864, 37.617698];

            const waitForYmaps = (tries = 0) => {
                if (window.ymaps && typeof window.ymaps.ready === 'function') {
                    window.ymaps.ready(initMap);
                    return;
                }
                if (tries > 50) return;
                setTimeout(() => waitForYmaps(tries + 1), 100);
            };

            const initMap = () => {
                const bounds = [];

                const map = new ymaps.Map('branches-yandex-map', {
                    center: moscowCenter,
                    zoom: hasPoints ? 10 : 10,
                    controls: ['zoomControl', 'fullscreenControl'],
                }, {
                    suppressMapOpenBlock: true,
                });

                if (!hasPoints) {
                    return;
                }

                const clusterer = new ymaps.Clusterer({
                    preset: 'islands#invertedBlueClusterIcons',
                    groupByCoordinates: false,
                });

                const placemarks = points.map((p) => {
                    const contentParts = [];
                    if (p.address) contentParts.push(`<div><strong>Адрес:</strong> ${escapeHtml(p.address)}</div>`);
                    if (p.phone) contentParts.push(`<div><strong>Телефон:</strong> ${escapeHtml(p.phone)}</div>`);
                    if (p.working_hours) contentParts.push(`<div><strong>Время работы:</strong> ${escapeHtml(p.working_hours)}</div>`);

                    const pm = new ymaps.Placemark([p.lat, p.lng], {
                        balloonContentBody: contentParts.join(''),
                    }, {
                        preset: 'islands#blueIcon',
                    });

                    bounds.push([p.lat, p.lng]);
                    return pm;
                });

                clusterer.add(placemarks);
                map.geoObjects.add(clusterer);

                const isCitySelected = @json(!empty($currentCity));

                if (isCitySelected) {
                    if (bounds.length > 1) {
                        map.setBounds(bounds, { checkZoomRange: true, zoomMargin: 60 });
                    } else {
                        map.setCenter(bounds[0], 15, { duration: 200 });
                    }
                } else {
                    // Без выбранного города — центр на Москву, более крупный масштаб.
                    map.setCenter(moscowCenter, 10, { duration: 200 });
                }
            };

            const escapeHtml = (s) => String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            waitForYmaps();
        });
    </script>
@endpush
