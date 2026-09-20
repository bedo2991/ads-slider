@extends('layouts.app')

@section('content')

<div class="container">
    <h2>{{__('Edit Template')}}</h2>
    <div class="row justify-content-center">
        <div class="row justify-content-center">
            <livewire:edit-template :$template :$allMenus />
        </div>
    </div>
</div>
@endsection