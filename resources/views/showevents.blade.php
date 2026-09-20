<!DOCTYPE html>
<html lang="en-US">

<head>
    <meta charset="UTF-8" />
    <meta name="author" content="Francesco Bedini" />
    <meta name="google" content="notranslate" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="description" content="This page shows slides." />

    <meta name="csrf-token" content="{{ csrf_token() }}" />

    @include('layouts.favicon')
    <title>NEW Ads</title>
    <script src="{{ config('ads.fontAwesomeKitURL') }}" crossorigin="anonymous"></script>
    @vite(['resources/js/slider.ts'])
</head>

<body>
    <div id="main-container">
        <div id="loading-container">
            <div id="loading-content">
                <div class="monitor-name">{{ $data['m']['name'] }}</div>
                <i class="loading fa fa-tv animate__animated animate__swing animate__slow animate__repeat-3"></i>
                <div id="loading-message">{{__('is working hard…')}}</div>
                <div class="version"><span>{{__('Version')}}: </span><span>{{ config('app.version') }}</span></div>
            </div>
        </div>

        <div id="fatal-error-container" style="display:none">
            <div id="fatal-error-content">
                <div class="monitor-name">{{ $data['m']['name'] }}</div>
                <i
                    class="loading fa fa-heart-crack animate__animated animate__swing animate__slow animate__repeat-3"></i>
                <div class="fatal-error">⚠️ {{__('Fatal error!')}} ⚠️</div>
                <div id="fatal-error-message"></div>
                <div class="version"><span>{{__('Version')}}: </span><span>{{ config('app.version') }}</span></div>
            </div>
        </div>

        <div id="final-round-container" style="display:none">
            <div class="finalText animate__animated animate__jello animate__infinite animate__slow">{{__('Final round!')}}</div>
            <div class="countdown"><span class="minutes">&nbsp;</span>:<span class="seconds">&nbsp;</span></div>
        </div>

        <div id="orderslist-container" style="display:none">
            <div class="ordersslide animate__animated animate__fadeIn">
                <div id="ol-pickup" class="ordersbox">
                    <h2 class="title">{{ __('Ready') }}</h2>
                    <div class="orderslist">
                    </div>
                </div>
                    <div id="ol-fulfilled" class="ordersbox">
                    <h2 class="title">{{ __('Fulfilled') }}</h2>
                    <div class="orderslist">
                    </div>
                </div>
                <div id="ol-pending" class="ordersbox">
                    <h2 class="title">{{ __('Pending') }}</h2>
                    <div class="orderslist">
                    </div>
                </div>
                <div id="ol-counters">

                </div>
            </div>
        </div>


        <div id="menu-container" style="display:none">
            <div><span class="menu-name">
                    <!-- -->
                </span><span class="menu-icon">
                    <!-- -->
                </span><!-- -->
            </div>
        </div>
        <template id="menu-row">
            <div class='menu_item animate__animated animate__fadeIn'>
            </div>
        </template>

        <div id="final-15-minutes" style="display:none">
            <i class="fas fa-unlock-alt animate__animated animate__pulse animate__infinite animate__slow">
                <!-- -->
            </i>
            <div id="wir-schliessen">{{__('We will close shortly')}}&nbsp;😢
                @if(app()->getLocale() != 'en')
                    <hr />
                    <span class="english">{{__('We will close shortly', [], 'en')}}</span>
                @endif
            </div>
        </div>

        <div id="thirty-minutes-after" style="display:none">
            <div class="table">
                <i class="fas fa-hourglass-end animate__animated animate__swing animate__slow">
                    <!-- -->
                </i>
                <div id="wir-haben-geschlossen">{{__("Sorry, we're closed!")}}&nbsp;😭
                    @if(app()->getLocale() != 'en')
                    <br><span class="english">{{__("Sorry, we're closed!",[],'en')}}</span>
                    @endif
                </div>
            </div>
            <div id="mitgliederWerbung">
            </div>
        </div>

        <div id="preparations" style="display:none">
            <div class="event-name"></div>
            <i id="preparations-icon"></i>
            <div id="in">{{__('in')}}</div>
            <div class="countdown"><span class="minutes">&nbsp;</span>:<span class="seconds">&nbsp;</span></div>
        </div>

        <div id="happy-hour-container" style="display:none">
            <div class="happyhour animate__animated animate__jello animate__infinite animate__slow">{{__('Happy Hour!')}}</div>
            <div id="happy-hour-drink"></div>
            <div id="happy-hour-priceline"><span>für</span>&nbsp;<span id="price"></span></div>
            <div id="happy-hour-extra">&nbsp;</div>
            <div class="countdown">
                <span class="hours">&nbsp;</span>
                <span class="minutes">&nbsp;</span>:<span class="seconds">&nbsp;</span>
            </div>
        </div>

        <div id="event-slide-small" style="display:none">
            <h1 id="event-name-small" class="event-name"></h1>
            <div>
                <i id="main-icon-small"></i>
                <div id="event-details-small" class="event-details">
                    <div class="start-small"><i class="fas fa-calendar">
                            <!-- -->
                        </i>&nbsp;<span id="start-small"></span></div>
                    <div><i class="fas fa-clock">
                            <!-- -->
                        </i>&nbsp;<span id="start-time-small"></div>
                </div>
            </div>
        </div>


        <div id="event-slide" style="display:none">
            <div id="progress-bar"></div>
            <div id="left-part-container">
                <i id="main-icon"></i>
                <canvas id="qr-code"></canvas>
            </div>
            <div class="event-details">
                <div class="start"><i class="fas fa-calendar">
                        <!-- -->
                    </i>&nbsp;<span id="start"></span></div>
                <div class="start-time"><i class="fas fa-clock">
                        <!-- -->
                    </i>&nbsp;<span id="start-time"></span></div>
                <h1 class="event-name">&nbsp;</h1>
                <div class="section"><i class="fas fa-map-marked-alt"></i>&nbsp;<span id="section"></span></div>
            </div>
            <div id="bar">
                <p id="marqueeText"></p>
            </div>
        </div>
        <div id="karaoke" style="display:none">
            <h1>{{__('Karaoke')}} - {{__('Coming next…')}}</h1>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Song') }}</th>
                        <th>{{ __('Singer') }}</th>
                    </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
        <div id="weather" style="display:none">
            <div>
                <div id="weatherTitle">
                    <div id="theWeather">{{ __('The weather in') }}&nbsp;</div>
                    <div id="placeName">Ilmenau</div>
                </div>
                <div id="sunTimes">
                    <div id="sunRise" class="sunTime"><i class="fas fa-sun">
                            <!-- -->
                        </i><i class="fas fa-arrow-circle-up">
                            <!-- -->
                        </i><time>6:07</time></div>
                    <div id="sunSet" class="sunTime"><i class="fas fa-sun">
                            <!-- -->
                        </i><i class="fas fa-arrow-circle-down">
                            <!-- -->
                        </i><time>18:09</time></div>
                </div>
                <div class="resp-table">
                    <div id="weatherHeader" class="resp-table-header">
                        <div class="time table-header-cell">
                            <i class="fas fa-clock">&nbsp;</i>
                        </div>
                        <div class="weatherIcon table-header-cell">&nbsp;</div>
                        <div class="weatherTemperature table-header-cell">
                            <i class="fas fa-temperature-low">&nbsp;</i>{{__('(feels)')}}
                        </div>
                        <div class="weatherCloud table-header-cell">
                            <i class="fas fa-cloud">&nbsp;</i>
                        </div>
                        <div class="weatherDescr table-header-cell"></div>
                    </div>

                    <div id="weatherRows" class="resp-table-body">

                    </div>
                </div>
                <div id="templateWeather" class="weatherRow resp-table-row" style="display: none">
                    <div class="time table-body-cell">
                        12:00
                    </div>
                    <div class="weatherIcon table-body-cell">
                        <img src="https://openweathermap.org/img/wn/01n@2x.png" alt="?" data-icon="01n"
                            onerror="this.onerror=null;if(this.dataset.icon){this.src='https://openweathermap.org/img/wn/'+this.dataset.icon+'@2x.png';this.dataset.icon = null;}" />
                    </div>
                    <div class="weatherTemperature table-body-cell">
                        <span>23</span><span class="temp_feels">25</span><span class="superscript">°C</span>
                    </div>
                    <div class="weatherCloud table-body-cell">
                        <span>55</span><span class="superscript">%</span>
                    </div>
                    <div class="weatherDescr table-body-cell">Very Very Sunny</div>
                </div>
            </div>
            <div id="noWarranty">{{ __('Information without guarantee') }}</div>
        </div>

        <div id="pics-container" style="display:none">
            <!-- img id="current-pic" src="#" alt="" /-->
        </div>

        <div id="videos-container" style="display:none">
            <!-- img id="current-pic" src="#" alt="" /-->
            <video id="mainVideo" autoplay muted
                poster="{{ Vite::asset('resources/img/videos/VideoIsLoading.gif') }}">
                <source id="mainVideoSource">
                </source>
                <!--track src="example.vtt" default kind="subtitles" srclang="en" label="English"-->
                {{__('This browser does not support the HTML5 video tag')}}
            </video>
        </div>
    </div>
    <div id="clock"></div>
    <div id="alert" style="display:none">
        <i id="alert-icon" class="fas fa-circle-info"></i>
        <div class="row">
            <div class="col">
                <h4 id="alert-title">
                    <!-- Alert title -->
                </h4>
            </div>
            <div class="col-auto">
                <canvas id="alert-qr"></canvas>
            </div>
        </div>
        <div>
            <div id="alert-message">
                <!--Alert message-->
            </div>
        </div>
    </div>
    <script>
        window.getData = () => {
            return {!! json_encode($data, JSON_HEX_TAG) !!}
        };
    </script>
</body>

</html>
