@extends('celerik.layout')

@section('title', 'Theme builder')

@section('content')
    <x-celerik :component="$builder" />
@endsection
