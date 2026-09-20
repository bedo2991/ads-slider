@props(['name', 'label' => null])
<label for="{{ $name }}" class="form-label">
    <x-forms.helpers.label :name="$name" :label="$label" />
</label> 
<div class="input-group mb-3">
    <i class="align-middle input-group-text fs-4 fas" wire:ignore id="{{ $name }}-addon"></i>
    <input id="{{ $name }}" wire:model="{{ $name }}" type="text" class="form-control @error($name) is-invalid @enderror"
        {{$attributes}}>
    @error($name)
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
    {{ $slot }}
</div>

@script
<script>
    const inp = document.getElementById('{{ $name }}');
    const icon = document.getElementById('{{ $name }}-addon');
    inp.addEventListener('input', function(event) {
        icon.className = `align-middle input-group-text fs-4 fas fa-${event.target.value}`;
});

</script>
@endscript
