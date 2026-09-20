@props(['name', 'label' => null])
<div class="mb-3">
    <x-forms.helpers.label :name="$name" :label="$label" />
    <input id="{{ $name }}" name="{{ $name }}" wire:model="{{ $name }}" type="file"
        class="form-control @error($name) is-invalid @enderror" {{ $attributes }}>
    @error($name)
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
</div>
