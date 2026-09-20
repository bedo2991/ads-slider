@extends('layouts.app')

@section('content')

    <div class="container">
        <h2>{{__('New Video')}}</h2>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="row justify-content-center">
            <div class="col-md-9">
                <form action="{{route('videos.store')}}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="upload">{{__('Upload Video')}}</label>
                        <input type="file" class="form-control" accept="video/mp4,video/x-m4v,video/*" name="upload" id="upload" required>
                    </div>
                    <div class="mb-3">
                    <label for="name" class="form-label">{{__('Video Name')}}</label>
                    <input type="text" class="form-control" name="name" id="name" placeholder="{{__('Short Description')}}"
                           required value="{{ old('name') }}">
                    </div>
                    <div class="mb-3">
                        <label for="monitors[]" class="form-label">{{__('Show only on these monitors')}}</label>
                        <select id="monitors[]" name="monitors[]" class="form-control" multiple>
                            @foreach($monitors as $m)
                                <option value="{{$m->id}}">{{$m->name}}</option>
                            @endforeach
                        </select>
                        <small class="form-text">{{__('Hint: do not select any monitor to show it on all monitors.')}}</small>
                    </div>
                    <input type="submit" class="btn btn-primary btn-block" value="{{__('Upload Video')}}">
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script type="module">
        $('button[name=delete_button]').hide();

        $('button[name=reset]').click((e) => {
            if (!confirm('Are you really sure you want to rollback your input?')) {
                e.preventDefault();
                return false;
            }
            return true;
        });
    </script>
@endsection
