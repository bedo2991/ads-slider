<div>
    <div class="fixed-top loader-line" wire:loading.delay></div>
    <x-forms.helpers.banner :bannerMessage="$form->errorMessage" :level="$form->level" />
    @if (isset($avUpdating) && $avUpdating)
    <div class="alert alert-warning alert-important">{{ __('Editing this event as AV, do not share your private link!') }}</div>
    @else
    @if (isset($event) && $event)
    <div class="row justify-content-center">
        <div class="col-md-9">
            <x-forms.helpers.avlink :link="$event->api_token ? url('/events/' . $event->id . '/edit/' . $event->api_token) : ''" readonly>
                <x-forms.helpers.help
                    text="{{ __('Share this link with the AV to allow them to make some changes to this event shortly before and during the event.') }}" />
            </x-forms.helpers.avlink>
        </div>
    </div>
    @endif
    @endif
    @isset($allTemplates)
    <div class="row justify-content-center">
        @if ($allTemplates->isEmpty())
        <div class="col-md-9">
            <h4>{{ __('You may select a Template…') }}</h4>
            {{-- TODO i18n --}}
            <p class="text-info-emphasis">There are none. Why don't you
                <a href="{{ route('templates.create') }}">create one</a>
                first?
            </p>
        </div>
        @else
        <div class="col-md-9">
            <h4>{{ __('You may select a Template…') }}</h4>
            <select id="templateSelect" class="form-control" data-live-search="true">
                <option disabled selected value> -- {{ __('select a template') }} -- </option>
                @foreach ($allTemplates as $template)
                <option value="{{ $template->id }}">{{ $template->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
    </div>
    @endisset

    <div class="row justify-content-center">
        <div class="col-md-9">
            <form wire:submit="save">
                <x-forms.inputs.text name="form.name" placeholder="{{ __('My Event Name') }}" required />
                <x-forms.inputs.time name="form.start_time" required label="{{ __('Start Time') }}" />
                <x-forms.inputs.time name="form.end_time" required label="{{ __('End Time') }}" />
                <div x-data>
                    <template x-if="!$store.ui.isDeleting">
                        <livewire:weekdays wire:model.live="form.repeat" name="form.repeat" label="{{ __('Repeat on…') }}" />
                    </template>
                </div>
                <x-forms.inputs.date name="form.start" label="{{ __('Start') }}" />
                <x-forms.inputs.date name="form.end" label="{{ __('End') }}" />


                <div class="mb-3">
                    @if ($this->duration)
                    <span
                        class="text-info-emphasis">{{ __('Total duration: :length', ['length' => $this->duration]) }}</span>
                    @else
                    <span class="text-warning">{{ __('Not enough data to compute the event duration') }}</span>
                    @endif
                </div>

                <x-forms.inputs.text name="form.place" placeholder="bc-Club\n0,50€/1€" label="{{ __('Place') }}" />
                <x-forms.inputs.color name="form.color" label="{{ __('Color') }}" />
                <x-forms.inputs.url name="form.link"
                    placeholder="{{ __('https://an.useful.url/will-be-shown-as-QR') }}" label="{{ __('URL') }}" />
                <x-forms.inputs.icon-picker name="form.icon" placeholder="beer star compact-disc glass-cheers"
                    label="{{ __('Icon') }}">

                    <x-forms.helpers.any>
                        {{ __('Pick an icon from here:') }}&nbsp;<a
                            href="https://fontawesome.com/search?m=free&s=solid" target="_blank">link</a>
                    </x-forms.helpers.any>

                </x-forms.inputs.icon-picker>


                <x-forms.inputs.number name="form.preparation_time" min="0" max="60" step="1"
                    placeholder="30" label="{{ __('Preparation Time') }}">
                    <x-forms.helpers.help
                        text="{{ __('For how many minutes should the preparation countdown be shown? If empty: 30 minutes.') }}" />
                </x-forms.inputs.number>

                <x-forms.inputs.select name="form.menus" multiple label="{{ __('Menus') }}">
                    <x-slot:options>
                        @forelse ($allMenus as $menu)
                        <option wire:key="menu-{{ $menu->id }}" value="{{ $menu->id }}">
                            {{ $menu->name }}
                        </option>
                        @empty
                        <option disabled>{{ __('No menus available, create one first') }}</option>
                        @endforelse
                        </x-slot>
                </x-forms.inputs.select>

                <x-forms.inputs.checkbox name="form.disabled" label="{{ __('Disabled') }}">
                    <x-forms.helpers.help
                        text="{{ __('When checked, the current event does not get displayed in the advertisement slides. If the event is not happening, \'Cancelled\' must also be selected') }}" />
                </x-forms.inputs.checkbox>

                <x-forms.inputs.checkbox name="form.cancelled" label="{{ __('Cancelled') }}">
                    <x-forms.helpers.help
                        text="{{ __('When checked, the event is shown as cancelled on the advertisement slides') }}" />
                </x-forms.inputs.checkbox>

                <x-forms.inputs.checkbox name="form.final_round_confirmed" label="{{ __('Final round confirmed') }}"
                    default-checked>
                    <x-forms.helpers.help
                        text="{{ __('When checked, the 15 min final round countdown gets shown 30 minutes before the closing time') }}" />
                </x-forms.inputs.checkbox>

                <x-forms.inputs.checkbox name="form.is_karaoke" label="{{ __('Is Karaoke?') }}">
                    <x-forms.helpers.help
                        text="{{ __('When checked, the upcoming songs and singers from a Google Sheet are displayed on the monitors') }}" />
                </x-forms.inputs.checkbox>

                <x-forms.inputs.checkbox name="form.is_protected" label="{{ __('Locked') }}">
                    <x-forms.helpers.help
                        text="{{ __('While locked, if the event gets updated on the remote server, the changes will not be synced here') }}" />
                </x-forms.inputs.checkbox>

                <x-forms.inputs.checkbox name="form.not_closing" label="{{ __('Not closing') }}">
                    <x-forms.helpers.help
                        text="{{ __('When checked, the \'we are closing/we are closed\' messages are not displayed') }}" />
                </x-forms.inputs.checkbox>

                <x-forms.buttons.primary text="{{ __('Save Event') }}" />
                <x-forms.buttons.reset text="{{ __('Clear Form') }}" />
                @isset($deleteButton)
                <x-forms.buttons.delete
                    x-on:click.prevent="if (confirm('{{ __('Are you sure?') }}')) {$store.ui.startDelete(); $wire.{{ $deleteButton }}()}"
                    text="{{ __('Delete Event') }}" />
                @endisset
            </form>

            <hr class="mt-5">

            <h4 class="mt-4">{{ __('Happy Hour') }}</h4>
            @isset($event)
            <livewire:edit-happy-hour :event="$event" :isManagerUpdating="$avUpdating" />
            @else
            <x-forms.helpers.help text="{{ __('To add an happy hour, first create the event, then edit it.') }}" />
            @endisset
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