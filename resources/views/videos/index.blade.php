@extends('layouts.app')
@section('content')
<div class="container">
    <h2>{{ __('All Videos') }}</h2>
    <div class="row">
        <div class="col col-lg-3 order-lg-1">
            <a href="{{ route('videos.create') }}">
                <button class="btn btn-primary btn-block">{{ __('Upload new Video') }}</button>
            </a>
            <a href="{{ route('vidSlides.create') }}">
                <button class="btn btn-secondary btn-block">{{ __('Create new Slide') }}</button>
            </a>
            <p>{{ __('Click on a Video to edit or delete it.') }}</p>
        </div>
        <div class="col-lg-9 order-lg-0">
            <div class="list-group">
                @forelse ($videos as $video)
                <a href="{{ route('videos.edit', $video->id) }}" class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <h4>{{ $video->name }}</h4>
                        <small>{{ $video->user->name }}</small>
                    </div>
                    <div class="img_padding"
                        style="background-color: {{ $video->bg_color }};
                                color:{{ $video->color }}">
                        <video height="200px" class="preview" controls muted preload="metadata">
                            <source
                                src="{{ Storage::disk('public')->url(config('ads.vid_basepath') . $video->path) }}"
                                type="video/mp4">
                            {{ __('This browser does not support videos') }}
                        </video><br>
                        <span class="example-text">{{ __('Example Text') }}</span>
                    </div>
                </a>
                @if (count($video->slides) > 0)
                <details>
                    <summary>{{ __('Used in these Slides') }}</summary>
                    @foreach ($video->slides as $s)
                    <a href="{{ route('vidSlides.edit', $s->id) }}">
                        <p>{{ $s->start_time }}</p>
                    </a>
                    @endforeach
                </details>
                @else
                <p class="text-muted"><i
                        class="fas fa-fw fa-info-circle"></i>&nbsp;{{ __('Currently not used in any Slide.') }} <a
                        href="{{ route('vidSlides.create') . '/' . $video->id }}">{{ __('Create one!') }}</a>
                </p>
                @endif
                @empty
                <h3>{{ __('No videos yet, what about adding one?') }}</h3>
                @endforelse
            </div>
        </div>
    </div>
    <div class="row justify-content-center">
        {{ $videos->links() }}
    </div>
</div>
@endsection
@section('scripts')
<script>
    (function stopAutoplay() {
        const videos = document.querySelectorAll('video.preview');
        for (let v of videos) {
            v.pause();
        }
    })();
</script>
@endsection