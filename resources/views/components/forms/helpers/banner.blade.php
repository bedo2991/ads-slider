@props(['bannerMessage', 'level', 'important' => true])
@if(!empty($bannerMessage))
<div class="sticky-top alert
alert-{{ $level ?? 'info' }} {{ (($important ?? false) ? 'alert-dismissible fade show' : '') }}"
role="alert" 
>
@if ($important ?? false)
<button type="button"
 class="btn-close" 
 data-bs-dismiss="alert" 
 aria-label="Close" wire:click.prevent="form.clearMessage">
</button>
@endif

{!! $bannerMessage !!}
</div>
@endif