@extends('celerik.layout')

@section('title', 'Form')

@section('content')
    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    <x-celerik :component="$form" />
@endsection
