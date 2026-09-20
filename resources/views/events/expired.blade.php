@extends('layouts.app')
@section('content')
    <div class="container">
        <h2>{{ __('Past Events') }}</h2>
        <livewire:past-events-list />
    </div>
@endsection
