@props(['event'])
<span class="badge text-bg-success" title="{{ $event->import_id }}"><i
        class="fas fa-fw fa-file-pen"></i>&nbsp;{{ __('Imported - Edited') }}
    ({{ $event->events_import?->import_name ?? 'unknown' }})</span>