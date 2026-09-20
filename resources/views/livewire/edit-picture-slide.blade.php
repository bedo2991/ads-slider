<div class="col-md-9">
    <div class="fixed-top loader-line" wire:loading.delay></div>
    <x-forms.helpers.banner :bannerMessage="$form->errorMessage" :level="$form->level" />

    <form wire:submit="@if ($action=='edit') updatePictureSlide @else createPictureSlide @endif">
        <x-forms.inputs.time name="form.start_time" label="{{ __('Start Time') }}" />
        <x-forms.inputs.time name="form.end_time" label="{{ __('End Time') }}" />

        <x-forms.inputs.date name="form.start" label="{{ __('Start') }}" />
        <x-forms.inputs.date name="form.end" label="{{ __('End') }}" />

        <div x-data="{ isDeleting: false }">
            <template x-if="!isDeleting">
                <livewire:weekdays wire:model="form.repeat" name="form.repeat" label="Repeat on…" />
            </template>

            <x-forms.inputs.checkbox name="form.disabled" label="{{ __('Disabled') }}">
                <x-forms.helpers.help text="{{ __('When checked, the slide is not displayed') }}" />
            </x-forms.inputs.checkbox>

            <x-forms.inputs.select name="form.picture_id" required label="{{ __('Picture') }}">
                <x-slot:options>
                    <option selected value="">{{ __('=== Select picture ===') }}</option>
                    @forelse ($allPictures as $pic)
                    <option value="{{ $pic->id }}">{{ $pic->name }}</option>
                    @empty
                    <option disabled>{{ __('No pics available, upload one first') }}</option>
                    @endforelse
                    </x-slot>
            </x-forms.inputs.select>

            @if ($form->picture_id)
            <div class="row justify-content-md-center">
                <div class="col-md-12 text-center mb-2">
                    <a href="{{ route('pics.edit', $form->picture_id) }}" target="_blank">{{ __('Edit Picture') }} <i class="fas fa-fw fa-external-link-alt"></i></a>
                </div>
                <img src="{{ route('pics.show', $form->picture_id) }}" class="col-md-4" alt="" />
            </div>
            @else
            {{ __('Please select a picture to display it here') }}
            @endif


            <div class="my-3" />

            <x-forms.buttons.primary text="{{ __('Save Picture Slide') }}" />
            {{-- <x-forms.buttons.reset text="{{ __('Clear Form') }}" /> --}}
            @if ($action <> 'create')
                <x-forms.buttons.delete x-on:click.prevent="if (confirm('{{ __('Are you sure?') }}')) { isDeleting = true; $wire.deletePictureSlide({{ $this->form->schedule->id }}) }" text="{{ __('Delete Picture Slide') }}" />
                @endif
        </div>
    </form>
</div>