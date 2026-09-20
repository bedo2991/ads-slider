@props(['name', 'label' => null, 'class'=>"form-label"])
<label for="{{ $name }}" class="{{ $class }}">
    @isset($label)
        {{ $label }}
    @else
        {{ str_starts_with($name, 'form.') ? ucfirst(str_replace("_"," ",substr($name, 5)))  : ucfirst(str_replace("_", " ",$name)) }}
    @endisset
</label>