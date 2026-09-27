@props(['name', 'label' => null, 'readonly' => false, 'rows' => 5])
<div class="mb-3">
    <x-forms.helpers.label :name="$name" :label="$label"/>
    <textarea id="{{ $name }}" wire:model.change="{{ $name }}" rows="{{ $rows }}" class="form-control @error($name) is-invalid @enderror @if($readonly) form-control-plaintext @endif"
           name="{{ $name }}" {{ $readonly ? 'readonly' : '' }} {{$attributes->merge(['placeholder' => $label])}}
        {{$attributes}}></textarea>
    @error($name)
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
    {{ $slot }}
</div>
