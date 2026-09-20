@extends('layouts.app')

@section('content')

<div class="container">
    <h2>{{__('New Picture Slide')}}</h2>
    <div class="row justify-content-center">
        <livewire:edit-picture-slide action="create" :$allPictures :$selected_picture />
    </div>
</div>
@endsection