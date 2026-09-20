<nav class="navbar navbar-expand-md shadow-sm sticky-top bg-body-tertiary">
    <div class="container">
        <a class="navbar-brand" href="{{ url('/') }}"
            @auth data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="{{ 'V. ' . config('app.version') }}" @endauth>
            {{ config('app.name', 'Laravel') }}
            @if (config('app.env', 'production') != 'production')
                <span class="badge text-bg-info align-text-top">dev</span>
            @endif
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation menu') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <!-- Left Side Of Navbar -->
            <ul class="navbar-nav me-auto">

            </ul>

            <!-- Right Side Of Navbar -->
            <ul class="navbar-nav ms-auto">
                @auth
                    <!-- Events actions -->
                    <li class="nav-item dropdown">
                        <a id="dropDownEvents" class="nav-link dropdown-toggle" href="#" role="button"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                            {{ __('Events') }} <span class="caret"></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropDownEvents">
                            <a class="dropdown-item" href="{{ route('events.index') }}" data-bs-toggle="tooltip"
                                data-bs-placement="left" data-bs-title="{{ __('List Events') }}">
                                <i class="fa-solid fa-fw fa-list-ul"></i>&nbsp;{{ __('Events') }}
                            </a>
                            <a class="dropdown-item text-secondary" href="{{ route('events.expired') }}"
                                data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="{{ __('Past Events') }}">
                                <i class="fa-solid fa-fw fa-person-cane"></i>&nbsp;{{ __('Events') }}
                            </a>
                            <a class="dropdown-item text-primary" href="{{ route('events.create') }}"
                                data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="{{ __('Create Event') }}">
                                <i class="fa-solid fa-fw fa-plus"></i>&nbsp;{{ __('Events') }}
                            </a>
                            <hr>
                            <a class="dropdown-item" href="{{ route('templates.index') }}" data-bs-toggle="tooltip"
                                data-bs-placement="left" data-bs-title="{{ __('List Templates') }}">
                                <i class="fa-solid fa-fw fa-list-ul"></i>&nbsp;{{ __('Templates') }}
                            </a>
                            <a class="dropdown-item  text-primary" href="{{ route('templates.create') }}"
                                data-bs-toggle="tooltip" data-bs-placement="left"
                                data-bs-title="{{ __('Create a Template') }}">
                                <i class="fa-solid fa-fw fa-plus"></i>&nbsp;{{ __('Template') }}
                            </a>
                            <hr>
                            <a class="dropdown-item" href="{{ route('menus.index') }}" data-bs-toggle="tooltip"
                                data-bs-placement="left" data-bs-title="{{ __('List Menus') }}">
                                <i class="fa-solid fa-fw fa-list-ul"></i>&nbsp;{{ __('Menus') }}
                            </a>
                            <a class="dropdown-item text-primary" href="{{ route('menus.create') }}"
                                data-bs-toggle="tooltip" data-bs-placement="left"
                                data-bs-title="{{ __('Create a Menu') }}">
                                <i class="fa-solid fa-fw fa-plus"></i>&nbsp;{{ __('Menu') }}
                            </a>
                        </div>
                    </li>
                    <!-- Monitor actions -->
                    <li class="nav-item dropdown">
                        <a id="dropDownMonitors" class="nav-link dropdown-toggle" href="#" role="button"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                            {{ __('Monitors') }}<span class="caret"></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-label="Monitors">
                            <a class="dropdown-item" href="{{ route('monitors.index') }}" data-bs-toggle="tooltip"
                                data-bs-placement="left" data-bs-title="{{ __('List Monitors') }}">
                                <i class="fa-solid fa-fw fa-list-ul"></i>&nbsp;{{ __('Monitors') }}
                            </a>
                            <a class="dropdown-item text-primary" href="{{ route('monitors.create') }}"
                                data-bs-toggle="tooltip" data-bs-placement="left"
                                data-bs-title="{{ __('Create new Monitor') }}">
                                <i class="fa-solid fa-fw fa-plus"></i>&nbsp;{{ __('Monitor ') }}
                            </a>
                        </div>
                    </li>
                    <!-- Medias actions -->
                    <li class="nav-item dropdown">
                        <a id="dropDownMedia" class="nav-link dropdown-toggle" href="#" role="button"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                            {{ __('Media') }} <span class="caret"></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropDownMedia">
                            <a class="dropdown-item" href="{{ route('pics.index') }}" data-bs-toggle="tooltip"
                                data-bs-placement="left" data-bs-title="{{ __('List Pictures') }}">
                                <i class="fa-solid fa-fw fa-list-ul"></i>&nbsp;{{ __('Pictures') }}
                            </a>
                            <a class="dropdown-item text-primary" href="{{ route('pics.create') }}"
                                data-bs-toggle="tooltip" data-bs-placement="left"
                                data-bs-title="{{ __('Upload a Picture') }}">
                                <i class="fa-solid fa-fw fa-upload"></i>&nbsp;{{ __(' a Picture') }}
                            </a>
                            <hr>
                            <a class="dropdown-item" href="{{ route('videos.index') }}" data-bs-toggle="tooltip"
                                data-bs-placement="left" data-bs-title="{{ __('List Videos') }}">
                                <i class="fa-solid fa-fw fa-list-ul"></i>&nbsp;{{ __('Videos') }}
                            </a>
                            <a class="dropdown-item text-primary" href="{{ route('videos.create') }}"
                                data-bs-toggle="tooltip" data-bs-placement="left"
                                data-bs-title="{{ __('Upload a Video') }}">
                                <i class="fa-solid fa-fw fa-upload"></i>&nbsp;{{ __(' a Video') }}
                            </a>
                        </div>
                    </li>

                    <!-- Slides actions -->
                    <li class="nav-item dropdown">
                        <a id="dropDownSlides" class="nav-link dropdown-toggle" href="#" role="button"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                            {{ __('Slides') }} <span class="caret"></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropDownSlides">
                            <a class="dropdown-item" href="{{ route('picSlides.index') }}" data-bs-toggle="tooltip"
                                data-bs-placement="left" data-bs-title="{{ __('List Slides') }}">
                                <i class="fa-solid fa-fw fa-list-ul"></i>&nbsp;{{ __('Picture Slides') }}
                            </a>
                            <a class="dropdown-item text-primary" href="{{ route('picSlides.create') }}"
                                data-bs-toggle="tooltip" data-bs-placement="left"
                                data-bs-title="{{ __('Create a Slide') }}">
                                <i class="fa-solid fa-fw fa-plus"></i>&nbsp;{{ __('Picture Slide') }}
                            </a>
                            <hr>
                            <a class="dropdown-item" href="{{ route('vidSlides.index') }}" data-bs-toggle="tooltip"
                                data-bs-placement="left" data-bs-title="{{ __('List Slides') }}">
                                <i class="fa-solid fa-fw fa-list-ul"></i>&nbsp;{{ __('Video Slides') }}
                            </a>
                            <a class="dropdown-item text-primary" href="{{ route('vidSlides.create') }}"
                                data-bs-toggle="tooltip" data-bs-placement="left"
                                data-bs-title="{{ __('Create a Slide') }}">
                                <i class="fa-solid fa-fw fa-plus"></i>&nbsp;{{ __('Video Slide') }}
                            </a>
                        </div>
                    </li>
                    @if (Auth::user()->is_realm_admin)
                        <!-- Admin actions -->
                        <li class="nav-item dropdown">
                            <a id="dropDownAdmin" class="nav-link dropdown-toggle" href="#" role="button"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                {{ __('Admin') }} <span class="caret"></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropDownAdmin">
                                <a class="dropdown-item text-danger" href="{{ route('alerts.index') }}"><i
                                        class="fa-solid fa-fw fa-bell"></i>&nbsp;{{ __('Send Alert') }}</a>
                                <a class="dropdown-item text-warning" href="{{ route('register') }}"><i
                                        class="fa-solid fa-fw fa-user-plus"></i>&nbsp;{{ __('Register User') }}</a>
                                <hr>
                                @if (auth()->user()->is_admin)
                                    <a class="dropdown-item text-info-emphasis" href="{{ route('log-viewer.index') }}"><i
                                            class="fa-solid fa-fw fa-virus-covid-slash"></i>&nbsp;{{ __('View Logs') }}</a>
                                    <hr>
                                @endif
                                @foreach (\App\Models\Realm::all() as $realm)
                                        @if (auth()->user()->is_admin or auth()->user()->realm_id == $realm->id)
                                        <a class="dropdown-item" href="{{ route('realms.edit', $realm->id) }}"
                                            data-bs-toggle="tooltip" data-bs-placement="left"
                                            data-bs-title="{{ __('Edit Realm') }}">
                                            <i class="fa-solid fa-fw fa-pen-to-square"></i>{{__('Edit :realm_name',['realm_name'=>$realm->name]) }}
                                        </a>
                                        @endif
                                @endforeach
                                    <hr>
                                <a class="dropdown-item" href="{{ route('eventsImports.index') }}"
                                    data-bs-toggle="tooltip" data-bs-placement="left"
                                    data-bs-title="{{ __('List Events Imports') }}">
                                    <i class="fa-solid fa-fw fa-list-ul"></i>&nbsp;{{ __('Events Imports') }}
                                </a>
                                <a class="dropdown-item text-primary" href="{{ route('eventsImports.create') }}"
                                    data-bs-toggle="tooltip" data-bs-placement="left"
                                    data-bs-title="{{ __('Create new Events Imports') }}">
                                    <i class="fa-solid fa-fw fa-plus"></i>&nbsp;{{ __('Events Import') }}</i>
                                </a>
                            </div>
                        </li>
                    @endif
                @endauth
                <!-- Language toggler -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="fas fa-fw fa-language" data-bs-toggle="tooltip" data-bs-placement="left"
                            data-bs-title="{{ __('Switch Language') }}"></i>
                        @php
                            $locale = App::currentLocale() ?? 'en';
                        @endphp
                        @switch($locale)
                            @case('en')
                                EN
                            @break

                            @case('de')
                                DE
                            @break

                            @case('it')
                                IT
                            @break

                            @default
                                EN
                        @endswitch
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="https://forms.gle/6xfyQdciNVisXJ7HA" target="_blank">
                                <i class="fas fa-fw fa-bug"></i>&nbsp;{{ __('Report an issue') }}</a></li>
                        <li><a class="dropdown-item" href="/lang/en">English</a></li>
                        <li><a class="dropdown-item" href="/lang/de">Deutsch</a></li>
                        <li><a class="dropdown-item" href="/lang/it">Italiano</a></li>
                    </ul>
                </li>

                <!-- Dark mode toggler -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="fas fa-fw fa-lightbulb" id="bd-theme" data-bs-toggle="tooltip" data-bs-placement="left"
                            data-bs-title="{{ __('Toggle Dark Mode') }}"></i>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="bd-theme-text" data-bs-popper="static">
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center"
                                data-bs-theme-value="light" aria-pressed="false">
                                <i class="bi me-2 opacity-50 theme-icon  fas fa-fw fa-sun"></i>
                                {{ __('Light') }}
                                <i class="bi ms-auto d-none fas fa-fw fa-check"></i>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center"
                                data-bs-theme-value="dark" aria-pressed="false">
                                <i class="bi me-2 opacity-50 theme-icon fas fa-fw fa-moon"></i>
                                {{ __('Dark') }}
                                <i class="bi ms-auto d-none fas fa-fw fa-check"></i>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center active"
                                data-bs-theme-value="auto" aria-pressed="false">
                                <i class="bi me-2 opacity-50 theme-icon fas fa-fw fa-circle-half-stroke"></i>
                                {{ __('Auto') }}
                                <i class="bi ms-auto d-none fas fa-fw fa-check"></i>
                            </button>
                        </li>
                    </ul>
                </li>

                <!-- Authentication Links -->
                @guest
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                    </li>
                    <!--
                                                                        @if (Route::has('register'))
    <li class="nav-item">
                                                                                <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                                                            </li>
    @endif
                                                                        -->
                @else
                    <li class="nav-item dropdown">
                        <a id="dropDownUser" class="nav-link dropdown-toggle" href="#" role="button"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                            {{ Auth::user()->name }}
                            @if (Auth::user()->is_admin)
                                <span class="badge text-bg-danger align-text-top" data-bs-placement="bottom"
                                    data-bs-toggle="tooltip"
                                    data-bs-title="{{ __('Server Admin') }} ({{ Auth::user()->realm->name }})"><i
                                        class="fas fa-fw fa-crown"></i></span>
                            @elseif (Auth::user()->is_realm_admin)
                                <span class="badge text-bg-warning align-text-top" data-bs-placement="bottom"
                                    data-bs-toggle="tooltip" data-bs-title="{{ __('Admin') }} ({{ Auth::user()->realm->name }})"><i
                                        class="fas fa-fw fa-crown"></i></span>
                            @endif
                            <span class="caret"></span>
                        </a>

                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropDownUser">
                            <a class="dropdown-item" href="{{ route('users.edit', Auth::user()->id) }}"
                                data-bs-toggle="tooltip" data-bs-placement="left"
                                data-bs-title="{{ __('Edit Account') }}">
                                <i class="fa-solid fa-fw fa-user-pen"></i>&nbsp;{{ __('Account') }}
                            </a>
                            <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                                onclick="event.preventDefault();
                                             document.getElementById('logout-form').submit();">
                                <i class="fa-solid fa-fw fa-person-through-window"></i>&nbsp;{{ __('Logout') }}
                            </a>

                            <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                style="display: none;">
                                @csrf
                            </form>
                        </div>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>
