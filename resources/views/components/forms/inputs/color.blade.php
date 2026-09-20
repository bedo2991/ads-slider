@props(['name', 'label'=> null])
<div class="mb-3">
    <x-forms.helpers.label :name="$name" :label="$label" />
    <input id="{{ $name }}" wire:model.change="{{ $name }}" type="color" class="form-control @error($name) is-invalid @enderror"
        {{$attributes}}>
    @error($name)
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
    {{ $slot }}
</div>
