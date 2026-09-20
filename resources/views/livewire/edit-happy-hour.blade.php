<div>
    <div class="fixed-top loader-line" wire:loading.delay></div>
    <x-forms.helpers.banner :bannerMessage="$hhForm->errorMessage" :level="$hhForm->level" />
    <div class="row justify-content-center">
        <div>

            <form wire:submit="@if($hhForm->event->happy_hour) updateHappyHour{{$isManagerUpdating==true?'AsManager':''}} @else createHappyHour{{$isManagerUpdating==true?'AsManager':''}} @endif">
                <x-forms.inputs.text name="hhForm.drink" required label="{{ __('Drink name') }}"
                    placeholder="{{__('<Cuba Libre>')}}" />
                <x-forms.inputs.text name="hhForm.price" required label="{{ __('Price') }}" placeholder="{{__('?,?0 €')}}" />
                <x-forms.inputs.text name="hhForm.info" label="{{ __('Additional information') }}"
                    placeholder="{{__('optional')}}" />
                <x-forms.inputs.datetime-local name="hhForm.start" required label="{{ __('Show From') }}" />
                <x-forms.inputs.datetime-local name="hhForm.end" required label="{{ __('Show Until') }}" />
                @if ($hhForm->event->happy_hour)
                    <x-forms.buttons.primary text="{{ __('Update Happy Hour') }}" />
                    @isset($deleteButton)
                        <x-forms.buttons.delete wire:click.prevent="{{ $deleteButton }}"
                            text="{{ __('Delete Happy Hour') }}" />
                    @endisset
                @else
                    <x-forms.buttons.primary text="{{ __('Create Happy Hour') }}" />
                @endif
                <x-forms.buttons.reset text="{{ __('Clear Form') }}" />
            </form>
        </div>
    </div>
</div>
