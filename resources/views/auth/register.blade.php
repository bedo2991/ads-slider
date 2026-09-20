@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Register') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="row mb-3">
                            <label for="name" class="col-md-4 col-form-label text-md-end">{{ __('Name') }}</label>

                            <div class="col-md-6">
                                <input id="name" type="text" value="{{ old('name') }}"
                                    class="form-control @error('name') is-invalid @enderror" name="name" required
                                    placeholder="Mario" autocomplete="name" autofocus>

                                @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        @if (auth()->user()->is_admin)
                        <div class="row mb-3">
                            <label for="realm_id"
                                class="col-md-4 col-form-label text-md-end">{{ __('Realm') }}</label>

                            <div class="col-md-6">
                                <select required id="realm_id"
                                    class="form-select @error('realm_id') is-invalid @enderror" name="realm_id">
                                    @foreach ($realms as $r)
                                    <option value="{{ $r->id }}"
                                        {{ $r->id == old('realm_id') ? 'selected' : '' }}>{{ $r->name }}
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

                        <div class="row mb-3">
                            <label for="user_type" class="col-md-4 col-form-label text-md-end">
                                <a class="btn btn-link" data-bs-toggle="collapse" href="#collapseExample" role="button"
                                    aria-expanded="false" aria-controls="collapseExample">
                                    <i class="fas fa-fw fa-circle-info">&nbsp;</i>
                                </a>&nbsp;{{ __('User role') }}
                            </label>


                            <div class="col-md-6">
                                <select id="user_type" class="form-select @error('user_type') is-invalid @enderror"
                                    name="user_type" value="{{ old('user_type') }}">
                                    <option value="member"
                                        {{ 'member' === old('user_type', 'member') ? 'selected' : '' }}>
                                        {{ __('User') }}
                                    </option>
                                    <option value="realm_admin"
                                        {{ 'realm_admin' === old('user_type') ? 'selected' : '' }}>
                                        {{ __('Realm Admin') }}
                                    </option>
                                    @if (auth()->user()->is_admin)
                                    <option value="admin" {{ 'admin' === old('user_type') ? 'selected' : '' }}>
                                        {{ __('Admin') }}
                                    </option>
                                    @endif
                                </select>

                                @error('user_type')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="collapse" id="collapseExample">
                                <div class="card card-body">
                                    <ul>
                                        <li><b>{{ __('User') }}:</b>
                                            {{ __('can do basic editing, creating, updating, and deleting elements
                                                                                                                                                                                                within their realm.') }}
                                        </li>
                                        <li><b>{{ __('Realm Admin') }}:</b>
                                            {{ __('additionally, they can register new users, can send
                                                                                                                                                realtime-alerts, and manage event imports for their realm.') }}
                                        </li>
                                        <li><b>{{ __('Admin') }}:</b>
                                            {{ __('additionally, they can manage realms, view the server logs and
                                                                                                                                                freely switch between realms.') }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="email"
                                class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email"
                                    class="form-control @error('email') is-invalid @enderror" name="email"
                                    placeholder="example@email.com" value="{{ old('email') }}" required
                                    autocomplete="email">

                                @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12 text-info-emphasis text-center mb-3">
                                <i class="fas  fa-fw fa-circle-info">&nbsp;</i>
                                {{ __('A password must be at least 10 characters long and contain at least:') }}<br>
                                {{ __('1 uppercase letter, 1 lowercase letter, 1 digit and 1 special character.') }}
                            </div>
                            <label for="password"
                                class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password"
                                    class="form-control @error('password') is-invalid @enderror" name="password"
                                    required autocomplete="new-password">

                                @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password-confirm"
                                class="col-md-4 col-form-label text-md-end">{{ __('Confirm Password') }}</label>

                            <div class="col-md-6">
                                <input id="password-confirm" type="password" class="form-control"
                                    name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Register') }}
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