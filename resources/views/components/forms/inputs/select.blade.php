@props(['name', 'label' => null, 'options'])
<div class="mb-3">
    <x-forms.helpers.label :name="$name" :label="$label" />
    <select class="form-control" id="{{ $name }}" name="{{ $name }}" wire:model.change="{{ $name }}"
        {{ $attributes }}>
        {{ $options }}
    </select>
    @error($name)
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
    {{ $slot }}
</div>
