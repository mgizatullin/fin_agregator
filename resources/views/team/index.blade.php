@extends('layouts.app')

@push('styles')
    <style>
        @media (min-width: 992px) {
            .team-page .section-team.style-2 .tf-grid-layout-2 {
                gap: 89px 100px;
            }
        }

        .team-page .team-item.style-default.v2:hover .content {
            opacity: 1;
            visibility: visible;
            transform: none;
        }

        .team-page .team-item.style-default.v2 .bot {
            display: block;
        }

        .team-page .team-section-description {
            max-width: 960px;
            margin-top: 56px;
        }
    </style>
@endpush

@section('page-header')
    @include('layouts.partials.page-header', [
        'title' => $title ?? 'Специалисты',
        'subtitle' => $subtitle ?? null,
        'breadcrumbs' => [
            ['url' => url('/'), 'label' => 'Главная'],
            ['label' => $title ?? 'Специалисты'],
        ],
    ])
@endsection

@section('content')
    <div class="main-content style-1 team-page">
        <div class="section-team style-2 tf-spacing-9 pb-0">
            <div class="tf-container">
                <div class="wrap">
                    <div class="tf-grid-layout-2 lg-col-4 ">
                        @forelse($items as $item)
                            @php
                                $photo = filled($item->photo)
                                    ? asset('storage/' . ltrim((string) $item->photo, '/'))
                                    : asset('template/images/item/team-emty.png');
                            @endphp

                            <div class="team-item v2 style-default hover-border hover-image">
                                <div class="img-style mb_19">
                                    <img src="{{ $photo }}" alt="{{ $item->name }}" style="width: 100%; aspect-ratio: 1 / 1; object-fit: cover;">
                                </div>

                                <div class="bot">
                                    <div class="content">
                                        <h3 class="name">
                                            <span class="link hover-line-text">{{ $item->name }}</span>
                                        </h3>

                                        @if(filled($item->position))
                                            <p class="text-body-1">{{ $item->position }}</p>
                                        @endif

                                        @if(filled($item->short_description))
                                            <p class="text-body-2 text_mono-gray-7 mt_8">{{ $item->short_description }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-body-1 text_mono-gray-7">Специалисты пока не добавлены.</p>
                        @endforelse
                    </div>

                    @if(filled($section?->description ?? null))
                        <div class="team-section-description text-body-1 text_mono-gray-7">
                            {!! $section->description !!}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
