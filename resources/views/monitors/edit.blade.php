@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>{{ __('Edit Monitor') }}</h2>
        <livewire:edit-monitor :$monitor/>
    </div>
@endsection

@section('scripts')
@endsection
