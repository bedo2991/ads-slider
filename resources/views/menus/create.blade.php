@extends('layouts.app')

@section('content')

<div class="container">
    <livewire:create-menu />
</div>
@endsection

@section('scripts')
<script type="module">
    $('button[name=delete_button]').hide();

    $('button[name=reset]').click((e) => {
        if (!confirm('Are you really sure you want to rollback your input?')) {
            e.preventDefault();
            return false;
        }
        return true;
    });
</script>
@endsection