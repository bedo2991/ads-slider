<div>
    <div class="fixed-top loader-line" wire:loading.delay></div>
    <x-forms.helpers.banner :bannerMessage="$form->errorMessage" :level="$form->level" />

    <div class="row justify-content-center">
        <div class="col-md-9">
            <form wire:submit="save">
                <x-forms.inputs.text name="form.import_name" label="{{__('Import Name')}}" placeholder="{{__('My Import Name')}}" required />
                <x-forms.inputs.url name="form.import_url" label="{{__('Import URL')}}" placeholder="{{__('https://link_to_published.json')}}" />
                <x-forms.inputs.checkbox name="form.import_disabled" label="{{__('Import Disabled')}}">
                    <x-forms.helpers.help text="{{__('When checked, the import from this URL is stopped')}}" />
                </x-forms.inputs.checkbox>
                <h2>{{ __('Default values if not provided in the event data') }}</h2>
                <x-forms.inputs.text name="form.place" label="{{__('Place')}}" placeholder="bc-Club\n0,50€/1€" />
                <x-forms.inputs.color name="form.color" label="{{__('Color')}}" />
                <x-forms.inputs.url name="form.link" placeholder="{{__('https://an.useful.url/will-be-shown-as-QR')}}" />
                <x-forms.inputs.icon-picker name="form.icon" placeholder="beer star compact-disc glass-cheers">

                    <x-forms.helpers.any>
                        Pick an icon from&nbsp;<a href="https://fontawesome.com/search?m=free&s=solid"
                            target="_blank">here</a>
                    </x-forms.helpers.any>

                </x-forms.inputs.icon-picker>
                <x-forms.inputs.text name="form.marquee" label="{{__('Marquee')}}">
                    <x-forms.helpers.help text="{{__('This message will run at the bottom of the screen during the event.')}}" />
                </x-forms.inputs.text>

                <x-forms.inputs.number name="form.preparation_time" label="{{__('Preparation Time')}}" min="0" max="60" step="1">
                    <x-forms.helpers.help
                        text="{{__('For how many minutes should the preparation countdown be shown? If empty: 30 minutes.')}}" />
                </x-forms.inputs.number>

                {{-- TODO: maybe support menus?
                <x-forms.inputs.select name="form.menus" multiple>
                    <x-slot:options>
                        @forelse ($allMenus as $menu)
                            <option value="{{ $menu->id }}">{{ $menu->name }}</option>
                @empty
                <option disabled>No menus available, create one first</option>
                @endforelse
                </x-slot>
                </x-forms.inputs.select>
                --}}

                <x-forms.inputs.checkbox name="form.disabled">
                    <x-forms.helpers.help
                        text="{{__('When checked, the current event does not get displayed in the advertisement slides. If the event is not happening, \'Cancelled\' must also be selected')}}" />
                </x-forms.inputs.checkbox>

                <x-forms.inputs.checkbox name="form.final_round_confirmed" label="Final round confirmed"
                    default-checked>
                    <x-forms.helpers.help
                        text="{{__('When checked, the 15 min final round countdown gets shown 30 minutes before the closing time')}}" />
                </x-forms.inputs.checkbox>

                <x-forms.inputs.checkbox name="form.is_karaoke" label="Is Karaoke?">
                    <x-forms.helpers.help
                        text="{{__('When checked, the upcoming songs and singers from a Google Sheet are displayed on the monitors')}}" />
                </x-forms.inputs.checkbox>

                <x-forms.inputs.checkbox name="form.not_closing" label="Not closing">
                    <x-forms.helpers.help
                        text="{{__('When checked, the \'we are closing/we are closed\' messages are not displayed')}}" />
                </x-forms.inputs.checkbox>

                <x-forms.buttons.primary text="{{ __('Save Event') }}" />
                {{-- <x-forms.buttons.reset text="{{ __('Clear Form') }}" /> --}}
                @isset($deleteButton)
                <x-forms.buttons.delete wire:click.prevent="{{$deleteButton}}" text="{{ __('Delete Events Import') }}" />
                @endisset
            </form>
        </div>
    </div>
</div>