@extends('layouts.app')
@section('content')
    <div class="container">
        <h2>{{ __('Future Events') }}</h2>
        <livewire:events-list />
    </div>
@endsection
