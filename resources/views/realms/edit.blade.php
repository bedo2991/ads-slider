@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>{{ __('Edit Realm') }}</h2>
        <livewire:edit-realm :$realm/>
    </div>
@endsection

@section('scripts')
@endsection
