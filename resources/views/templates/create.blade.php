@extends('layouts.app')

@section('content')

<div class="container">
    <h2>{{__('New Template')}}</h2>
    <div class="row justify-content-center">
        <livewire:edit-template :$allMenus />
    </div>
</div>
@endsection
