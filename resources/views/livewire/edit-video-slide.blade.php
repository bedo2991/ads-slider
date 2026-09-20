<div class="col-md-9">
    <div class="fixed-top loader-line" wire:loading.delay></div>
    <x-forms.helpers.banner :bannerMessage="$form->errorMessage" :level="$form->level" />

    <form wire:submit="@if ($form->schedule) updateVideoSlide @else createVideoSlide @endif">
        <x-forms.inputs.time name="form.start_time" label="{{ __('Start Time') }}" />
        <x-forms.inputs.time name="form.end_time" label="{{ __('End Time') }}" />

        <x-forms.inputs.date name="form.start" label="{{ __('Start') }}" />
        <x-forms.inputs.date name="form.end" label="{{ __('End') }}" />


        <div x-data>
            <template x-if="!$store.ui.isDeleting">
                <livewire:weekdays wire:model="form.repeat" name="form.repeat" label="{{ __('Repeat on…') }}" />
            </template>
        </div>

        <x-forms.inputs.checkbox name="form.disabled" label="{{ __('Disabled') }}">
            <x-forms.helpers.help text="{{ __('When checked, the slide is not displayed') }}" />
        </x-forms.inputs.checkbox>

        <x-forms.inputs.select name="form.video_id" required>
            <x-slot:options>
                <option selected value="">{{ __('=== Select video ===') }}</option>
                @forelse ($allVideos as $vid)
                <option value="{{ $vid->id }}">{{ $vid->name }}</option>
                @empty
                <option disabled>{{ __('No vids available, upload one first') }}</option>
                @endforelse
                </x-slot>
        </x-forms.inputs.select>

        @if($form->video_id)
        <div class="mb-3 text-center">
            <a href="{{ route('videos.edit', $form->video_id) }}" target="_blank">{{ __('Edit Video') }} <i class="fas fa-fw fa-external-link-alt"></i></a>
        </div>
        @endif

        <div class="my-3" />

        <x-forms.buttons.primary text="{{ __('Save Video Slide') }}" />
        {{-- <x-forms.buttons.reset text="{{ __('Clear Form') }}" /> --}}
        @if ($form->schedule)
        <x-forms.buttons.delete x-on:click.prevent="if (confirm('{{ __('Are you sure?') }}')) {$store.ui.startDelete(); $wire.deleteVideoSlide()}" text="{{ __('Delete Video Slide') }}" />
        @endif
    </form>
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