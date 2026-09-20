<div>
    <div class="fixed-top loader-line" wire:loading.delay></div>
    <x-forms.helpers.banner :bannerMessage="$form->errorMessage" :level="$form->level" />

    <div class="row justify-content-center">
        <div class="col-md-9">
            <form wire:submit="@if($form->template) updateTemplate @else createTemplate @endif">
                <x-forms.inputs.text name="form.name" placeholder="{{__('My Event Name')}}" required label="{{__('Name')}}" />
                <x-forms.inputs.time name="form.start_time" required label="{{__('Start Time')}}" />
                <x-forms.inputs.time name="form.end_time" required label="{{__('End Time')}}" />

                <div x-data>
                    <template x-if="!$store.ui.isDeleting">
                        <livewire:weekdays wire:model.live="form.repeat" name="form.repeat" label="{{ __('Repeat on…') }}" />
                    </template>
                </div>
                <x-forms.inputs.text name="form.place" placeholder="bc-Club\n0,50€/1€" label="{{__('Place')}}" />
                <x-forms.inputs.color name="form.color" label="{{__('Color')}}" />
                <x-forms.inputs.url name="form.link" placeholder="{{__('https://an.useful.url/will-be-shown-as-QR')}}" label="{{__('Link')}}" />
                <x-forms.inputs.icon-picker name="form.icon" placeholder="beer star compact-disc glass-cheers" label="{{__('Icon')}}">

                    <x-forms.helpers.any>
                        {{__('Pick an icon from here:')}}&nbsp;<a href="https://fontawesome.com/search?m=free&s=solid"
                            target="_blank">{{__('link')}}</a>
                    </x-forms.helpers.any>

                </x-forms.inputs.icon-picker>
                <x-forms.inputs.text name="form.marquee" label="{{__('Marquee')}}">
                    <x-forms.helpers.help text="{{__('This message will run at the bottom of the screen during the event.')}}" />
                </x-forms.inputs.text>

                <x-forms.inputs.number name="form.preparation_time" min="0" max="60" step="1" placeholder="30" label="{{__('Preparation Time')}}">
                    <x-forms.helpers.help
                        text="{{__('For how many minutes should the preparation countdown be shown? If empty: 30 minutes.')}}" />
                </x-forms.inputs.number>

                <x-forms.inputs.select name="form.menus" multiple label="{{__('Menus')}}">
                    <x-slot:options>
                        @forelse ($allMenus as $menu)
                        <option value="{{ $menu->id }}">{{ $menu->name }}</option>
                        @empty
                        <option disabled>{{__('No menus available, create one first')}}</option>
                        @endforelse
                        </x-slot>
                </x-forms.inputs.select>

                <x-forms.inputs.checkbox name="form.final_round_confirmed" label="{{__('Final round confirmed')}}"
                    default-checked>
                    <x-forms.helpers.help
                        text="{{__('When checked, the 15 min final round countdown gets shown 30 minutes before the closing time')}}" />
                </x-forms.inputs.checkbox>

                <x-forms.inputs.checkbox name="form.is_karaoke" label="{{__('Is Karaoke?')}}">
                    <x-forms.helpers.help
                        text="{{__('When checked, the upcoming songs and singers from a Google Sheet are displayed on the monitors')}}" />
                </x-forms.inputs.checkbox>

                <x-forms.inputs.checkbox name="form.not_closing" label="{{__('Not closing')}}">
                    <x-forms.helpers.help
                        text="{{__('When checked, the \'we are closing/we are closed\' messages are not displayed')}}" />
                </x-forms.inputs.checkbox>

                <x-forms.buttons.primary text="{{ __('Save Template') }}" />
                <x-forms.buttons.reset text="{{ __('Clear Template') }}" />
                @if($form->template)
                <x-forms.buttons.delete x-on:click.prevent="if (confirm('{{ __('Are you sure?') }}')) {$store.ui.startDelete(); $wire.deleteTemplate()}" text="{{ __('Delete Template') }}" />
                @endif
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('ui', {
            isDeleting: false,
            startDelete() {
                this.isDeleting = true
            }
        })
    })
</script>