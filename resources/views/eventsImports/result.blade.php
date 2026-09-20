@extends('layouts.app')
@section('content')
<div class="container">
    <pre style="background-color:black; color:white; padding: 3px; height:70vh; overflow-y:auto;  box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);">
        {{!! e($logs) !!}}
    </pre>
    <a href="{{route('eventsImports.index')}}" class="btn btn-secondary">
    {{__('Back')}}
    </a>

</div>
@endsection