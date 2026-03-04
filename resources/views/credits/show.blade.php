@extends('layouts.section-index')

@section('content')
    <div class="section-opportunities tf-spacing-27">
        <div class="tf-container">
            <div class="content">
                @if(filled($credit->description))
                    {!! $credit->description !!}
                @else
                    <p class="text-body-1 text_mono-gray-7">Описание кредита пока не добавлено.</p>
                @endif
            </div>
        </div>
    </div>
@endsection
