<button class="btn btn-danger"
wire:confirm="Are you sure you want to delete this element?" {{$attributes}}  type="button" name="delete_button">
    @isset($text)
        {{ $text }}
    @else
        {{__('Delete')}}
    @endisset
</button>
