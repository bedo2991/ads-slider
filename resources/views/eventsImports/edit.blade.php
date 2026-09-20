@extends('layouts.app')

@section('content')
<div class="container">
    <h2>{{__('Edit Events Import')}}</h2>
    <livewire:update-events-import :eventsImport="$eventsImport" />
</div>
@endsection

@section('scripts')
@endsection
