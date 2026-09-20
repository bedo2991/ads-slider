<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="author" content="Francesco Bedini" />
    <meta name="robots" content="noindex, nofollow" />
    @include('layouts.favicon')

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? "{$title} - " . config('app.name', 'AdsSlider') : config('app.name', 'AdsSlider') }}
    </title>

    <!-- Styles -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <script>
        /*!
         * Color mode toggler for Bootstrap's docs (https://getbootstrap.com/)
         * Copyright 2011-2023 The Bootstrap Authors
         * Licensed under the Creative Commons Attribution 3.0 Unported License.
         */

        (() => {
            'use strict'

            const getStoredTheme = () => localStorage.getItem('theme')
            const setStoredTheme = theme => localStorage.setItem('theme', theme)

            const getPreferredTheme = () => {
                const storedTheme = getStoredTheme()
                if (storedTheme) {
                    return storedTheme
                }

                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
            }

            const setTheme = theme => {
                if (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.setAttribute('data-bs-theme', 'dark')
                } else {
                    document.documentElement.setAttribute('data-bs-theme', theme)
                }
            }

            setTheme(getPreferredTheme())

            const showActiveTheme = (theme, focus = false) => {
                const themeSwitcher = document.querySelector('#bd-theme')

                if (!themeSwitcher) {
                    return
                }

                const btnToActive = document.querySelector(`[data-bs-theme-value="${theme}"]`)

                document.querySelectorAll('[data-bs-theme-value]').forEach(element => {
                    element.classList.remove('active')
                    element.setAttribute('aria-pressed', 'false')
                })

                btnToActive.classList.add('active')
                btnToActive.setAttribute('aria-pressed', 'true')

                if (focus) {
                    themeSwitcher.focus()
                }
            }

            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                const storedTheme = getStoredTheme()
                if (storedTheme !== 'light' && storedTheme !== 'dark') {
                    setTheme(getPreferredTheme())
                }
            })

            window.addEventListener('DOMContentLoaded', () => {
                showActiveTheme(getPreferredTheme())

                document.querySelectorAll('[data-bs-theme-value]')
                    .forEach(toggle => {
                        toggle.addEventListener('click', () => {
                            const theme = toggle.getAttribute('data-bs-theme-value')
                            setStoredTheme(theme)
                            setTheme(theme)
                            showActiveTheme(theme, true)
                        })
                    })

                // enable tooltips 
                const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(
                    tooltipTriggerEl))
            })

        })()
    </script>
</head>

<body>
    <div id="app">
        @include('layouts.navbar')



        @if (session('error'))
            <div class="alert alert-danger">
                <strong>{{ __('An error has occurred') }}</strong><br>
                {{ session('error') }}
            </div>
        @endif
        @if (session('success'))
            <div class="alert alert-success">
                <strong>{{ __('Success!') }}</strong><br>
                {{ session('success') }}
            </div>
        @endif

        @include('flash::message')

        <main class="py-4">
            @isset($header)
                <div class="container">
                    <div class="row">
                        <h2>{{ $header }}</h2>
                    </div>
                </div>
            @endisset

            {{-- N.B. content should be inside a .container --}}
            @yield('content')
        </main>
    </div>

    <!-- Scripts -->
    <script type="module">
        $('div.alert').not('.alert-important').delay(3000).fadeOut(350);
    </script>
    <script async src="{{ config('ads.fontAwesomeKitURL') }}" crossorigin="anonymous"></script>
    @yield('scripts')
</body>

</html>
