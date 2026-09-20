@extends('layouts.app')
@section('content')
<div class="container">
    <h2>{{__('All Menus')}}</h2>
    <div class="row">
        <div class="col col-lg-3 order-lg-1">
            <a href="{{ route('menus.create') }}">
                <button class="btn btn-primary btn-block">{{__('Create New Menu')}}</button>
            </a>
            <p>{{__('Click on a menu to edit or delete it.')}}</p>
        </div>
        <div class="col-lg-9 order-lg-0">
            <div class="list-group">
                @forelse ($menus as $menu)
                <a href="{{ route('menus.edit', $menu->id) }}" class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <h4>{{ $menu->name }}</h4>
                        <small>{{ $menu->user->name }}</small>
                    </div>
                </a>
                @if (count($menu->events) > 0)
                <details>
                    <summary>{{__('Used in these Events')}}</summary>
                    @foreach ($menu->events as $e)
                    <a href="{{ route('events.edit', $e->id) }}">
                        <p>{{ $e->name }}</p>
                    </a>
                    @endforeach
                </details>
                @else
                <p class="text-muted"><i class="fas fa-fw fa-info-circle"></i>&nbsp;{{__('Currently not used in any event.')}} <a href="{{ route('events.index') }}">{{__('Assign it to one!')}}</a></p>
                @endif
                @empty
                <h3>{{ __('No menus yet, what about adding one?') }}</h3>
                @endforelse
            </div>
        </div>
    </div>
    <div class="row justify-content-center">
        {{ $menus->links() }}
    </div>
</div>
@endsection