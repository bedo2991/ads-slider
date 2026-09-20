@props(['text' => null])
<p class="form-text">
    {{ $text ?? $slot }}
</p>