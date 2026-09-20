@props(['name', 'label' => null, 'defaultChecked' => false, 'disabled' => false])
<div class="form-check">
    <input class="form-check-input" wire:model="{{ $name }}" id="{{ $name }}"
        name="{{ $name }}" type="checkbox"
        {{ $disabled ? 'disabled' : '' }}
        @if($defaultChecked) checked @endif
        {{ $attributes }}>
    <x-forms.helpers.label :name="$name" :label="$label" class="form-check-label" />
    {{ $slot }}
</div>

