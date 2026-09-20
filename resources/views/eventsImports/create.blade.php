@extends('layouts.app')

@section('content')

    <div class="container">
        <h2>{{ __('New Events Import') }}</h2>
        <livewire:create-events-import />
    </div>
@endsection

@section('scripts')
@endsection
