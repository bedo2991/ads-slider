@extends('layouts.app')

@section('content')

    <div class="container">
        <h2>{{ __('Edit Menu') }}</h2>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="row">
            <div class="col-md-12">
                <form action="{{ route('menus.update', $menu->id) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('put')
                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('Menu Name') }}</label>
                        <input type="text" class="form-control" name="name" id="name"
                            placeholder="{{ __('Short Description') }}" required value="{{ old('name', $menu->name) }}">
                    </div>
                    <div class="mb-3">
                        <label for="monitors[]" class="form-label">{{ __('Show only on these monitors') }}</label>
                        <select id="monitors[]" name="monitors[]" class="form-control" multiple>
                            @forelse($monitors as $m)
                                <option value="{{ $m->id }}"
                                    {{ $menu->monitors->contains($m->id) ? 'selected' : '' }}>
                                    {{ $m->name }}</option>
                            @empty
                                <option disabled>{{ __('No monitors available') }}</option>
                            @endforelse
                        </select>
                        <small
                            class="form-text">{{ __('Hint: do not select any monitor to show it on all monitors.') }}</small>
                    </div>
                    <div class="mb-3">
                        <label for="menu_content" class="form-label">{{ __('Menu') }}</label>
                        <textarea id="menu_content" name="menu_content" class="form-control" rows="15" cols="33">

                        </textarea>
                        <p class="form-text text-danger" id="invalid_format" style="display:none"></p>
                    </div>
                    <!-- TODO: Allow in the future to change menu
                        <div class="mb-3">
                            <label for="upload" class="form-label">{{ __('Upload Menu') }}</label>
                            <input type="file" class="form-control" accept="image/*" name="upload" id="upload" required>
                        </div>
                        -->
                    <input type="submit" class="btn btn-primary btn-block" value="Edit Menu">
                    <button class="btn btn-danger" name="delete_button">{{ __('Delete Menu') }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script type="module">
        $('button[name=delete_button]').click((e) => {
            if (confirm('Are you really sure you want to delete this menu?')) {
                $(e.target).prepend($('<span class="spinner-border spinner-border-sm"></span>'));
                $.post('{{ route('menus.destroy', $menu->id) }}', {
                        _method: 'DELETE',
                        "_token": "{{ csrf_token() }}",
                    }).done((data, textStatus, jqXHR) => {
                        if (data.status === 'success') {
                            document.location = '{{ route('menus.index') }}';
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
        $('#menu_content').load("{{ route('menus.show', $menu->id) }}");
        $('#menu_content').on('input', (e) => {
            let menu = null;
            try {
                menu = JSON.parse($(e.target).val());
            } catch (e) {
                console.error("Not a valid JSON");
                showError("!! Invalid JSON format !!");
                return;
            }
            let validation = validate(menu[0]);
            if (!validation) {
                console.error("Validation failed");
                return;
            }
            console.log("Validation OK");
            clearErrors();
        });

        function showError(message) {
            $('#invalid_format').text(message);
            $('#invalid_format').show();
        }

        function appendError(message) {
            $('#invalid_format').append(` | ${message}`);
            $('#invalid_format').show();
        }

        function clearErrors() {
            $('#invalid_format').text("");
            $('#invalid_format').hide();
        }

        function validate(m) {
            const fields = ['category_name', 'icon', 'currency', 'products'];
            clearErrors();
            let validationOK = true;
            fields.forEach(element => {
                if (m[element] === undefined) {
                    appendError(`"${element}" is missing`);
                    validationOK = false;
                }
            });
            if (validationOK) {
                const p_fields = ['name', 'price', 'disabled' , 'size' ];
                m.products.forEach((p, i) => {
                    p_fields.forEach(f => {
                        if (p[f] === undefined) {
                            appendError(`"${f}" is missing inside product ${i+1}`);
                            validationOK = false;
                        }
                    });
                });
            }
            return validationOK;
        }
    </script>
@endsection
