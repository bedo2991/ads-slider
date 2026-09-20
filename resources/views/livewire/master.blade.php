@extends('layouts.app', ['header' => $header])

@section('content')
    {{ $slot }}
@endsection