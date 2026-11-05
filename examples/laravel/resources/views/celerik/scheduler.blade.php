@extends('celerik.layout')

@section('title', 'Scheduler')

@section('content')
    <p class="text-body-secondary small">
        Drag or resize a meeting to move it, select a time range to create one, click a meeting to delete it.
    </p>

    <x-celerik :component="$scheduler" />
@endsection
