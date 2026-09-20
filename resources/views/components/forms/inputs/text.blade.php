@props(['name', 'label' => null, 'readonly' => false])
<div class="mb-3">
    <x-forms.helpers.label :name="$name" :label="$label"/>
    <input id="{{ $name }}" wire:model.change="{{ $name }}" type="text" class="form-control @error($name) is-invalid @enderror @if($readonly) form-control-plaintext @endif"
           name="{{ $name }}" {{ $readonly ? 'readonly' : '' }} {{$attributes->merge(['placeholder' => $label])}}
        {{$attributes}}>
    @error($name)
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
    {{ $slot }}
</div>
