@extends('layouts.app')

@section('content')

<div class="container">
    <h2>{{__('New Picture')}}</h2>
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
            <form action="{{route('pics.store')}}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="upload" class="form-label">{{__('Upload Picture')}}</label>
                    <input type="file" class="form-control" accept="image/*" name="upload" id="upload" required>
                </div>
                <div class="mb-3">
                    <label for="name" class="form-label">{{__('Picture Name')}}</label>
                    <input type="text" class="form-control" name="name" id="name" placeholder="{{__('Short Description')}}"
                        required value="{{ old('name') }}">
                </div>
                <div class="mb-3">
                    <label for="duration" class="form-label">{{__('Duration (seconds)')}}</label>
                    <input type="number" class="form-control" name="duration" id="duration" placeholder="{{__('15')}}"
                        required value="{{ old('duration', 15) }}" min="1">
                </div>
                <div class="mb-3">
                    <label for="monitors[]" class="form-label">{{__('Show only on these monitors')}}</label>
                    <select id="monitors[]" name="monitors[]" class="form-control" multiple>
                        @foreach($monitors as $m)
                        <option value="{{$m->id}}">{{$m->name}}</option>
                        @endforeach
                    </select>
                    <small class="form-text">Hint: do not select any monitor to show it on all
                        monitors.</small>
                </div>
                <input type="submit" class="btn btn-primary btn-block" value="Upload Picture">
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