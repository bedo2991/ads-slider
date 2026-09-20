@extends('layouts.app')
@section('content')
<div class="container">
    <h2>{{ __('All Templates') }}</h2>
    <div class="row">
        <div class="col col-lg-3 order-lg-1">
            <a href="{{ route('templates.create') }}">
                <button
                    class="btn btn-primary btn-block">{{ __('Create New
                                                            Template') }}</button>
            </a>
            <p>{{ __('Click on a template to edit or delete it.') }}</p>
        </div>
        <div class="col-lg-9 order-lg-0">
            <div class="list-group">
                @forelse ($templates as $template)
                <a href="{{ route('templates.edit', $template->id) }}" class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <h4><i class="fas fa-fw fa-{{ $template->icon }}"
                                style="background-color:{{ config('ads.background-color', '#000000') }};
                                           color:{{ $template->color }}"></i>&nbsp;{{ $template->name }}
                        </h4>
                        <small>{{ $template->user->name }}</small>
                    </div>
                    <p class="event-start_time"><i
                            class="fas fa-fw fa-clock"></i>&nbsp;{{ substr($template->start_time, 0, 5) }}</p>
                    <p class="event-place"><i class="fas fa-fw fa-map-marked-alt"></i>&nbsp;{{ $template->place }}</p>
                    <p>
                        @if ($template->not_closing)
                        @include('badges.not_closing')
                        @endif
                        @if (!$template->final_round_confirmed)
                        @include('badges.no_final_round')
                        @endif
                        @if ($template->is_karaoke)
                        @include('badges.karaoke')
                        @endif
                    </p>
                </a>
                @empty
                <h3>{{ __('No templates yet, what about adding one?') }}</h3>
                @endforelse
            </div>
        </div>
    </div>
    <div class="row justify-content-center">
        {{ $templates->links() }}
    </div>
</div>
</div>
@endsection