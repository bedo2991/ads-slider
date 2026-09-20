@extends('layouts.app')

@section('content')

<div class="container ">
    <h2>{{__('Create Event')}}</h2>
    <livewire:create-event :allTemplates="$allTemplates ?? null" :template="$template??null" />
</div>
@endsection

@section('scripts')
<script type="module">
    $('#templateSelect').change((e) => {
        const templateID = $(e.target).val();
        document.location = "{{route('events.create')}}" + '/' + templateID;
    });

    $('#form.start').change((e) => {
        const date = $(e.target).val();
        const start = new Date(date);
        const start_time = $('#form.start_time').val();
        const end_time = $('#form.end_time').val();
        if (end_time < start_time) {
            // Tomorrow as yyyy-MM-dd
            $('#form.end').val(new Date(start.getTime() + 86400000).toISOString().split('T')[0]);
        } else {
            // Today
            $('#form.end').val(date);
        }
    });
</script>
@endsection