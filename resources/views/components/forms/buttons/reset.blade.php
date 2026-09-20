<button class="btn btn-warning" type="reset" title="{{ $text ?? 'Reset' }}"
wire:click="form.resetForm"
wire:confirm="Are you sure you want to clear this form?">
    @isset($text)
        {{ $text }}
    @else
        {{__('Reset')}}
    @endisset
</button>
