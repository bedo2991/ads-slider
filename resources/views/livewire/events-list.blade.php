<div class="container">
    <div class="row">
        @if (session('error'))
        <div class="alert alert-danger">
            <strong>{{ __('An error has occurred') }}</strong><br>
            {{ session('error') }}
        </div>
        @endif
        @if (session('success'))
        <div class="alert alert-success">
            <strong>{{ __('Success!') }}</strong><br>
            {{ session('success') }}
        </div>
        @endif
        <div class="row my-3">
            <div class="col-md-9">
                <div class="input-group">
                    <span class="input-group-text" id="basic-addon1"><i class="fas fa-fw fa-magnifying-glass"></i></span>
                    <input type="text" name="search" wire:model.live.debounce.200ms="search"
                        wire:keydown.debounce.200ms="searchUpdates" aria-label="Search" class="form-control"
                        @if ($this->allEvents->count() == 0) disabled placeholder="{{ __('Search is disabled as there are no events') }}" @else
                    placeholder="{{ __('Type to filter events by name…') }}" @endif>
                </div>
            </div>
            <div class="col-md-3 mt-2 mt-md-0">
                <button wire:click="clearSearch" class="btn btn-secondary">{{ __('Clear Filter') }}</button>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col col-lg-3 order-lg-1">
                <a href="{{ route('events.create') }}">
                    <button class="btn btn-primary btn-block">{{ __('Create New Event') }}</button>
                </a>
                <p class="form-text">{{ __('Click on an event to edit or delete it.') }}</p>
            </div>
            <div class="col-lg-9 order-lg-0">
                <div class="list-group">
                    @forelse ($this->events as $event)
                    <a wire:key="event-{{ $event->id }}" href="{{ route('events.edit', $event->id) }}"
                        class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h4 class="{{ $event->cancelled ? 'event-cancelled' : '' }}">
                                <i class="event-icon fas
                        fa-{{ $event->icon }}"
                                    style="background-color:{{ config('ads.background-color', '#000000') }};
                                   color:{{ $event->color }}"></i>&nbsp;<span
                                    class="fw-bolder">{{ $event->name }}</span>
                            </h4>
                            <small>
                                {!! $event->user
                                ? $event->user->name
                                : '<i class="fas fa-fw fa-robot" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="' .
                                            __('Imported') .
                                            '"></i>' !!}
                            </small>
                        </div>
                        <p class="event-start">
                            {{ Carbon\Carbon::parse($event->real_start_date)->isoFormat('dddd LL') }}
                            {{ $event->repeat
                                    ? __('- Repeats on:') .
                                        ' ' .
                                        join(
                                            ', ',
                                            array_map(function ($el) {
                                                if ($el === '7') {
                                                    $el = 0;
                                                }
                                                return Carbon\Carbon::now()->next(intval($el))->dayName;
                                            }, str_split($event->repeat)),
                                        )
                                    : '' }}
                        </p>
                        <p class="event-start_time"><i
                                class="fas fa-fw fa-clock"></i>&nbsp;{{ substr($event->start_time, 0, 5) . ' - ' . substr($event->end_time, 0, 5) }}
                        </p>
                        <p class="event-place"><i class="fas fa-fw fa-map-marked-alt"></i>&nbsp;{{ $event->place }}</p>
                        <p>
                            @if ($event->schedule?->disabled)
                            @include('badges.disabled')
                            @endif
                            @if (!$event->final_round_confirmed)
                            @include('badges.no_final_round')
                            @endif
                            @if ($event->not_closing)
                            @include('badges.not_closing')
                            @endif
                            @if ($event->is_karaoke)
                            @include('badges.karaoke')
                            @endif
                            @if ($event->is_protected)
                            @include('badges.protected')
                            @endif
                            @if ($event->link)
                            @include('badges.qr', ['event' => $event])
                            @endif
                            @if ($event->import_id)
                            @if ($event->created_at->gte($event->updated_at))
                            @include('badges.imported', ['event' => $event])
                            @else
                            @include('badges.imported_edited', ['event' => $event])
                            @endif
                            @endif
                        </p>
                        <button class="btn btn-sm btn-danger float-end"
                            wire:click.prevent="deleteEvent({{ $event->id }})"
                            wire:confirm="{{ __('Are you sure you want to delete the event ":EVENT_NAME"?', ['EVENT_NAME' => $event->name]) }}"
                            data-bs-toggle="tooltip" data-bs-title="{{ __('Delete Event') }}">
                            <i class="fas fa-fw fa-trash-can"></i>
                        </button>
                    </a>
                    @empty
                    @if ($search)
                    <h3>{{ __('No events found') }}</h3>
                    <button wire:click="clearSearch"
                        class="btn btn-primary col-3">{{ __('Clear Search') }}</button>
                    @else
                    <h3>{{ __('No events found, how about creating one?') }}</h3>
                    @endif
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-12 justify-content-center">
            {{ $this->events->links() }}
        </div>
    </div>
</div>