@extends('layouts.app')

@section('content')

<div class="container">
    <h2>{{__('Edit Picture')}}</h2>
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
            <form action="{{route('pics.update',$picture->id)}}" method="post" enctype="multipart/form-data">
                @csrf
                @method('put')
                <div class="mb-3">
                    <label for="name" class="form-label">{{__('Picture Name')}}</label>
                    <input type="text" class="form-control" name="name" id="name" placeholder="{{__('Short Description')}}"
                        required value="{{ old('name',$picture->name) }}">
                </div>
                <div class="mb-3">
                    <label for="duration" class="form-label">{{__('Duration (seconds)')}}</label>
                    <input type="number" class="form-control" name="duration" id="duration" placeholder="{{__('15')}}"
                        required value="{{ old('duration', $picture->duration) }}" min="1">
                </div>
                <div class="mb-3">
                    <label for="monitors[]" class="form-label">{{__('Show only on these monitors')}}</label>
                    <select id="monitors[]" name="monitors[]" class="form-control" multiple>
                        @foreach($monitors as $m)
                        <option value="{{$m->id}}" {{$picture->monitors->contains($m->id)
                                ?"selected":""}}>{{$m->name}}</option>
                        @endforeach
                    </select>
                    <small class="form-text">{{__('Hint: do not select any monitor to show it on all monitors.')}}</small>
                </div>

                <div class="mb-3">
                    <label for="clock_location" class="form-label">{{__('Clock position')}}</label>
                    <select id="clock_location" name="clock_location" class="form-control">
                        <option value="0" {{ old('clock_location', $picture->clock_location) == 0 ? 'selected' : '' }}> {{__('Disabled')}}</option>
                        <option value="1" {{ old('clock_location', $picture->clock_location) == 1 ? 'selected' : '' }}>↖ {{__('Top left')}}</option>
                        <option value="2" {{ old('clock_location', $picture->clock_location) == 2 ? 'selected' : '' }}>↑ {{__('Top middle')}}</option>
                        <option value="3" {{ old('clock_location', $picture->clock_location) == 3 ? 'selected' : '' }}>↗ {{__('Top right')}}</option>
                        <option value="4" {{ old('clock_location', $picture->clock_location) == 4 ? 'selected' : '' }}>→ {{__('Right middle')}}</option>
                        <option value="5" {{ old('clock_location', $picture->clock_location) == 5 ? 'selected' : '' }}>↘ {{__('Bottom right')}} ({{__('default')}})</option>
                        <option value="6" {{ old('clock_location', $picture->clock_location) == 6 ? 'selected' : '' }}>↓ {{__('Bottom middle')}}</option>
                        <option value="7" {{ old('clock_location', $picture->clock_location) == 7 ? 'selected' : '' }}>↙ {{__('Bottom left')}}</option>
                        <option value="8" {{ old('clock_location', $picture->clock_location) == 8 ? 'selected' : '' }}>← {{__('Left middle')}}</option>
                        <option value="9" {{ old('clock_location', $picture->clock_location) == 9 ? 'selected' : '' }}>⊚ {{__('Center')}}</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="bg_color" class="form-label">{{__('Background Color')}}</label>
                    <input type="color" class="form-control" name="bg_color" id="bg_color" value="{{old('bg_color',
                        $picture->bg_color)}}">
                </div>

                <div class="mb-3">
                    <label for="color" class="form-label">{{__('Text Color')}}</label>
                    <input type="color" class="form-control" name="color" id="color" value="{{old('color', $picture->color)}}">
                </div>


                <!-- TODO: Allow in the future to change picture
                    <div class="mb-3">
                        <label for="upload" class="form-label">{{__('Upload Picture')}}</label>
                        <input type="file" class="form-control" accept="image/*" name="upload" id="upload" required>
                    </div>
                    -->
                <input type="submit" class="btn btn-primary btn-block" value="{{__('Edit Picture')}}">
                <button class="btn btn-danger" name="delete_button">{{__('Delete Picture')}}</button>
            </form>

            <hr>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h3>{{__('Formats')}}</h3>
                <div class="d-flex align-items-center">
                    <label for="preview_screen_format" class="me-2 mb-0 text-nowrap">{{__('Preview Screen Format')}}</label>
                    <select id="preview_screen_format" class="form-control form-control-sm" style="width: auto;">
                        <option value="1.777777">16:9</option>
                        <option value="1.6">16:10</option>
                        <option value="1.333333">4:3</option>
                        <option value="1">1:1</option>
                        <option value="0.5625">9:16</option>
                    </select>
                </div>
            </div>
            <h5>{{__('The best option for each monitor ratio will be selected automatically.')}}</h5>
            <div class="row">
                @foreach ($picture->sources as $source)
                <div class="col-12 col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header">
                            <span class="badge bg-secondary">{{ $source->ratio }}</span>&nbsp;{{ $source->width }}x{{ $source->height }}<br>
                        </div>
                        <div class="card-body">
                            <div class="mb-2 preview_box d-flex justify-content-center align-items-center position-relative mx-auto" style="background-color: {{$picture->bg_color}}; color: {{$picture->color}}; height: 150px; width: 266px; overflow: hidden;">
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(config('ads.pic_basepath').$source->path) }}" target="_blank" class="d-flex w-100 h-100 justify-content-center align-items-center">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(config('ads.pic_basepath').$source->path) }}" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                </a>
                                <span class="preview_text" data-source-id="{{ $source->id }}" style="position: absolute; font-weight: bold; background-color: transparent;">12:34</span>
                            </div>

                            <div class="form-floating d-flex align-items-center">
                                <select class="form-select w-100 form-control-sm clock-location-selector" data-source-id="{{ $source->id }}" id="floatingSelect">
                                    <option value="0" {{ $source->clock_location == 0 ? 'selected' : '' }}> {{__('Disabled')}}</option>
                                    <option value="1" {{ $source->clock_location == 1 ? 'selected' : '' }}>↖ {{__('Top left')}}</option>
                                    <option value="2" {{ $source->clock_location == 2 ? 'selected' : '' }}>↑ {{__('Top middle')}}</option>
                                    <option value="3" {{ $source->clock_location == 3 ? 'selected' : '' }}>↗ {{__('Top right')}}</option>
                                    <option value="4" {{ $source->clock_location == 4 ? 'selected' : '' }}>→ {{__('Right middle')}}</option>
                                    <option value="5" {{ $source->clock_location == 5 ? 'selected' : '' }}>↘ {{__('Bottom right')}} ({{__('default')}})</option>
                                    <option value="6" {{ $source->clock_location == 6 ? 'selected' : '' }}>↓ {{__('Bottom middle')}}</option>
                                    <option value="7" {{ $source->clock_location == 7 ? 'selected' : '' }}>↙ {{__('Bottom left')}}</option>
                                    <option value="8" {{ $source->clock_location == 8 ? 'selected' : '' }}>← {{__('Left middle')}}</option>
                                    <option value="9" {{ $source->clock_location == 9 ? 'selected' : '' }}>⊚ {{__('Center')}}</option>
                                </select>
                                <label for="floatingSelect">{{__('Clock position')}}</label>
                            </div>
                        </div>
                        <div class="card-footer text-body-secondary">
                            <form action="{{ route('pics.destroySource', $source) }}" method="POST" onsubmit="return confirm('{{ __('Delete this format?') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" {{ $picture->sources->count() <= 1 ? 'disabled' : '' }}>{{ __('Delete format') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <form action="{{ route('pics.storeSource', $picture) }}" method="POST" enctype="multipart/form-data" class="card card-body mt-3">
                @csrf
                <h5>{{ __('Add new format') }}</h5>
                <div class="input-group">
                    <input type="file" class="form-control" name="upload" required accept="image/*">
                    <button class="btn btn-primary" type="submit">{{ __('Upload') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script type="module">
    function setPosition(text, val) {
        text.css('top', '').css('bottom', '').css('left', '').css('right', '').css('transform', '');

        switch (parseInt(val)) {
            case 0: // Disabled
                text.hide();
                break;
            case 1: // Top Left
                text.show().css('top', '10px').css('left', '10px');
                break;
            case 2: // Top Middle
                text.show().css('top', '10px').css('left', '50%').css('transform', 'translateX(-50%)');
                break;
            case 3: // Top Right
                text.show().css('top', '10px').css('right', '10px');
                break;
            case 4: // Right Middle
                text.show().css('top', '50%').css('right', '10px').css('transform', 'translateY(-50%)');
                break;
            case 5: // Bottom Right
                text.show().css('bottom', '10px').css('right', '10px');
                break;
            case 6: // Bottom Middle
                text.show().css('bottom', '10px').css('left', '50%').css('transform', 'translateX(-50%)');
                break;
            case 7: // Bottom Left
                text.show().css('bottom', '10px').css('left', '10px');
                break;
            case 8: // Left Middle
                text.show().css('top', '50%').css('left', '10px').css('transform', 'translateY(-50%)');
                break;
            case 9: // Center
                text.show().css('top', '50%').css('left', '50%').css('transform', 'translate(-50%, -50%)');
                break;
        }
    }

    function updateClockPosition(sourceId, val) {
        // Find the specific preview text for this source
        const text = $(`.preview_text[data-source-id="${sourceId}"]`);
        setPosition(text, val);
    }

    // Preview Screen Format updater
    function updatePreviewRatio() {
        const ratio = parseFloat($('#preview_screen_format').val());
        const height = 120;
        const width = height * ratio;
        $('.preview_box').width(width);
    }
    $('#preview_screen_format').change(updatePreviewRatio);
    // Init
    updatePreviewRatio();

    // Initialize all clock positions and listeners
    $('.clock-location-selector').each(function() {
        const sourceId = $(this).data('source-id');
        updateClockPosition(sourceId, $(this).val());

        $(this).change((e) => {
            const select = $(e.target);
            const val = select.val();
            updateClockPosition(sourceId, val);

            // AJAX update
            select.prop('disabled', true);
            $.ajax({
                url: `{{ url('pics/source') }}/${sourceId}`,
                type: 'PUT',
                data: {
                    _token: "{{ csrf_token() }}",
                    clock_location: val
                },
                success: function(result) {
                    select.prop('disabled', false);
                    select.addClass('is-valid');
                    setTimeout(() => select.removeClass('is-valid'), 2000);
                },
                error: function(err) {
                    alert("Error updating clock location");
                    select.prop('disabled', false);
                    select.addClass('is-invalid');
                }
            });
        });
    });

    $('#bg_color').on('input', (e) => {
        $('.preview_box').css('background-color', $(e.target).val());
    });

    $('#color').on('input', (e) => {
        $('.preview_box').css('color', $(e.target).val());
    });

    $('button[name=delete_button]').click((e) => {
        if (confirm('Are you really sure you want to delete this picture?\nAll Slides will be removed, too.')) {
            $(e.target).prepend($('<span class="spinner-border spinner-border-sm"></span>'));
            $.post("{{route('pics.destroy',$picture->id)}}", {
                    _method: 'DELETE',
                    "_token": "{{ csrf_token() }}",
                }).done((data, textStatus, jqXHR) => {
                    if (data.status === 'success') {
                        document.location = "{{route('pics.index')}}";
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