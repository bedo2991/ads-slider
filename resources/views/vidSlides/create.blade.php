@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>{{ __('New Video Slide') }}</h2>
        <div class="row justify-content-center">
            <livewire:edit-video-slide :$allVideos :$selected_video />
        </div>
    </div>
@endsection
