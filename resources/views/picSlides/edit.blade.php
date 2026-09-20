@extends('layouts.app')

@section('content')
<div class="container">
    <h2>{{__('Edit Picture Slide')}}</h2>
    <div class="row justify-content-center">
        <livewire:edit-picture-slide action="edit" :$picSlide :$allPictures />
    </div>
</div>
@endsection