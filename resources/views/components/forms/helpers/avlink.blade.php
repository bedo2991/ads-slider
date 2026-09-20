@props(['link' => null, 'label' => null])
<div class="mb-3">
    <x-forms.helpers.label name="av_link" label="{{ $label ?? __('AV Link') }}" />
    <div class="input-group">
        <input id="av_link" type="text" class="form-control" {{ $attributes }} value="{{ $link }}">
        <button id="copyAVLink" @class(['btn btn-outline-primary' , 'd-none'=> !$link]) data-bs-toggle="tooltip" data-bs-placement="top"
            data-bs-title="{{ __('Copy link') }}" type="button"><i class="fas fa-fw fa-copy"></i></button>
        <button class="btn btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="top"
            data-bs-title="{{ __('Generate a new random link') }}" wire:click="refreshApiToken"
            @if ($link) wire:confirm="All the AVs with the old link will not be able to edit the event anymore, are you sure?" @endif
            type="button"><i class="fas fa-fw fa-arrows-rotate"></i></button>
        @if ($link)
        <button @class(['btn btn-outline-danger' , 'd-none'=> !$link]) data-bs-toggle="tooltip" data-bs-placement="top"
            data-bs-title="{{ __('Remove AV access') }}" wire:click="removeApiToken"
            wire:confirm="All the AVs with the old link will not be able to edit the event anymore, are you sure?"
            type="button"><i class="fas fa-fw fa-trash"></i></button>
        @endif
    </div>
    {{ $slot }}
</div>

@script
<script>
    const button = document.getElementById("copyAVLink");
    button.addEventListener("click", function() {
        const input = document.getElementById("av_link");
        input.select();
        navigator.clipboard.writeText(input.value);
    });
</script>
@endscript