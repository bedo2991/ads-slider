@extends('layouts.app')

@section('content')
<div class="container">
    <h2>{{ __('Edit Event') }}</h2>
    <livewire:update-event :event="$event" :avUpdating="$avUpdating" />
</div>
@endsection

{{-- TODO: when changing the start date, if the end time is before the start time, set timorrow, othewise set today  --}}