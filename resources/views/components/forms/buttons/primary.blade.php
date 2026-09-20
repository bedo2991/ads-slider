<button type="submit" class="btn btn-primary">
    @isset($text) {{$text}} @else {{__('Submit')}} @endisset
</button>