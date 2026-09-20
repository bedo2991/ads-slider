@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                    <h4>{{__('What\'s this?')}}</h4>
                        <p>{{__('AdsSlider allows you to show slides and pictures to advertise your events.')}}</p>

                    <h4>{{__('Where do I start?')}}</h4>
                    <p>{{__('Use the menu on the upper-right corner of this page to manage the slides.')}}</p>

                    <h4>{{__('How do I deploy a Monitor?')}}</h4>
                    <p>{{__('Create one monitor, click on the link to get its URL including the API key and start using it.')}}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
