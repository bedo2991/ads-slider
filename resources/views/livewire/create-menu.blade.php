<div>
    <h2>{{ __('New Menu') }}</h2>
    @if ($errors->any())
    <div class="alert alert-danger">
        {{ __('Please fix all errors shown below') }}
    </div>
    @endif
    <div class="row">
        <div class="col-md-12">
            <form wire:submit="save">
                <x-forms.inputs.upload name="upload" label="{{ __('Menu file') }}" accept="application/JSON" required />

                <x-forms.inputs.text name="name" placeholder="{{ __('Short description') }}"
                    label="{{ __('Menu Name') }}" required />

                <x-forms.inputs.select name="monitors" label="{{ __('Monitors') }}" multiple>
                    <x-slot:options>
                        @forelse (\App\Models\Monitor::ofRealm(auth()->user()->realm_id)->orderBy('name')->get() as $m)
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @empty
                        <option disabled>{{ __('No monitors available') }}</option>
                        @endforelse
                        </x-slot>
                        <x-forms.helpers.help
                            text="{{ __('Hint: do not select any monitor to show it on all monitors.') }}" />
                </x-forms.inputs.select>

                <x-forms.buttons.primary text="{{ __('Upload Menu') }}" />
            </form>
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="col-md-5">
            <h5>{{ __('Json File Structure Example') }}</h5>
        </div>
        <div class="col-md-7">
            <pre class="text-muted">
    [
        {
      "category_name": "Cold drinks",
      "icon" : "snowflake",
      "currency": "€",
      "products": [
        {
          "name": "Cola",
          "size": "0,5L",
          "price": "1,30",
          "ingredients": "CURRENTLY_NOT_SHOWN",
          "disabled": false
        },
        {
          "name": "Orange Juice",
          "size": "0,5L",
          "price": "1,20",
          "ingredients": "CURRENTLY_NOT_SHOWN",
          "disabled": false
        },
        {
          "name": "Special Juices",
          "special": true,
          "size": "0,2L",
          "price": "1,00",
          "size2": "0,5L",
          "price2": "2,00",
          "ingredients": "CURRENTLY_NOT_SHOWN",
          "disabled": false
         }
        ]
        }
    ]
                </pre>
        </div>
    </div>

    {{--
        <div class="row">
            <div class="col-md-12">
                <form action="{{ route('menus.store') }}" method="post" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
        <label for="upload" class="form-label">{{ __('Upload Menu') }}</label>
        <input type="file" class="form-control" accept="application/JSON" name="upload" id="upload"
            required>
    </div>
    <div class="mb-3">
        <label for="name" class="form-label">{{ __('Menu Name') }}</label>
        <input type="text" class="form-control" name="name" id="name"
            placeholder="Short Description" required value="{{ old('name') }}">
    </div>
    <div class="mb-3">
        <label for="monitors[]" class="form-label">{{ __('Show only on these monitors') }}</label>
        <select id="monitors[]" name="monitors[]" class="form-control" multiple>
            @foreach ($monitors as $m)
            <option value="{{ $m->id }}">{{ $m->name }}</option>
            @endforeach
        </select>
        <small class="form-text">Hint: do not select any monitor to show it on all
            monitors.</small>
    </div>
    <input type="submit" class="btn btn-primary btn-block" value="Upload Menu">
    </form>
</div>
</div>
<hr>
<div class="row">
    <div class="col-md-5">
        <h5>{{ __('Json File Structure Example') }}</h5>
    </div>
    <div class="col-md-7">
        <pre class="text-muted">
    [
        {
      "category_name": "Cold drinks",
      "icon" : "snowflake",
      "currency": "€",
      "products": [
        {
          "name": "Cola",
          "size": "0,5L",
          "price": "1,30",
          "ingredients": "CURRENTLY_NOT_SHOWN",
          "disabled": false
        },
        {
          "name": "Orange Juice",
          "size": "0,5L",
          "price": "1,20",
          "ingredients": "CURRENTLY_NOT_SHOWN",
          "disabled": false
        },
        {
          "name": "Special Juices",
          "special": true,
          "size": "0,2L",
          "price": "1,00",
          "size2": "0,5L",
          "price2": "2,00",
          "ingredients": "CURRENTLY_NOT_SHOWN",
          "disabled": false
         }
        ]
        }
    ]
                </pre>
    </div>
</div> --}}
</div>