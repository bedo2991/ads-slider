@extends('layouts.app')

@section('content')
        <div class="container">
            <h2>{{__('Edit Video Slide')}}</h2>
            <div class="row justify-content-center">
                <livewire:edit-video-slide :$vidSlide :$allVideos />
            </div>
        </div>
@endsection