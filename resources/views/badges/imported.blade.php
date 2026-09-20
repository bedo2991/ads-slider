@props(['event'])
<span class="badge text-bg-primary" data-bs-toggle="tooltip" data-bs-title="{{ $event->import_id }}"><i
        class="fas fa-file-import"></i>&nbsp;{{__('Imported')}} ({{ $event->events_import?->import_name ?? 'unknown' }})</span>
