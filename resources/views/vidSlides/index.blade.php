@extends('layouts.app')
@section('content')
<div class="container">
    <h2>{{ __('All Video Slides') }}</h2>
    <div class="row">
        <div class="col col-lg-3 order-lg-1">
            <a href="{{ route('vidSlides.create') }}">
                <button class="btn btn-primary btn-block">{{ __('Create New Video Slide') }}</button>
            </a>
            <p>{{ __('Click on a video slide to edit or delete it.') }}</p>
        </div>
        <div class="col-lg-9 order-lg-0">
            <div class="list-group">
                @forelse ($videoSlides as $slide)
                <a href="{{ route('vidSlides.edit', $slide->id) }}"
                    class="list-group-item
                        list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <h4>{{ $slide->scheduleable->name }}</h4>
                        <small>{{ $slide->user->name }}</small>
                    </div>
                    <p class="event-start">{{ $slide->start ? $slide->start->format('M d, Y') : '' }} -
                        {{ $slide->end
                                    ? $slide->end->format('M d,
                                                                                                                                                                                                                                                                                            Y')
                                    : '' }}
                        {{ $slide->repeat
                                    ? __('- Repeats on:') .
                                        ' ' .
                                        join(
                                            ', ',
                                            array_map(function ($el) {
                                                if ($el === '7') {
                                                    $el = 0;
                                                }
                                                return Carbon\Carbon::now()->next(intval($el))->dayName;
                                            }, str_split($slide->repeat)),
                                        )
                                    : '' }}
                    </p>
                    <p class="event-start_time"><i
                            class="fas fa-fw fa-clock"></i>&nbsp;{{ substr($slide->start_time, 0, 5) }}-{{ substr($slide->end_time, 0, 5) }}
                    </p>
                    <p>
                        @if ($slide->disabled)
                        <span class="badge text-bg-danger">{{ __('Disabled') }}</span>
                        @endif
                    </p>
                    <div class="img_padding"
                        style="background-color: {{ $slide->scheduleable->bg_color }};
                            color:{{ $slide->scheduleable->color }}">
                        <video height="200px" class="preview" controls muted preload="metadata">
                            <source
                                src="{{ Storage::disk('public')->url(config('ads.vid_basepath') . $slide->scheduleable->path) }}"
                                type="video/mp4">
                            {{ __('This browser does not support videos') }}
                        </video><br>
                        <span class="example-text">{{ __('Example Text') }}</span>
                    </div>
                </a>
                @empty
                <h3>{{ __('No video slides yet, what about adding one?') }}</h3>
                @endforelse
            </div>
        </div>
    </div>
    <div class="row justify-content-center">
        {{ $videoSlides->links() }}
    </div>
</div>
@endsection