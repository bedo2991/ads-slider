@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>{{ __('New Alert Message') }}</h2>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="/alerts">
            @csrf
            <div class="mb-3">
                <label for="title" class="form-label">{{ __('Title') }} *</label>
                <input name="title" required type="text" id="title" class="form-control"
                    placeholder="{{ __('Title') }}" value="{{ old('title') }}" />
            </div>

            <div class="mb-3">
                <label for="message" class="form-label">{{ __('Message') }} *</label>
                <textarea name="message" required maxlength="200" class="form-control" id="message" rows="3">{{ old('message') }}</textarea>
                <div class="form-text">{{ __('Markdown supported:') }} *bold* _italics_ ~striked~ ```console```</div>
            </div>

            <div class="mb-3">
                <label for="link" class="form-label">{{ __('Link') }}</label>
                <input type="url" name="link" class="form-control" id="link" value="{{ old('link') }}"
                    placeholder="https://...">
                <div class="form-text">{{ __('will be shown as a QR code') }}</div>
            </div>

            <div class="row">
                <div class="col">
                    <div class="mb-3">
                        <label for="level" class="form-label">{{ __('Severity') }} *</label>
                        <select name="level" required class="form-control" id="level" value="{{ old('level') }}">
                            <option value="0" {{ old('level') == 0 ? 'selected' : '' }}>{{ __('Info') }}</option>
                            <option value="1" {{ old('level') == 1 ? 'selected' : '' }}>{{ __('Warning') }}</option>
                            <option value="2" {{ old('level') == 2 ? 'selected' : '' }}>{{ __('Error') }}</option>
                            <option value="3" {{ old('level') == 3 ? 'selected' : '' }}>{{ __('Catastrophy') }}
                            </option>
                        </select>
                        <div class="form-text">{{ __('The alert is shown in a different style, based on the severity') }}
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="mb-3">
                        <label for="showFor" class="form-label">{{ __('Display message for x seconds') }}</label>
                        <input required type="number" name="showFor" id="showFor" step="1" min="5"
                            max="900" class="form-control" value="{{ old('showFor', 30) }}" />
                        <div class="form-text">{{ __('Default: 30s') }}</div>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <button type="reset" class="btn btn-secondary btn-sm">{{ __('Reset') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Send alert') }}</button>
            </div>
        </form>

    </div>
@endsection

@section('scripts')
    <script></script>
@endsection
