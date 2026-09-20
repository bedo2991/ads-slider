@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Your Profile') }}</div>

                    <div class="card-body">

                        <form method="POST" action="{{ route('users.update', $user->id) }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-3 row">
                                <label for="name"
                                    class="col-md-4 col-form-label text-md-end form-label">{{ __('Name') }}</label>

                                <div class="col-md-6">
                                    <input id="name" value="{{ old('name', $user->name) }}" type="text"
                                        class="form-control @error('name') is-invalid @enderror" name="name" required
                                        autocomplete="nickname">

                                    @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="email"
                                    class="col-md-4 col-form-label text-md-end form-label">{{ __('Email') }}</label>

                                <div class="col-md-6">
                                    <input id="email" type="email" value="{{ old('email', $user->email) }}"
                                        class="form-control @error('email') is-invalid @enderror" name="email" required
                                        autocomplete="email">

                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            @if (auth()->user()->is_admin)
                                <div class="row mb-3">
                                    <label for="realm_id"
                                        class="col-md-4 col-form-label text-md-end form-label">{{ __('Realm') }}
                                    </label>

                                    <div class="col-md-6">
                                        <select required id="realm_id"
                                            class="form-select @error('realm_id') is-invalid @enderror" name="realm_id">
                                            @foreach ($realms as $r)
                                                <option value="{{ $r->id }}"
                                                    {{ $r->id == old('realm_id', $user->realm_id) ? 'selected' : '' }}>
                                                    {{ $r->name }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @error('realm_id')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                            @endif

                            <div class="row mb-0">
                                <div class="col-md-8 offset-md-4">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Submit') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
