@extends('layouts.app')
@section('content')
<div class="container">
    <h2>{{ __('All Pictures') }}</h2>
    <div class="row">
        <div class="col col-lg-3 order-lg-1">
            <a href="{{ route('pics.create') }}">
                <button class="btn btn-primary btn-block">{{ __('Upload new Picture') }}</button>
            </a>
            <a href="{{ route('picSlides.create') }}">
                <button class="btn btn-secondary btn-block">{{ __('Create new Slide') }}</button>
            </a>
            <p>{{ __('Click on a Picture to edit or delete it.') }}</p>
        </div>
        <div class="col-lg-9 order-lg-0">
            <div class="list-group">
                @forelse ($pictures as $picture)
                <a href="{{ route('pics.edit', $picture->id) }}" class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <h4>{{ $picture->name }}</h4>
                        <small>{{ $picture->user->name }}</small>
                    </div>
                    <div class="img_padding"
                        style="background-color: {{ $picture->bg_color }};
                                color:{{ $picture->color }}">
                        <img class="preview" src="{{ $picture->public_path }}">
                        <span class="example-text">{{ __('Example Text') }}</span>
                    </div>
                </a>
                @if (count($picture->slides) > 0)
                <details>
                    <summary>{{ __('Used in these Slides') }}</summary>
                    @foreach ($picture->slides as $s)
                    <a href="{{ route('picSlides.edit', $s->id) }}">
                        <p>{{ $s->start_time }}</p>
                    </a>
                    @endforeach
                </details>
                @else
                <p class="text-muted"><i
                        class="fas fa-fw fa-info-circle"></i>&nbsp;{{ __('Currently not used in any Slide.') }} <a
                        href="{{ route('picSlides.create') . '/' . $picture->id }}">{{ __('Create one!') }}</a>
                </p>
                @endif
                @empty
                <h3>{{ __('No pictures yet, what about adding one?') }}</h3>
                @endforelse
            </div>
        </div>
    </div>
    <div class="row justify-content-center">
        {{ $pictures->links() }}
    </div>
</div>
@endsection