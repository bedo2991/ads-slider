<div>
    <div class="fixed-top loader-line" wire:loading.delay></div>
    <x-forms.helpers.banner :bannerMessage="$form->errorMessage" :level="$form->level" />

    <div class="row justify-content-center">
        <div class="col-md-9">
            <form wire:submit="@if($form->monitor) updateMonitor @else createMonitor @endif">
                <x-forms.inputs.text name="form.name" placeholder="{{__('My Monitor Name')}}" required label="{{__('Name')}}" />
                <x-forms.inputs.select name="form.locale" label="{{__('Locale')}}" >
                        <x-slot:options>
                        @foreach ([null, 'de', 'en', 'it'] as $loc)
                            <option value="{{ $loc }}">
                                {{ $loc }}</option>
                        @endforeach
                        </x-slot>
                    </x-forms.inputs.select>
                <x-forms.inputs.number name="form.events_to_show" min="1" max="15" step="1" required label="{{__('Events to show')}}">
                    <x-forms.helpers.help
                        text="{{__('The number of events slides which will be shown on this monitor')}}" />
                </x-forms.inputs.number>

                <h4 class="mt-3">{{__('Events and Countdowns')}}</h4>
                <x-forms.inputs.checkbox name="form.show_preparation_countdowns" label="{{__('Show preparation countdowns')}}"/>

                <x-forms.inputs.checkbox name="form.show_final_rounds" label="{{__('Show final rounds')}}"/>

                <x-forms.inputs.checkbox name="form.show_we_are_closing" label="{{__('Show -we are closing- messages')}}"/>

                <x-forms.inputs.checkbox name="form.show_we_are_closed_marketing" label="{{__('Show -we are closed- message')}}"/>

                <x-forms.inputs.checkbox name="form.show_event_while_is_happening" label="{{__('Show event while is happening')}}"/>

                <x-forms.inputs.checkbox name="form.show_cancelled_events" label="{{__('Show cancelled events')}}"/>

                <x-forms.inputs.checkbox name="form.show_menus" label="{{__('Show menus')}}"/>

                <x-forms.inputs.checkbox name="form.show_happy_hours" label="{{__('Show happy hours')}}"/>

                <x-forms.inputs.checkbox name="form.show_orderslist" label="{{__('Show orders list')}}"/>
                
                <h4 class="mt-3">{{__('Media')}}</h4>

                <x-forms.inputs.checkbox name="form.show_pictures" label="{{__('Show pictures')}}"/>

                <x-forms.inputs.checkbox name="form.show_videos" label="{{__('Show videos')}}"/>

                <h4 class="mt-3">{{__('Integrations')}}</h4>

                <x-forms.inputs.checkbox disabled name="form.show_karaoke" label="{{__('Show karaoke'). ' (' . __('Currently deprecated') . ')'}}"/>

                <x-forms.inputs.checkbox :disabled="!$hasOpenweatherApiKey" name="form.show_weather_forecast" label="{{__('Show weather forecast')}}">
                    @if (!$hasOpenweatherApiKey)
                        <x-forms.helpers.help>
                            @if ($realm && (auth()->user()->is_admin || auth()->user()->is_realm_admin))
                                {{ __('Please set up the Openweather API Key in the') }} <a href="{{ route('realms.edit', $realm->id) }}">{{ __('settings') }}</a>.
                            @else
                                {{ __('Please set up the Openweather API Key in the settings.') }}
                            @endif
                        </x-forms.helpers.help>
                    @endif
                </x-forms.inputs.checkbox>

                <h4 class="mt-3">{{__('Performance')}}</h4>
                <x-forms.helpers.any>
                     <p>{{__('The following settings are for performance tuning. Only enable them on a Raspberry Pi 3+')}}</p>
                </x-forms.helpers.any>

                <x-forms.inputs.checkbox name="form.use_animations" label="{{__('Use animation')}}"/>

                <x-forms.inputs.checkbox disabled name="form.show_marquee" label="{{__('Show marquee') . ' (' . __('Currently deprecated') . ')'}}"/>

                <x-forms.inputs.checkbox name="form.show_videos" label="{{__('Show videos')}}"/>

                <div class="my-3"/>

                <x-forms.buttons.primary text="{{ __('Save Monitor') }}" />
                {{-- <x-forms.buttons.reset text="{{ __('Clear Form') }}" /> --}}
                @isset($deleteButton)
                    <x-forms.buttons.delete wire:click.prevent="{{$deleteButton}}" text="{{ __('Delete Monitor') }}" />
                @endisset
            </form>
        </div>
    </div>
</div>
