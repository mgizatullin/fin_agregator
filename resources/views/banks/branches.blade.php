@extends('layouts.app')
@include('layouts.partials.redirect-city-push')

@push('styles')
    <style>
        .branches-list {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .branch-card {
            border: 1px solid var(--Mono-gray-2);
            border-radius: 12px;
            background: #fafaff;
            overflow: hidden;
        }

        .branch-card .bank-department__row {
            display: grid;
            grid-template-columns: minmax(0, 120px) minmax(0, 1fr);
            border-bottom: 1px solid var(--Mono-gray-2);
            padding: 14px 16px;
        }

        .branch-card .bank-department__cell {
            padding: 0;
            font-size: 15px;
            line-height: 1.5;
        }

        .branch-card .bank-department__cell--value {
            color: #101828;
            font-weight: 600;
        }

        .branch-card .bank-department__cell--label {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #475467;
        }

        .branch-card .bank-department__icon {
            width: 18px;
            height: 18px;
            flex: 0 0 18px;
            color: #f5b301;
        }

        .branch-card .bank-department__icon svg {
            width: 100%;
            height: 100%;
        }

        .branch-card .bank-department__row:last-child {
            border-bottom: 0;
        }

        .branch-phone {
            display: block;
        }

        .branch-phone-line {
            display: block;
            line-height: 1.35;
        }

        @media (max-width: 1199px) {
            .branches-list {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 991px) {
            .branches-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575px) {
            .branches-list {
                grid-template-columns: 1fr;
            }

            .branch-card .bank-department__row {
                grid-template-columns: 1fr;
            }

            .branch-card .bank-department__cell--label {
                padding-bottom: 6px;
            }

            .branch-card .bank-department__cell--value {
                padding: 0;
            }
        }
    </style>
@endpush

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
                            @if($branch->address)
                                <div class="bank-department__row">
                                    <div class="bank-department__cell bank-department__cell--label">
                                        <span class="bank-department__icon" aria-hidden="true">
                                            <svg viewBox="0 0 20 20" fill="none">
                                                <path d="M10 2.5L17 6.1V13.9L10 17.5L3 13.9V6.1L10 2.5Z" stroke="currentColor" stroke-width="1.4"></path>
                                                <path d="M7.2 10L9 11.8L12.8 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"></path>
                                            </svg>
                                        </span>
                                        <span>Адрес:</span>
                                    </div>
                                    <div class="bank-department__cell bank-department__cell--value branch-address text-body-2 text_mono-gray-7">
                                        {{ $branch->address }}
                                    </div>
                                </div>
                            @endif
                            @if($branch->phone)
                                @php
                                    $phoneParts = collect(explode(',', (string) $branch->phone))
                                        ->map(fn ($part) => trim($part))
                                        ->filter(fn ($part) => $part !== '')
                                        ->values();
                                @endphp
                                <div class="bank-department__row">
                                    <div class="bank-department__cell bank-department__cell--label">
                                        <span class="bank-department__icon" aria-hidden="true">
                                            <svg viewBox="0 0 20 20" fill="none">
                                                <path d="M10 2.5L17 6.1V13.9L10 17.5L3 13.9V6.1L10 2.5Z" stroke="currentColor" stroke-width="1.4"></path>
                                                <path d="M7.2 10L9 11.8L12.8 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"></path>
                                            </svg>
                                        </span>
                                        <span>Телефон:</span>
                                    </div>
                                    <div class="bank-department__cell bank-department__cell--value text-body-2 text_mono-gray-7">
                                        @foreach($phoneParts as $phonePart)
                                            @php
                                                $phoneHref = preg_replace('/[^\d+]/', '', (string) $phonePart);
                                            @endphp
                                            @if(filled($phoneHref))
                                                <a href="tel:{{ $phoneHref }}" class="branch-phone branch-phone-line text-body-2 link">{{ $phonePart }}</a>
                                            @else
                                                <span class="branch-phone-line">{{ $phonePart }}</span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
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

                    return pm;
                });

                clusterer.add(placemarks);
                map.geoObjects.add(clusterer);

                const isCitySelected = @json(!empty($currentCity));

                if (isCitySelected) {
                    const clusterBounds = clusterer.getBounds();

                    if (clusterBounds && clusterBounds.length === 2) {
                        map.setBounds(clusterBounds, { checkZoomRange: true, zoomMargin: 60 });

                        // Guard against excessive zoom-out from malformed/edge bounds.
                        if (map.getZoom() < 10) {
                            map.setZoom(10, { duration: 200 });
                        }
                    } else {
                        map.setCenter([points[0].lat, points[0].lng], 15, { duration: 200 });
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
