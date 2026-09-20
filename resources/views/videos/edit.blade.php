@extends('layouts.app')

@section('content')

    <div class="container">
        <h2>{{ __('Edit Video') }}</h2>
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
                <form action="{{ route('videos.update', $video->id) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('put')
                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('Video Name') }}</label>
                        <input type="text" class="form-control" name="name" id="name"
                            placeholder="{{__('Short Description')}}" required value="{{ old('name', $video->name) }}">
                    </div>
                    <div class="mb-3">
                        <label for="monitors[]" class="form-label">{{ __('Show only on these monitors') }}</label>
                        <select id="monitors[]" name="monitors[]" class="form-control" multiple>
                            @foreach ($monitors as $m)
                                <option value="{{ $m->id }}"
                                    {{ $video->monitors->contains($m->id) ? 'selected' : '' }}>
                                    {{ $m->name }}</option>
                            @endforeach
                        </select>
                        <small class="form-text">{{__('Hint: do not select any monitor to show it on all monitors.')}}</small>
                    </div>

                    <div class="mb-3">
                        <label for="clock_location" class="form-label">{{__('Clock position')}}</label>
                        <select id="clock_location" name="clock_location" class="form-control">
                            <option value="0" {{ old('clock_location', $video) == 0 ? 'selected' : '' }}>  {{__('Disabled')}}</option>
                            <option value="1" {{ old('clock_location', $video) == 1 ? 'selected' : '' }}>↖ {{__('Top left')}}</option>
                            <option value="2" {{ old('clock_location', $video) == 2 ? 'selected' : '' }}>↑ {{__('Top middle')}}</option>
                            <option value="3" {{ old('clock_location', $video) == 3 ? 'selected' : '' }}>↗ {{__('Top right')}}</option>
                            <option value="4" {{ old('clock_location', $video) == 4 ? 'selected' : '' }}>→ {{__('Right middle')}}</option>
                            <option value="5" {{ old('clock_location', $video) == 5 ? 'selected' : '' }}>↘ {{__('Bottom right')}} ({{__('default')}})</option>
                            <option value="6" {{ old('clock_location', $video) == 6 ? 'selected' : '' }}>↓ {{__('Bottom middle')}}</option>
                            <option value="7" {{ old('clock_location', $video) == 7 ? 'selected' : '' }}>↙ {{__('Bottom left')}}</option>
                            <option value="8" {{ old('clock_location', $video) == 8 ? 'selected' : '' }}>← {{__('Left middle')}}</option>
                            <option value="9" {{ old('clock_location', $video) == 9 ? 'selected' : '' }}>⊚ {{__('Center')}}</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="bg_color" class="form-label">{{ __('Background Color') }}</label>
                        <input type="color" class="form-control" name="bg_color" id="bg_color"
                            value="{{ old('bg_color', $video->bg_color) }}">
                    </div>

                    <div class="mb-3">
                        <label for="color" class="form-label">{{ __('Text Color') }}</label>
                        <input type="color" class="form-control" name="color" id="color"
                            value="{{ old('color', $video->color) }}">
                    </div>

                    <div class="img_padding"
                        style="background-color: {{ $video->bg_color }};
                        color:{{ $video->color }}">
                        <a href="{{ route('videos.show', $video->id) }}" target="picPreview">
                            <video height="200px" class="preview" controls muted autoplay>
                                <source
                                    src="{{ Storage::disk('public')->url(config('ads.vid_basepath') . $video->path) }}"
                                    type="video/mp4">
                                {{__('This browser does not support videos')}}
                            </video></a><br>
                        {{ __('Example Text') }}
                    </div>
                    <!-- TODO: Allow in the future to change video
                        <div class="mb-3">
                            <label for="upload" class="form-label">{{ __('Upload Video') }}</label>
                            <input type="file" class="form-control" accept="image/*" name="upload" id="upload" required>
                        </div>
                        -->
                    <input type="submit" class="btn btn-primary btn-block" value="Edit Video">
                    <button class="btn btn-danger" name="delete_button">{{ __('Delete Video') }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script type="module">
        $('button[name=delete_button]').click((e) => {
            if (confirm('Are you really sure you want to delete this video?\nAll Slides will be removed, too.')) {
                $(e.target).prepend($('<span class="spinner-border spinner-border-sm"></span>'));
                $.post('{{ route('videos.destroy', $video->id) }}', {
                        _method: 'DELETE',
                        "_token": "{{ csrf_token() }}",
                    }).done((data, textStatus, jqXHR) => {
                        if (data.status === 'success') {
                            document.location = '{{ route('videos.index') }}';
                        } else {
                            alert("Something went wrong!");
                        }
                    })
                    .fail((e) => {
                        alert("Could not send the request. Are you still online?");
                        console.error(e)
                    }).always(() => {
                        $(e.target).find('span').remove();
                    });
            }
            e.preventDefault();
            return false;
        });
    </script>
@endsection
