@extends('layouts.app')

@section('title', 'Главная')

@section('content')
<h1>Главная</h1>
<p>Данные из HomePageSettings (админка: Настройки главной).</p>

<dl>
    <dt>hero_title</dt>
    <dd>{{ $settings->hero_title ?? '—' }}</dd>
    <dt>hero_description</dt>
    <dd>{{ $settings->hero_description ?? '—' }}</dd>
    <dt>advantages_block_title</dt>
    <dd>{{ $settings->advantages_block_title ?? '—' }}</dd>
    <dt>about_title</dt>
    <dd>{{ $settings->about_title ?? '—' }}</dd>
    <dt>about_description</dt>
    <dd>{{ $settings->about_description ?? '—' }}</dd>
    <dt>about_image</dt>
    <dd>
        @if($settings->about_image)
            <img src="{{ asset('storage/' . $settings->about_image) }}" alt="" width="200">
            <br>{{ $settings->about_image }}
        @else
            —
        @endif
    </dd>
</dl>

<h2>Преимущества (advantages)</h2>
@forelse(($settings->advantages ?? []) as $adv)
<ul>
    <li>id: {{ $adv->id }}</li>
    <li>title: {{ $adv->title }}</li>
    <li>description: {{ $adv->description }}</li>
    <li>image: @if($adv->image) <img src="{{ asset('storage/' . $adv->image) }}" alt="" width="100"> {{ $adv->image }} @else — @endif</li>
    <li>sort_order: {{ $adv->sort_order }}</li>
</ul>
@empty
<p>Нет преимуществ.</p>
@endforelse
@endsection
