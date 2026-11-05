@extends('celerik.layout')

@section('title', 'File manager')

@section('content')
    <x-celerik :component="$fileManager" />
@endsection
